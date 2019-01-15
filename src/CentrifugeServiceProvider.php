<?php

namespace TheArdent\Centrifuge;

use Predis\Client as RedisClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;

class CentrifugeServiceProvider extends ServiceProvider
{

    /**
     * Add centrifuge broadcaster.
     *
     * @param \Illuminate\Broadcasting\BroadcastManager $broadcastManager
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('centrifuge', function ($app) {
            return new CentrifugeBroadcaster($app->make('centrifuge'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('centrifuge', function ($app) {
            $config = $app->make('config')->get('broadcasting.connections.centrifuge', []);
            $redis  = $app->make('redis')->connection($config['redis_connection']);

            // for laravel 5.4
            if (! ($redis instanceof RedisClient)) {
                $redis = $redis->client();
            }

            return new Centrifuge($config, $redis);
        });

        $this->app->alias('centrifuge', 'TheArdent\Centrifuge\Centrifuge');
        $this->app->alias('centrifuge', 'TheArdent\Centrifuge\Contracts\Centrifuge');
    }
}
