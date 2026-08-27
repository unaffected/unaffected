<?php

declare(strict_types=1);

namespace Diagonal\Server\Routes;

use Diagonal\Application\Transport;
use Diagonal\Gateway\Gateway;
use Diagonal\Server\Exchange;
use Diagonal\Server\Route;
use TrueAsync\HttpRequest;
use TrueAsync\HttpResponse;

/**
 * The envelope endpoint. Its whole job is to get a request onto the spine —
 * it holds no services and knows nothing about what an action does.
 */
class Api implements Route
{
    public function __construct(
        protected readonly string $path = '/api',
    ) {}

    public function matches(HttpRequest $request): bool
    {
        return $request->getPath() === $this->path && $request->getMethod() === 'POST';
    }

    public function handle(HttpRequest $request, HttpResponse $response, Gateway $gateway): void
    {
        $answered = new Exchange($gateway)->answer(
            $request->getBody(),
            [
                'remote' => $request->getRemoteAddress(),
                'protocol' => $request->getHttpVersion(),
            ],
            Transport::Http,
        );

        $response->json($answered['frame'], $answered['status']);
    }
}
