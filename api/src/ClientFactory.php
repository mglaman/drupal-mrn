<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;

final class ClientFactory
{
    public static function create(): Client
    {
        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory([
            'max_retry_attempts' => 5,
            'retry_on_status' => [429, 503],
            'default_retry_multiplier' => 1.5,
        ]));

        return new Client(['handler' => $stack]);
    }
}
