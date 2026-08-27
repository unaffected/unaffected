<?php

declare(strict_types=1);

namespace Diagonal\Server;

use Diagonal\Application\Application;
use Diagonal\Application\Plugin;
use Diagonal\Gateway\Gateway;
use Diagonal\Server\Routes\Api;
use Diagonal\Server\Routes\Rest;
use Diagonal\Server\Routes\Socket;
use Throwable;
use TrueAsync\HttpRequest;
use TrueAsync\HttpResponse;
use TrueAsync\HttpServer;
use TrueAsync\HttpServerConfig;

/**
 * The public edge. Owns sockets and protocol; the gateway owns what a request
 * means, and every service lives on an engine across the spine.
 *
 * HTTP is for the outside world only — nothing internal speaks it. Browsers
 * require TLS for HTTP/2, so the cleartext h2 listener is for traffic behind a
 * terminating proxy.
 */
class Server implements Plugin
{
    protected ?HttpServer $server = null;

    /** @var list<Route> Everything routes() declared, with the REST fallback last. */
    protected array $handlers;

    protected Socket $socket;

    public function __construct(
        protected readonly Gateway $gateway = new Gateway(),
        protected readonly ?HttpServerConfig $configuration = null,
    ) {
        $this->handlers = [...$this->routes(), new Rest()];
        $this->socket = new Socket($this->gateway);
    }

    /**
     * The routes this edge answers, tried in order. Override to add your own.
     * The REST fallback is appended after them because it matches any path
     * short enough to be a service address, and would shadow whatever followed.
     *
     * @return list<Route>
     */
    protected function routes(): array
    {
        return [new Api()];
    }

    public function key(): string
    {
        return 'server';
    }

    public function dependencies(): iterable
    {
        return [$this->gateway];
    }

    public function install(Application $app): void
    {
        $app->set('server', $this);
    }

    public static function configure(): HttpServerConfig
    {
        return new HttpServerConfig()
            ->addHttp1Listener('0.0.0.0', (int) (getenv('HTTP_PORT') ?: 8080))
            ->addHttp2Listener('0.0.0.0', (int) (getenv('HTTP2_PORT') ?: 8081), false);
    }

    public function server(): HttpServer
    {
        return $this->server ??= new HttpServer($this->configuration ?? self::configure())
            ->addHttpHandler($this->answer(...))
            ->addHttp2Handler($this->answer(...))
            ->addWebSocketHandler($this->socket->handle(...));
    }

    public function start(): bool
    {
        return $this->server()->start();
    }

    public function answer(HttpRequest $request, HttpResponse $response): void
    {
        foreach ($this->handlers as $route) {
            if (! $route->matches($request)) {
                continue;
            }

            try {
                $route->handle($request, $response, $this->gateway);
            } catch (Throwable $error) {
                // The gateway answers with a Response rather than throwing, so
                // reaching here means a route itself broke.
                $response->json(['ok' => false, 'error' => [
                    'type' => 'ServerError',
                    'message' => $error->getMessage(),
                ]], 500);
            }

            return;
        }

        $response->json(['ok' => false, 'error' => [
            'type' => 'NotFound',
            'message' => 'Nothing is served at that path.',
        ]], 404);
    }
}
