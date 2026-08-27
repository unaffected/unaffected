<?php

declare(strict_types=1);

namespace Diagonal\Schema;

/**
 * What a schema node holds. The structural vocabulary every value in the
 * system is described with.
 */
enum Value: string
{
    case Null = 'null';
    case Toggle = 'toggle';
    case Number = 'number';
    case Text = 'text';
    case List = 'list';
    case Dictionary = 'dictionary';

    case Any = 'any';

    public function holds(mixed $value): bool
    {
        return match ($this) {
            self::Any => true,
            self::Null => $value === null,
            self::Toggle => is_bool($value),
            self::Number => is_int($value) || is_float($value),
            self::Text => is_string($value),
            self::List => is_array($value) && array_is_list($value),
            self::Dictionary => is_array($value) && ($value === [] || ! array_is_list($value)),
        };
    }
}
