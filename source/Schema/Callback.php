<?php

declare(strict_types=1);

namespace Diagonal\Schema;

use Closure;

/**
 * The general rule. Every named rule below is one of these with a fixed
 * predicate, so a caller can compose an ad-hoc rule the same way.
 */
class Callback implements Rule
{
    /** @param array<string,mixed> $parameters */
    public function __construct(
        private readonly string $name,
        private readonly Closure $predicate,
        private readonly string $message,
        private readonly array $parameters = [],
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function holds(mixed $value): bool
    {
        return ($this->predicate)($value);
    }

    public function message(): string
    {
        return $this->message;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
