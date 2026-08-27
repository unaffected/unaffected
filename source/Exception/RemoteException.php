<?php

declare(strict_types=1);

namespace Diagonal\Exception;

use RuntimeException;
use Throwable;

/** A failure the far side reported, carried back across the spine. */
class RemoteException extends RuntimeException
{
    /** @param list<array{path:string,rule:string,message:string}> $findings */
    public function __construct(
        string $message,
        public readonly string $type = '',
        public readonly array $findings = [],
    ) {
        parent::__construct($message);
    }

    /**
     * How a failure crosses the network. Structure the far side collected —
     * schema findings especially — has to survive the trip, or collecting it
     * was pointless.
     *
     * @return array<string,mixed>
     */
    public static function wire(Throwable $error): array
    {
        $wire = [
            'type' => $error::class,
            'message' => $error->getMessage(),
        ];

        $findings = Failure::from($error)->findings();

        if ($findings === []) {
            return $wire;
        }

        return [...$wire, 'findings' => $findings];
    }

    /** @param array<string,mixed> $error */
    public static function from(array $error): self
    {
        return new self(
            $error['message'] ?? 'The far side reported a failure.',
            $error['type'] ?? '',
            $error['findings'] ?? [],
        );
    }
}
