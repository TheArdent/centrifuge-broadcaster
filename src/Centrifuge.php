<?php

namespace TheArdent\Centrifuge;

use Exception;
use phpcent\Client;
use Predis\PredisException;
use Predis\Client as RedisClient;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use TheArdent\Centrifuge\Contracts\Centrifuge as CentrifugeContract;

class Centrifuge implements CentrifugeContract
{

    const REDIS_SUFFIX = '.api';

    const API_PATH = '/api';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var \Predis\Client
     */
    protected $redisClient;

    /**
     * @var \phpcent\Client
     */
    protected $phpcent;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $redisMethods = ['publish', 'broadcast', 'unsubscribe', 'disconnect'];

    /**
     * Create a new Centrifuge instance.
     *
     * @param array $config
     * @param \Predis\Client|null $redisClient
     */
    public function __construct(array $config, RedisClient $redisClient = null)
    {
        $this->config = $this->initConfiguration($config);

        $this->phpcent = new Client($this->prepareUrl(), $this->config['api_key'], $this->config['secret']);
    }

    /**
     * Init centrifuge configuration.
     *
     * @param  array $config
     * @return array
     */
    protected function initConfiguration(array $config)
    {
        $defaults = [
            'url'              => 'http://localhost:8000',
            'api_key'          => null,
            'secret'           => null,
            'redis_api'        => false,
            'redis_prefix'     => 'centrifugo',
            'redis_num_shards' => 0,
            'ssl_key'          => null,
            'verify'           => true,
        ];

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $defaults)) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Send message into channel.
     *
     * @param string $channel
     * @param array $data
     * @return mixed
     */
    public function publish($channel, array $data)
    {
        $params = ['channel' => $channel, 'data' => $data];

        return $this->send('publish', $params);
    }

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @return mixed
     */
    public function broadcast(array $channels, array $data)
    {
        $params = ['channels' => $channels, 'data' => $data];

        return $this->send('broadcast', $params);
    }

    /**
     * Get channel presence information (all clients currently subscribed on this channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function presence($channel)
    {
        return $this->send('presence', ['channel' => $channel]);
    }

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function history($channel)
    {
        return $this->send('history', ['channel' => $channel]);
    }

    /**
     * Unsubscribe user from channel.
     *
     * @param string $user_id
     * @param string $channel
     * @return mixed
     */
    public function unsubscribe($user_id, $channel)
    {
        $params['channel'] = $channel;
        $params['user']    = (string)$user_id;

        return $this->send('unsubscribe', $params);
    }

    /**
     * Disconnect user by its ID.
     *
     * @param string $user_id
     * @return mixed
     */
    public function disconnect($user_id)
    {
        return $this->send('disconnect', ['user' => (string)$user_id]);
    }

    /**
     * Get channels information (list of currently active channels).
     *
     * @return mixed
     */
    public function channels()
    {
        return $this->send('channels');
    }

    /**
     * Get stats information about running server nodes.
     *
     * @return mixed
     */
    public function stats()
    {
        return $this->send('info');
    }

    /**
     * Generate connection token
     *
     * @param string $userOrClient
     * @param int $exp
     * @param array $info
     * @return string
     */
    public function generateToken($userOrClient, $exp = 0, $info = [])
    {
        return $this->phpcent->generateConnectionToken($userOrClient, $exp, $info);
    }

    /**
     * Generate private channel subscription token
     *
     * @param string $userOrClient
     * @param string $channel
     * @param int $exp
     * @param array $info
     * @return string
     */
    public function generatePrivateChannelToken($userOrClient, $channel, $exp = 0, $info = [])
    {
        return $this->phpcent->generatePrivateChannelToken($userOrClient, $channel, $exp, $info);
    }

    /**
     * Get secret key.
     *
     * @return string
     */
    protected function getSecret()
    {
        return $this->config['secret'];
    }

    /**
     * Send message to centrifuge server.
     *
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    protected function send($method, array $params = [])
    {
        try {
            if ($this->config['redis_api'] === true && ! is_null($this->redisClient) && in_array($method,
                    $this->redisMethods)) {
                $result = $this->redisSend($method, $params);
            } else {
                $result = call_user_func_array([$this->phpcent, $method], $params);
            }
        } catch (Exception $e) {
            $result = [
                'method' => $method,
                'error'  => $e->getMessage(),
                'body'   => $params,
            ];
        }

        return $result;
    }

    /**
     * Prepare URL to send the http request.
     *
     * @return string
     */
    protected function prepareUrl()
    {
        $address = rtrim($this->config['url'], '/');

        if (substr_compare($address, static::API_PATH, -strlen(static::API_PATH)) !== 0) {
            $address .= static::API_PATH;
        }

        return $address;
    }

    /**
     * Send message to centrifuge server from redis client.
     * @param $method
     * @param array $params
     * @return array
     * @throws PredisException
     */
    protected function redisSend($method, array $params = [])
    {
        $json = json_encode(['method' => $method, 'params' => $params]);

        try {
            $this->redisClient->rpush($this->getQueueKey(), $json);
        } catch (PredisException $e) {
            throw $e;
        }

        return [
            'method' => $method,
            'error'  => null,
            'body'   => null,
        ];
    }

    /**
     * Get queue key for redis engine.
     *
     * @return string
     */
    protected function getQueueKey()
    {
        $apiKey    = $this->config['redis_prefix'].self::REDIS_SUFFIX;
        $numShards = (int)$this->config['redis_num_shards'];

        if ($numShards > 0) {
            return sprintf('%s.%d', $apiKey, rand(0, $numShards - 1));
        }

        return $apiKey;
    }
}
