<?php

declare(strict_types=1);

namespace Diagonal\Engine;

use Diagonal\Application\Application;
use Diagonal\Application\Plugin;
use Diagonal\Application\Transport;
use Diagonal\Exception\RemoteException;
use Diagonal\Network\Network;
use Diagonal\Network\Subject;
use Throwable;

/**
 * The execution runtime. Takes work off the network and runs it through the
 * same hook pipeline a local dispatch uses.
 */
class Engine implements Plugin
{
    public const GROUP = 'engine';

    private Application $app;

    public function __construct(
        private readonly Network $network = new Network(),
        private readonly string $prefix = Subject::PREFIX,
    ) {}

    public function key(): string
    {
        return 'engine';
    }

    public function dependencies(): iterable
    {
        return [$this->network];
    }

    public function install(Application $app): void
    {
        $this->app = $app;

        $app->set('engine', $this);
    }

    /** Start answering for every service this application has registered. */
    public function serve(?string $subject = null): void
    {
        $this->network->subscribe($subject ?? Subject::all($this->prefix), self::GROUP, $this->answer(...));
    }

    /** Deliver whatever has arrived. One call handles at most one message. */
    public function tick(?float $timeout = 0): void
    {
        $this->network->process($timeout);
    }

    /** Run until stopped. */
    public function run(?float $timeout = 1): void
    {
        while (true) {
            $this->tick($timeout);
        }
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function answer(array $payload, string $subject): array
    {
        try {
            [$service, $action] = Subject::parse($subject, $this->prefix);

            return [
                'ok' => true,
                'result' => $this->app->dispatch(
                    $service,
                    $action,
                    $payload['data'] ?? [],
                    $payload['parameters'] ?? [],
                    transport: Transport::tryFrom($payload['transport'] ?? '') ?? Transport::Internal,
                ),
            ];
        } catch (Throwable $error) {
            return ['ok' => false, 'error' => RemoteException::wire($error)];
        }
    }
}
