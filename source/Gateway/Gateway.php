<?php

declare(strict_types=1);

namespace Diagonal\Gateway;

use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Plugin;
use Throwable;

/**
 * The inbound edge. Turns a Request into a Context, runs it through the
 * application's hook pipeline, and returns a Response.
 */
class Gateway implements Plugin
{
    private Application $app;

    public function key(): string
    {
        return 'gateway';
    }

    public function dependencies(): iterable
    {
        return [];
    }

    public function install(Application $app): void
    {
        $this->app = $app;

        $app->set('gateway', $this);
    }

    public function queue(Request $request): void
    {
        $this->handle(new Request(
            $request->service,
            $request->action,
            $request->data,
            [...$request->parameters, 'queue' => true],
            $request->transport,
        ));
    }

    public function handle(Request $request): Response
    {
        try {
            $context = new Context(
                app: $this->app,
                service: $request->service,
                action: $request->action,
                parameters: $request->parameters,
                data: $request->data,
                transport: $request->transport,
            );

            return Response::of($this->app->run($context)->result);
        } catch (Throwable $error) {
            return Response::failed($error);
        }
    }
}
