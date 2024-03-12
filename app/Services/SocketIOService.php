<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketIOService
{
    protected $socketUrl;

    public function __construct()
    {
        $this->socketUrl = config('app.socket_url'); // You can set the socket server URL in your .env file or config
    }

    public function emit($event, $data)
    {
        $response = Http::post($this->socketUrl . '/emit', [
            'event' => $event,
            'data' => $data,
        ]);

        Log::info('Socket.IO emit response: ' . $response->body());
    }
}
