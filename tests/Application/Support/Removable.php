<?php

declare(strict_types=1);

namespace Diagonal\Tests\Application\Support;

use Diagonal\Application\Context;
use Diagonal\Service\Action;

class Removable extends Action
{
    public const string KEY = 'delete';

    public const string NAME = 'Delete';

    public const string DESCRIPTION = 'A second action, to prove scoping.';

    protected function authenticate(Context $context): bool
    {
        return true;
    }

    public function handle(Context $context): mixed
    {
        return 'deleted';
    }
}
