<?php

declare(strict_types=1);

namespace Diagonal\Exception;

use Diagonal\Schema\Finding;
use Throwable;

/**
 * A failure, shaped for a caller. One place decides what a throwable is called,
 * what it means to an HTTP caller, and which findings travel with it, so every
 * edge reports the same failure the same way.
 */
final class Failure
{
    /** @param list<array{path:string,rule:string,message:string}> $findings */
    private function __construct(
        private readonly string $type,
        private readonly string $message,
        private readonly array $findings,
        private readonly bool $remote,
    ) {}

    public static function from(Throwable $error): self
    {
        if ($error instanceof RemoteException) {
            return new self($error->type, $error->getMessage(), $error->findings, true);
        }

        if ($error instanceof SchemaException) {
            return new self(
                $error::class,
                $error->getMessage(),
                array_map(static fn (Finding $finding): array => $finding->toArray(), $error->report->findings()),
                false,
            );
        }

        return new self($error::class, $error->getMessage(), [], false);
    }

    /** The class that failed, without its namespace. */
    public function name(): string
    {
        $position = strrpos($this->type, '\\');

        return $position === false ? $this->type : substr($this->type, $position + 1);
    }

    public function status(): int
    {
        return match ($this->type) {
            AuthenticationException::class => 401,
            AuthorizationException::class => 403,
            NotFoundException::class => 404,
            ValidationException::class => 422,
            default => $this->remote ? 502 : 500,
        };
    }

    /** @return list<array{path:string,rule:string,message:string}> */
    public function findings(): array
    {
        return $this->findings;
    }

    /**
     * The shape the envelope endpoints report a failure in.
     *
     * @return array<string,mixed>
     */
    public function envelope(): array
    {
        $body = [
            'type' => $this->name(),
            'message' => $this->message,
        ];

        if ($this->findings === []) {
            return $body;
        }

        return [...$body, 'findings' => $this->findings];
    }

    /**
     * The shape Feathers reports a failure in: a name you can branch on, a
     * message you can show, and the findings that produced it.
     *
     * @return array<string,mixed>
     */
    public function feathers(): array
    {
        $body = [
            'name' => $this->name(),
            'message' => $this->message,
            'code' => $this->status(),
        ];

        if ($this->findings === []) {
            return $body;
        }

        return [...$body, 'errors' => $this->findings];
    }
}
