<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
               // 'cluster' => env('PUSHER_APP_CLUSTER'),
               // 'encrypted' => true,
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
        'fcm' => [
            'key' => env('FCM_API_KEY','AAAAzwH_bIY:APA91bH5BszHtIvbeXal3RXMNNjXkbmAGjEWRAv0qf5g95wfUMXzV8T3UoDjpwhbh71tRd1vnmeEoogB9JWhpxMmSeUKtDoZUS5sQy3g8aw_7S3QxlDp8i3nv0IUuAxzDeTuGBYTzOuf')
        ]

    ],

];
