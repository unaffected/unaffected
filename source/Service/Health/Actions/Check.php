<?php

declare(strict_types=1);

namespace Diagonal\Service\Health\Actions;

use Diagonal\Application\Context;
use Diagonal\Schema\Schema;
use Diagonal\Service\Action;

class Check extends Action
{
    public const string KEY = 'check';

    public const string NAME = 'Health: Check';

    public const string DESCRIPTION = 'Report that the platform is answering.';

    /** Liveness answers anyone. */
    protected function authenticate(Context $context): bool
    {
        return true;
    }

    public function verify(?Context $context = null): Schema
    {
        return Schema::text();
    }

    public function handle(Context $context): mixed
    {
        return 'ok';
    }
}
