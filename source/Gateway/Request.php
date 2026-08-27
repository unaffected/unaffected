<?php

declare(strict_types=1);

namespace Diagonal\Gateway;

use Diagonal\Application\Transport;
use InvalidArgumentException;

/**
 * Inbound work, normalised. Drivers (HTTP, CLI, NATS) build one of these and
 * hand it to the gateway; nothing below the edge knows where it came from.
 */
class Request
{
    /**
     * @param  array<string,mixed>  $data        the payload the action acts on
     * @param  array<string,mixed>  $parameters  everything about the call itself
     */
    public function __construct(
        public readonly string $service,
        public readonly string $action,
        public readonly array $data = [],
        public readonly array $parameters = [],
        public readonly Transport $transport = Transport::Internal,
    ) {}

    /**
     * @param  array<string,mixed>  $data
     * @param  array<string,mixed>  $parameters
     */
    public static function path(
        string $path,
        array $data = [],
        array $parameters = [],
        Transport $transport = Transport::Internal,
    ): self {
        $segments = explode('.', $path);

        if (count($segments) !== 2 || in_array('', $segments, true)) {
            throw new InvalidArgumentException("[{$path}] is not a <service>.<action> path.");
        }

        return new self($segments[0], $segments[1], $data, $parameters, $transport);
    }
}
