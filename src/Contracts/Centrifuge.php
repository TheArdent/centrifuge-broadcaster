<?php

namespace TheArdent\Centrifuge\Contracts;

interface Centrifuge
{

    /**
     * Send message into channel.
     *
     * @param string $channel
     * @param array $data
     * @return mixed
     */
    public function publish($channel, array $data);

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @param array $data
     * @return mixed
     */
    public function broadcast(array $channels, array $data);

    /**
     * Get channel presence information (all clients currently subscribed on this channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function presence($channel);

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function history($channel);

    /**
     * Unsubscribe user from channel.
     *
     * @param string $user_id
     * @param string $channel
     * @return mixed
     */
    public function unsubscribe($user_id, $channel);

    /**
     * Disconnect user by its ID.
     *
     * @param string $user_id
     * @return mixed
     */
    public function disconnect($user_id);

    /**
     * Get channels information (list of currently active channels).
     *
     * @return mixed
     */
    public function channels();

    /**
     * Get stats information about running server nodes.
     *
     * @return mixed
     */
    public function stats();

    /**
     * Generate connection token
     *
     * @param string $userOrClient
     * @param int $exp
     * @param array $info
     * @return string
     */
    public function generateToken($userOrClient, $exp = 0, $info = []);

    /**
     * Generate private channel subscription token
     *
     * @param string $userOrClient
     * @param string $channel
     * @param int $exp
     * @param array $info
     * @return string
     */
    public function generatePrivateChannelToken($userOrClient, $channel, $exp = 0, $info = []);
}
