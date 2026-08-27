<?php

declare(strict_types=1);

namespace Diagonal\Application\Hook;

use Closure;

/**
 * A registration: what to run, where it attaches, and what it applies to.
 *
 * An Around handler is `fn(Context $context, callable $next): Context`.
 * Every other type is `fn(Context $context): void`.
 */
class Hook
{
    public function __construct(
        public readonly Scope $scope,
        public readonly Type $type,
        public readonly Closure $handler,
        public readonly ?string $service = null,
        public readonly ?string $action = null,
    ) {}

    /** A copy bound to a service, for hooks declared before their service id is known. */
    public function scopedTo(string $service): self
    {
        return new self($this->scope, $this->type, $this->handler, $service, $this->action);
    }

    /** Does this hook apply to the given dispatch? */
    public function matches(string $service, string $action): bool
    {
        if ($this->service !== null && $this->service !== $service) {
            return false;
        }

        return $this->action === null || $this->action === $action;
    }
}
