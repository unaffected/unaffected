<?php

declare(strict_types=1);

namespace Diagonal\Network;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Message\Payload;
use Closure;
use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Plugin;
use Diagonal\Exception\RemoteException;
use Throwable;

/**
 * The connection to the network. Installs itself as `$app->network`.
 *
 * Payloads cross the wire as JSON and arrive at handlers already decoded, so
 * nothing above this class touches the transport's own types.
 */
class Network implements Plugin
{
    private ?Client $client = null;

    public function __construct(
        private readonly ?Configuration $configuration = null,
    ) {}

    public function key(): string
    {
        return 'network';
    }

    public function dependencies(): iterable
    {
        return [];
    }

    public function install(Application $app): void
    {
        $app->set('network', $this);
    }

    public function client(): Client
    {
        return $this->client ??= new Client($this->configuration ?? self::configure());
    }

    public static function configure(): Configuration
    {
        return new Configuration(
            host: getenv('NATS_HOST') ?: 'nats',
            port: (int) (getenv('NATS_PORT') ?: 4222),
        );
    }

    public function reachable(): bool
    {
        try {
            return $this->client()->ping();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Request/reply. Blocks until the answer arrives or the timeout passes.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function call(string $subject, array $payload, ?float $timeout = null): array
    {
        return self::normalise($this->client()->dispatch($subject, $payload, $timeout ?? 5.0));
    }

    /** @param array<string,mixed> $payload */
    public function publish(string $subject, array $payload): void
    {
        $this->client()->publish($subject, $payload);
    }

    /**
     * A Network-scope Around hook that answers a dispatch from an engine
     * instead of from this process. It never calls $next, so the local
     * registry is never consulted — which is exactly why Network is the
     * outermost scope.
     *
     * @return Closure(Context, callable): Context
     */
    public function forward(?float $timeout = null, string $prefix = Subject::PREFIX): Closure
    {
        return function (Context $context, callable $next) use ($timeout, $prefix): Context {
            $subject = Subject::for($context->service, $context->action, $prefix);

            $payload = [
                'data' => $context->data,
                'parameters' => $context->parameters,
                'transport' => $context->transport->value,
            ];

            if (($context->parameters['queue'] ?? false) === true) {
                $this->publish($subject, $payload);

                return $context;
            }

            $reply = $this->call(
                $subject,
                $payload,
                $timeout,
            );

            if (($reply['ok'] ?? false) !== true) {
                throw RemoteException::from($reply['error'] ?? []);
            }

            $context->result = $reply['result'] ?? null;

            return $context;
        };
    }

    /**
     * Send a request and hand the decoded answer to $onReply. The answer only
     * arrives once this connection is processed.
     *
     * @param  array<string,mixed>  $payload
     * @param  callable(array<string,mixed>): void  $onReply
     */
    public function request(string $subject, array $payload, callable $onReply): void
    {
        $this->client()->request($subject, $payload, static function (Payload $answer) use ($onReply): void {
            $onReply(self::normalise($answer));
        });
    }

    /**
     * Subscribe a queue group. Whatever the handler returns is sent back to
     * the requester.
     *
     * @param  callable(array<string,mixed>, string): mixed  $handler
     */
    public function subscribe(string $subject, string $group, callable $handler): void
    {
        $this->client()->subscribeQueue($subject, $group, static function (Payload $payload) use ($handler): mixed {
            return $handler(self::normalise($payload), (string) $payload->subject);
        });
    }

    /** Pump the connection: deliver whatever has arrived. */
    public function process(null|int|float $timeout = 0): void
    {
        $this->client()->process($timeout);
    }

    public function disconnect(): void
    {
        $this->client?->disconnect();
        $this->client = null;
    }

    /** @return array<string,mixed> */
    private static function normalise(mixed $answer): array
    {
        if ($answer instanceof Payload) {
            $answer = $answer->body;
        }

        if (is_string($answer)) {
            $answer = json_decode($answer, true);
        }

        return is_array($answer) ? $answer : [];
    }
}
