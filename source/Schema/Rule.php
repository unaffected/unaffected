<?php

declare(strict_types=1);

namespace Diagonal\Schema;

/**
 * One fine-grained check. Rules compose: a schema node holds a list of them,
 * and combinators are themselves rules.
 */
interface Rule
{
    public function name(): string;

    public function holds(mixed $value): bool;

    public function message(): string;

    /** @return array<string,mixed> */
    public function parameters(): array;
}
