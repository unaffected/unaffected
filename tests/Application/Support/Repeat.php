<?php

declare(strict_types=1);

namespace Diagonal\Tests\Application\Support;

use Diagonal\Application\Context;
use Diagonal\Schema\Schema;
use Diagonal\Service\Action;

/**
 * An action that takes input, so a test can prove input travels. It lives here
 * rather than in the platform: the health service answers for liveness only.
 */
class Repeat extends Action
{
    public const string KEY = 'repeat';

    public const string NAME = 'Repeat';

    public const string DESCRIPTION = 'Echo a message back.';

    protected function authenticate(Context $context): bool
    {
        return true;
    }

    public function validate(?Context $context = null): Schema
    {
        return Schema::dictionary([
            'message' => Schema::text()->length(1, 256),
        ]);
    }

    public function verify(?Context $context = null): Schema
    {
        return Schema::text();
    }

    public function handle(Context $context): mixed
    {
        return 'repeat: '.$context->data['message'];
    }
}
