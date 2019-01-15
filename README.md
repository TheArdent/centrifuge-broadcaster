<h1 align="center">Centrifuge Broadcaster for Laravel 5</h1>

## Introduction
Centrifuge broadcaster for laravel >= 5.3

## Requirements

- PHP 5.6.4+ or newer
- Laravel 5.3 or newer
- Centrifugo Server 2.0 or newer (see [here](https://github.com/centrifugal/centrifugo))

##### If you use Centrifugo Server 1.6 you should use [LaraComponents/centrifuge-broadcaster](https://github.com/LaraComponents/centrifuge-broadcaster) package

## Installation

Require this package with composer:

```bash
composer require theardent/centrifuge-broadcaster
```

Open your config/app.php and add the following to the providers array:

```php
'providers' => [
    // ...
    TheArdent\Centrifuge\CentrifugeServiceProvider::class,

    // And uncomment BroadcastServiceProvider
    App\Providers\BroadcastServiceProvider::class,
],
```

Open your config/broadcasting.php and add the following to it:

```php
'connections' => [
    'centrifuge' => [
        'driver'           => 'centrifuge',
        'api_key'          => env('CENTRIFUGE_API_KEY'), // you api key
        'secret'           => env('CENTRIFUGE_SECRET'), // you secret key
        'url'              => env('CENTRIFUGE_URL', 'http://localhost:8000'), // centrifuge api url
        'redis_api'        => env('CENTRIFUGE_REDIS_API', false), // enable or disable Redis API
        'redis_connection' => env('CENTRIFUGE_REDIS_CONNECTION', 'default'), // name of redis connection
        'redis_prefix'     => env('CENTRIFUGE_REDIS_PREFIX', 'centrifugo'), // prefix name for queue in Redis
        'redis_num_shards' => env('CENTRIFUGE_REDIS_NUM_SHARDS', 0), // number of shards for redis API queue
        'verify'           => env('CENTRIFUGE_VERIFY', false), // Verify host ssl if centrifuge uses this
        'ssl_key'          => env('CENTRIFUGE_SSL_KEY', null), // Self-Signed SSl Key for Host (require verify=true)
    ],
    // ...
],
```

For the redis configuration, add a new connection in config/database.php

```php
'redis' => [
    'centrifuge' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),,
        'password' => env('REDIS_PASSWORD', null),
        'port'     => env('REDIS_PORT', 6379),
        'database' => 0,
    ],
    // ...
],
```

You can also add a configuration to your .env file:

```
CENTRIFUGE_API_KEY=very-long-api-key
CENTRIFUGE_SECRET=very-long-secret-key
CENTRIFUGE_URL=http://localhost:8000
CENTRIFUGE_REDIS_API=false
CENTRIFUGE_REDIS_CONNECTION=centrifuge
CENTRIFUGE_REDIS_PREFIX=centrifugo
CENTRIFUGE_REDIS_NUM_SHARDS=0
CENTRIFUGE_SSL_KEY=/etc/ssl/some.pem
CENTRIFUGE_VERIFY=false
```

Do not forget to install the broadcast driver

```
BROADCAST_DRIVER=centrifuge
```

## Basic Usage

To configure the Centrifugo server, read the [official documentation](https://centrifugal.github.io/centrifugo/)

For broadcasting events, see the [official documentation of laravel](https://laravel.com/docs/5.7/broadcasting)

A simple example of using the client:

```php
<?php

namespace App\Http\Controllers;

use TheArdent\Centrifuge\Centrifuge;

class ExampleController extends Controller
{
    public function home(Centrifuge $centrifuge)
    {
        // Send message into channel:
        $centrifuge->publish('channel-name', [
            'key' => 'value'
        ]);

        // Generate token without expire:
        $token = $centrifuge->generateToken('user id');

        // Connection token that will be valid for 5 minutes:
        $token = $centrifuge->generateConnectionToken('user id', time() + 5*60);

        //It's also possible to generate private channel subscription token:
        $token = $centrifuge->generatePrivateChannelToken('user id', 'channel');
    }
}
```

### Available methods

| Name | Description |
|------|-------------|
| publish(string $channel, array $data) | Send message into channel. |
| broadcast(array $channels, array $data) | Send message into multiple channel. |
| presence(string $channel) | Get channel presence information (all clients currently subscribed on this channel). |
| history(string $channel) | Get channel history information (list of last messages sent into channel). |
| unsubscribe(string $user_id, string $channel) | Unsubscribe user from channel. |
| disconnect(string $user_id) | Disconnect user by its ID. |
| channels() | Get channels information (list of currently active channels). |
| stats() | Get stats information about running server nodes. |
| generateToken(string $userOrClient, int $exp, array $info = [])  | Generate token with expire time. |
| generatePrivateChannelToken($userOrClient, $channel, $exp = 0, $info = []) | Generate private channel toekn. |


### Difference with LaraComponents/centrifuge-broadcaster

| Name | Description |
|------|-------------|
| publish| Doesn't have client parameter. |
| broadcast | Doesn't have client parameter. |
| generateToken | Generate connection token(JWT),have new parameter int $exp = 0. |
| generateApiSign| Deprecated. |
| generatePrivateChannelToken | New method generate private channel subscription token. |


## License

The MIT License (MIT). Please see [License File](https://github.com/TheArdent/centrifuge-broadcaster/blob/master/LICENSE) for more information.
