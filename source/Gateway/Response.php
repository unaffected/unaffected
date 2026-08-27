<?php

declare(strict_types=1);

namespace Diagonal\Gateway;

use Throwable;

/**
 * What the edge hands back. The gateway never throws at its boundary — a
 * failure travels as a value so every driver reports it the same way.
 */
class Response
{
    private function __construct(
        public readonly mixed $result = null,
        public readonly ?Throwable $error = null,
    ) {}

    public static function of(mixed $result): self
    {
        return new self($result);
    }

    public static function failed(Throwable $error): self
    {
        return new self(null, $error);
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}
