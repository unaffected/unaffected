<?php

declare(strict_types=1);

namespace Diagonal\Schema;

/**
 * One problem, located. Findings accumulate — a failing check reports what it
 * found and lets the rest of the walk continue.
 */
class Finding
{
    /** @param array<string,mixed> $parameters */
    public function __construct(
        public readonly string $path,
        public readonly string $rule,
        public readonly string $message,
        public readonly array $parameters = [],
    ) {}

    /** @return array{path:string,rule:string,message:string} */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'rule' => $this->rule,
            'message' => $this->message,
        ];
    }

    public function __toString(): string
    {
        return $this->path === ''
            ? $this->message
            : "{$this->path}: {$this->message}";
    }
}
