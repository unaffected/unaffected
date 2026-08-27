<?php

declare(strict_types=1);

namespace Diagonal\Tests\Service;

use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Exception\AuthenticationException;
use Diagonal\Exception\AuthorizationException;
use Diagonal\Exception\ValidationException;
use Diagonal\Exception\VerificationException;
use Diagonal\Schema\Schema;
use Diagonal\Service\Action;
use Diagonal\Service\Service;
use Diagonal\Tests\Application\Support\Configurable;
use PHPUnit\Framework\TestCase;

final class ActionTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = Application::make(key: 'test');
    }

    private function context(array $data = [], mixed $identity = null): Context
    {
        return new Context($this->app, 'security.user', 'create', data: $data, identity: $identity);
    }

    // Identity

    public function test_an_action_scoped_to_a_service_takes_the_services_id_as_its_prefix(): void
    {
        $service = new Service('security.user', [new Configurable()]);

        $this->assertSame('security.user.create', $service->action('create')->id());
    }

    public function test_an_action_has_no_id_until_a_service_scopes_it(): void
    {
        $this->assertNull(new Configurable()->id());
    }

    // authenticate

    public function test_authenticate_demands_an_identity_by_default(): void
    {
        $action = new class extends Action
        {
            public function handle(Context $context): mixed
            {
                return 'made';
            }
        };

        $this->expectException(AuthenticationException::class);

        $action->execute($this->context());
    }

    public function test_the_default_authenticate_passes_when_an_identity_is_present(): void
    {
        $action = new class extends Action
        {
            public function handle(Context $context): mixed
            {
                return 'made';
            }
        };

        $this->assertSame('made', $action->execute($this->context(identity: 'user-1')));
    }

    public function test_an_action_may_override_authenticate_to_allow_an_anonymous_caller(): void
    {
        $action = new Configurable();

        $this->assertSame('made', $action->execute($this->context()));
    }

    public function test_authenticate_may_answer_from_the_context(): void
    {
        $action = new class extends Action
        {
            protected function authenticate(Context $context): bool
            {
                return $context->parameters['token'] === 'secret';
            }

            public function handle(Context $context): mixed
            {
                return 'made';
            }
        };

        $context = $this->context();
        $context->parameters['token'] = 'secret';

        $this->assertSame('made', $action->execute($context));
    }

    // authorize

    public function test_authorize_returning_false_denies_everyone(): void
    {
        $action = new class extends Action
        {
            protected function authenticate(Context $context): bool
            {
                return true;
            }

            protected function authorize(Context $context): bool
            {
                return false;
            }

            public function handle(Context $context): mixed
            {
                return 'made';
            }
        };

        $this->expectException(AuthorizationException::class);

        $action->execute($this->context());
    }

    public function test_authorize_may_answer_from_the_context(): void
    {
        $action = new class extends Action
        {
            protected function authenticate(Context $context): bool
            {
                return true;
            }

            protected function authorize(Context $context): bool
            {
                return $context->data['owner'] === 'me';
            }

            public function handle(Context $context): mixed
            {
                return 'made';
            }
        };

        $this->expectException(AuthorizationException::class);

        $action->execute($this->context(['owner' => 'someone else']));
    }

    public function test_authentication_is_checked_before_authorization(): void
    {
        $action = new class extends Action
        {
            protected function authorize(Context $context): bool
            {
                return false;
            }

            public function handle(Context $context): mixed
            {
                return 'made';
            }
        };

        $this->expectException(AuthenticationException::class);

        $action->execute($this->context());
    }

    // validate

    public function test_validate_returns_the_schema_the_input_must_satisfy(): void
    {
        $action = new class extends Action
        {
            protected function authenticate(Context $context): bool
            {
                return true;
            }

            public function validate(?Context $context = null): Schema
            {
                return Schema::dictionary(['email' => Schema::text()->email()]);
            }

            public function handle(Context $context): mixed
            {
                return $context->data['email'];
            }
        };

        $this->assertSame('a@b.com', $action->execute($this->context(['email' => 'a@b.com'])));
    }

    public function test_input_that_breaks_the_schema_is_rejected_before_the_handler_runs(): void
    {
        $action = new class extends Action
        {
            public bool $ran = false;

            protected function authenticate(Context $context): bool
            {
                return true;
            }

            public function validate(?Context $context = null): Schema
            {
                return Schema::dictionary(['email' => Schema::text()->email()]);
            }

            public function handle(Context $context): mixed
            {
                $this->ran = true;

                return null;
            }
        };

        try {
            $action->execute($this->context(['email' => 'nope']));
            $this->fail('expected a ValidationException');
        } catch (ValidationException $failure) {
            $this->assertSame('email', $failure->report->findings()[0]->path);
        }

        $this->assertFalse($action->ran, 'the handler ran despite invalid input');
    }

    public function test_validate_can_shape_the_schema_from_the_context(): void
    {
        $action = new class extends Action
        {
            protected function authenticate(Context $context): bool
            {
                return true;
            }

            public function validate(?Context $context = null): Schema
            {
                return $context?->parameters['strict']
                    ? Schema::dictionary(['email' => Schema::text()->email()])
                    : Schema::any();
            }

            public function handle(Context $context): mixed
            {
                return 'made';
            }
        };

        $lenient = $this->context(['email' => 'nope']);
        $lenient->parameters['strict'] = false;

        $this->assertSame('made', $action->execute($lenient));
    }

    // verify

    public function test_verify_returns_the_schema_the_result_must_satisfy(): void
    {
        $action = new class extends Action
        {
            protected function authenticate(Context $context): bool
            {
                return true;
            }

            public function verify(?Context $context = null): Schema
            {
                return Schema::dictionary(['id' => Schema::text()->uuid()]);
            }

            public function handle(Context $context): mixed
            {
                return ['id' => 'not-a-uuid'];
            }
        };

        $this->expectException(VerificationException::class);

        $action->execute($this->context());
    }

    public function test_a_result_matching_the_verify_schema_passes_through(): void
    {
        $action = new class extends Action
        {
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
        };

        $this->assertSame('ok', $action->execute($this->context()));
    }

    public function test_guards_run_in_order_authenticate_authorize_validate_handle_verify(): void
    {
        $action = new class extends Action
        {
            /** @var list<string> */
            public array $log = [];

            protected function authenticate(Context $context): bool
            {
                $this->log[] = 'authenticate';

                return true;
            }

            protected function authorize(Context $context): bool
            {
                $this->log[] = 'authorize';

                return true;
            }

            public function validate(?Context $context = null): Schema
            {
                $this->log[] = 'validate';

                return Schema::any();
            }

            public function verify(?Context $context = null): Schema
            {
                $this->log[] = 'verify';

                return Schema::any();
            }

            public function handle(Context $context): mixed
            {
                $this->log[] = 'handle';

                return 'ok';
            }
        };

        $action->execute($this->context());

        $this->assertSame(['authenticate', 'authorize', 'validate', 'handle', 'verify'], $action->log);
    }
}
