<?php

declare(strict_types=1);

namespace Diagonal\Server;

use Diagonal\Gateway\Gateway;
use TrueAsync\HttpRequest;
use TrueAsync\HttpResponse;

/**
 * One thing the edge can answer. Routes are tried in order, first match wins,
 * so assets and page rendering slot in beside the envelope endpoint.
 */
interface Route
{
    public function matches(HttpRequest $request): bool;

    public function handle(HttpRequest $request, HttpResponse $response, Gateway $gateway): void;
}
