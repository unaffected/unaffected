<?php

declare(strict_types=1);

namespace Diagonal\Service\Health;

use Diagonal\Service\Health\Actions\Check;
use Diagonal\Service\Service;

/** The platform answering for itself. */
class Health extends Service
{
    public function __construct()
    {
        parent::__construct('health', [new Check()], description: 'Platform liveness.');
    }
}
