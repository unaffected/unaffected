<?php

declare(strict_types=1);

namespace Diagonal\Tests\Application\Support;

use Closure;
use Diagonal\Application\Context;
use Diagonal\Service\Action;

/**
 * An action whose handler a test supplies. It admits anonymous callers, so a
 * test can dispatch it without standing up an identity first.
 */
class Configurable extends Action
{
    public const string KEY = 'create';

    public const string NAME = 'Create';

    public const string DESCRIPTION = 'A configurable action.';

    private Closure $handler;

    public function __construct(?Closure $handle = null)
    {
        $this->handler = $handle ?? static fn (Context $context): string => 'made';
    }

    protected function authenticate(Context $context): bool
    {
        return true;
    }

    public function handle(Context $context): mixed
    {
        return ($this->handler)($context);
    }
}
