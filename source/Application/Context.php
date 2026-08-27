<?php

declare(strict_types=1);

namespace Diagonal\Application;

use Diagonal\Application\Hook\Type;
use Throwable;

/**
 * The single mutable value carried through a dispatch. Hooks read and write it
 * on the way in and on the way back out.
 */
class Context
{
    public function __construct(
        public readonly Application $app,
        public readonly string $service,
        public readonly string $action,
        public array $parameters = [],
        /** @var array<string,mixed> */
        public array $data = [],
        public mixed $result = null,
        public mixed $identity = null,
        public Transport $transport = Transport::Internal,
        public ?Throwable $error = null,
        public ?Type $type = null,
    ) {}

    public function path(): string
    {
        return "{$this->service}.{$this->action}";
    }
}
