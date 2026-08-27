<?php

declare(strict_types=1);

namespace Diagonal\Application\Hook;

/**
 * Where a hook attaches. Scopes nest: Network is outermost and wraps transit,
 * Action is innermost and wraps a single action.
 */
enum Scope: string
{
    case Network = 'network';
    case Application = 'application';
    case Service = 'service';
    case Action = 'action';

    /** Lower runs further out. */
    public function depth(): int
    {
        return match ($this) {
            self::Network => 0,
            self::Application => 1,
            self::Service => 2,
            self::Action => 3,
        };
    }
}
