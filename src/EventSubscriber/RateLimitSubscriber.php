<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use MyDashboard\Shared\EventSubscriber\RateLimitSubscriber as BaseRateLimitSubscriber;

/**
 * Rate-limiting subscriber for translation-service.
 *
 * Limits all endpoints to 100 requests per minute per IP.
 */
final class RateLimitSubscriber extends BaseRateLimitSubscriber
{
    protected array $bypassPaths = [
        '/translation/health',
        '/translation/translations',
    ];
}
