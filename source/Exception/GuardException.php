<?php

declare(strict_types=1);

namespace Diagonal\Exception;

use ReflectionClass;
use RuntimeException;

/** A guard refused the call. The message names the guard that refused it. */
abstract class GuardException extends RuntimeException
{
    public static function for(?string $action): static
    {
        $guard = new ReflectionClass(static::class)->getShortName();

        return new static($action === null
            ? "{$guard}."
            : "{$guard} for [{$action}].");
    }
}
