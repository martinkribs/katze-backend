<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\WebsocketAuth;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register the broadcasting routes with API middleware
        Broadcast::routes([
            'middleware' => ['api'],
            'prefix' => 'api'
        ]);

        // Channel authorization rules are defined in routes/channels.php
        require base_path('routes/channels.php');
    }
}
