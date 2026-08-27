<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server;

use Diagonal\Gateway\Gateway;
use Diagonal\Server\Route;
use Diagonal\Server\Routes\Api;
use Diagonal\Server\Routes\Rest;
use Diagonal\Server\Server;
use Diagonal\Tests\Server\Support\HttpClient;
use PHPUnit\Framework\TestCase;
use Throwable;
use TrueAsync\HttpRequest;
use TrueAsync\HttpResponse;

/**
 * The REST fallback matches any path short enough to be a service address, so
 * appending it after everything routes() declared is the only thing keeping it
 * from shadowing them. `answer()` takes extension types and cannot be called
 * without a server, so the order is asserted on the list it walks.
 */
final class ServerTest extends TestCase
{
    private function route(): Route
    {
        return new class implements Route
        {
            public function matches(HttpRequest $request): bool
            {
                return true;
            }

            public function handle(HttpRequest $request, HttpResponse $response, Gateway $gateway): void {}
        };
    }

    /**
     * @param  list<Route>  $routes
     * @return list<Route>
     */
    private function handlers(array $routes): array
    {
        return new class(new Gateway(), $routes) extends Server
        {
            /** @param list<Route> $declared */
            public function __construct(Gateway $gateway, private readonly array $declared)
            {
                parent::__construct($gateway);
            }

            protected function routes(): array
            {
                return $this->declared;
            }

            /** @return list<Route> */
            public function handlers(): array
            {
                return $this->handlers;
            }
        }->handlers();
    }

    public function test_the_edge_answers_the_envelope_endpoint_and_the_rest_fallback_by_default(): void
    {
        $handlers = new class(new Gateway()) extends Server
        {
            /** @return list<Route> */
            public function handlers(): array
            {
                return $this->handlers;
            }
        }->handlers();

        $this->assertCount(2, $handlers);
        $this->assertInstanceOf(Api::class, $handlers[0]);
        $this->assertInstanceOf(Rest::class, $handlers[1]);
    }

    public function test_a_route_a_subclass_declares_is_tried_before_the_rest_fallback(): void
    {
        $probe = $this->route();

        $handlers = $this->handlers([$probe]);

        $this->assertSame($probe, $handlers[0], 'the declared route has to come first');
        $this->assertInstanceOf(Rest::class, $handlers[1]);
    }

    public function test_every_declared_route_keeps_its_order_and_the_fallback_stays_last(): void
    {
        $first = $this->route();
        $second = $this->route();
        $third = $this->route();

        $handlers = $this->handlers([$first, $second, $third]);

        $this->assertSame([$first, $second, $third], array_slice($handlers, 0, 3));
        $this->assertInstanceOf(Rest::class, $handlers[array_key_last($handlers)]);
    }

    public function test_the_rest_fallback_is_appended_once_and_never_declared_twice(): void
    {
        $handlers = $this->handlers([$this->route(), $this->route()]);

        $rest = array_filter($handlers, static fn (Route $route): bool => $route instanceof Rest);

        $this->assertCount(1, $rest);
        $this->assertSame(array_key_last($handlers), array_key_first($rest));
    }

    public function test_a_subclass_that_declares_nothing_is_answered_by_the_fallback_alone(): void
    {
        $handlers = $this->handlers([]);

        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(Rest::class, $handlers[0]);
    }

    /**
     * The live proof of the same thing: /api is one segment, so the REST
     * fallback matches it too — a GET, which Api refuses, falls through and is
     * answered as a service lookup. A POST has to reach Api instead.
     */
    public function test_a_declared_route_wins_the_path_the_fallback_would_have_taken(): void
    {
        $client = $this->reach();

        $envelope = $client->sendJson('POST', '/api', [
            'type' => 'request',
            'meta' => ['id' => 'order'],
            'data' => ['action' => 'health.check', 'input' => []],
        ]);

        $this->assertSame(200, $envelope['status']);
        $this->assertSame('response', json_decode($envelope['body'], true)['type'], 'Api answered, not the fallback');

        $fallback = $client->send('GET', '/api');

        $this->assertSame(404, $fallback['status']);
        $this->assertSame(
            'NotFoundException',
            json_decode($fallback['body'], true)['name'],
            'the fallback does match /api — which is why it has to be tried last',
        );
    }

    private function reach(): HttpClient
    {
        try {
            return new HttpClient(getenv('GATEWAY_HOST') ?: 'gateway', (int) (getenv('GATEWAY_PORT') ?: 8080), 5.0);
        } catch (Throwable $error) {
            $this->markTestSkipped('Gateway is not reachable; run `make up`. '.$error->getMessage());
        }
    }
}
