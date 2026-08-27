<?php

declare(strict_types=1);

namespace Diagonal\Application\Hook;

/**
 * What a hook does. Around is the primitive — Before, After and Error are
 * compiled into Around adapters before the pipeline is folded.
 */
enum Type: string
{
    case Around = 'around';
    case Before = 'before';
    case After = 'after';
    case Error = 'error';

    /**
     * Order within a scope, lower runs further out. Error wraps the rest of
     * its scope so it catches what the scopes inside it throw. Before and
     * After share a rank so they keep registration order relative to
     * each other.
     */
    public function depth(): int
    {
        return match ($this) {
            self::Error => 0,
            self::Around => 1,
            self::Before, self::After => 2,
        };
    }
}
