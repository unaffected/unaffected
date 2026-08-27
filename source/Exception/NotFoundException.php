<?php

declare(strict_types=1);

namespace Diagonal\Exception;

use RuntimeException;

/** Nothing is registered under the address a dispatch named. */
class NotFoundException extends RuntimeException
{
    public static function service(string $id): self
    {
        return new self("No service is registered as [{$id}].");
    }

    public static function action(string $service, string $action): self
    {
        return new self("Service [{$service}] has no action [{$action}].");
    }
}
