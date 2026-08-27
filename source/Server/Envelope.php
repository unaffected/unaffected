<?php

declare(strict_types=1);

namespace Diagonal\Server;

use Diagonal\Application\Transport;
use Diagonal\Exception\Failure;
use Diagonal\Gateway\Request;
use Diagonal\Gateway\Response;
use Throwable;

/**
 * The message shape every edge speaks — HTTP and WebSocket alike.
 *
 *   { "type": "request"|"queue",
 *     "meta": { "id": "…" },
 *     "data": { "action": "health.check", "input": { … } } }
 *
 * `request` waits for a result; `queue` hands the work off and returns.
 */
class Envelope
{
    public const string REQUEST = 'request';

    public const string QUEUE = 'queue';

    /**
     * @param  array<string,mixed>  $meta
     * @param  array<string,mixed>  $input
     */
    public function __construct(
        public readonly string $type,
        public readonly string $action,
        public readonly array $input = [],
        public readonly array $meta = [],
    ) {}

    /** @return self|null null when the message is not an envelope we understand */
    public static function from(string $raw): ?self
    {
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $type = $decoded['type'] ?? self::REQUEST;
        $action = $decoded['data']['action'] ?? null;

        if (! is_string($action) || $action === '') {
            return null;
        }

        if (! in_array($type, [self::REQUEST, self::QUEUE], true)) {
            return null;
        }

        return new self(
            type: $type,
            action: $action,
            input: $decoded['data']['input'] ?? [],
            meta: $decoded['meta'] ?? [],
        );
    }

    public function wants(): bool
    {
        return $this->type === self::REQUEST;
    }

    /** @param array<string,mixed> $parameters */
    public function request(array $parameters = [], Transport $transport = Transport::Internal): ?Request
    {
        $segments = explode('.', $this->action);

        if (count($segments) < 2 || in_array('', $segments, true)) {
            return null;
        }

        $action = array_pop($segments);

        return new Request(implode('.', $segments), $action, $this->input, [...$parameters, ...$this->meta], $transport);
    }

    /** @return array<string,mixed> */
    public function reply(Response $response): array
    {
        return [
            'type' => 'response',
            'meta' => $this->meta,
            'data' => self::body($response),
        ];
    }

    /**
     * The answer to an envelope that wanted no result.
     *
     * @return array<string,mixed>
     */
    public function acknowledge(): array
    {
        return [
            'type' => 'response',
            'meta' => $this->meta,
            'data' => ['ok' => true, 'result' => null],
        ];
    }

    /**
     * @param  array<string,mixed>  $meta
     * @return array<string,mixed>
     */
    public static function reject(string $message, array $meta = []): array
    {
        return [
            'type' => 'response',
            'meta' => $meta,
            'data' => ['ok' => false, 'error' => ['type' => 'BadEnvelope', 'message' => $message]],
        ];
    }

    /**
     * What the caller sees.
     *
     * @return array<string,mixed>
     */
    public static function body(Response $response): array
    {
        if ($response->succeeded()) {
            return ['ok' => true, 'result' => $response->result];
        }

        return ['ok' => false, 'error' => Failure::from($response->error)->envelope()];
    }

    /** What a failure means to an HTTP caller. */
    public static function status(Throwable $error): int
    {
        return Failure::from($error)->status();
    }
}
