<?php

declare(strict_types=1);

namespace Diagonal\Exception;

use Diagonal\Schema\Report;
use RuntimeException;

/** A schema check failed. The report carries every finding the walk collected. */
abstract class SchemaException extends RuntimeException
{
    public function __construct(public readonly Report $report, ?string $action = null)
    {
        parent::__construct($action === null
            ? $report->message()
            : "{$action}: ".$report->message());
    }
}
