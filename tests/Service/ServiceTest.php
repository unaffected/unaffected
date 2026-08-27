<?php

declare(strict_types=1);

namespace Diagonal\Tests\Service;

use Closure;
use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Hook\Type;
use Diagonal\Schema\Schema;
use Diagonal\Service\Action;
use Diagonal\Service\Service;
use Diagonal\Tests\Application\Support\Configurable;
use Diagonal\Tests\Application\Support\Removable;
use PHPUnit\Framework\TestCase;

final class ServiceTest extends TestCase
{
    /** @var list<string> */
    private array $log = [];

    private function service(string $id = 'security.user'): Service
    {
        $create = new Configurable(static fn (): string => 'created');

        return new Service($id, [$create, new Removable()]);
    }

    private function note(string $entry): Closure
    {
        return function (Context $context) use ($entry): void {
            $this->log[] = $entry;
        };
    }

    public function test_it_scopes_every_action_it_holds(): void
    {
        $service = $this->service();

        $this->assertSame('security.user.create', $service->action('create')->id());
        $this->assertSame('security.user.delete', $service->action('delete')->id());
    }

    public function test_an_action_added_later_is_scoped_too(): void
    {
        $service = $this->service()->add(new Removable());

        $this->assertSame('security.user.delete', $service->action('delete')->id());
    }

    public function test_a_service_hook_wraps_every_action_the_service_holds(): void
    {
        $service = $this->service()->hook(Type::Before, $this->note('service'));

        $app = Application::make(key: 'test')->register($service);

        $app->dispatch('security.user', 'create');
        $app->dispatch('security.user', 'delete');

        $this->assertSame(['service', 'service'], $this->log);
    }

    public function test_a_service_hook_named_for_an_action_wraps_only_that_action(): void
    {
        $service = $this->service()->hook(Type::Before, $this->note('create-only'), action: 'create');

        $app = Application::make(key: 'test')->register($service);

        $app->dispatch('security.user', 'delete');
        $this->assertSame([], $this->log);

        $app->dispatch('security.user', 'create');
        $this->assertSame(['create-only'], $this->log);
    }

    public function test_a_services_hooks_do_not_leak_into_another_service(): void
    {
        $secured = $this->service()->hook(Type::Before, $this->note('secured'));
        $other = $this->service('billing.invoice');

        $app = Application::make(key: 'test')->register($secured, $other);

        $app->dispatch('billing.invoice', 'create');

        $this->assertSame([], $this->log);
    }

    public function test_a_dotted_service_id_dispatches_end_to_end(): void
    {
        $create = new class extends Action
        {
            public const string KEY = 'create';

            protected function authenticate(Context $context): bool
            {
                return true;
            }

            public function validate(?Context $context = null): Schema
            {
                return Schema::dictionary(['email' => Schema::text()->email()]);
            }

            public function verify(?Context $context = null): Schema
            {
                return Schema::text();
            }

            public function handle(Context $context): mixed
            {
                return 'created '.$context->data['email'];
            }
        };

        $app = Application::make(key: 'test')->register(new Service('security.user', [$create]));

        $this->assertSame(
            'created a@b.com',
            $app->dispatch('security.user', 'create', ['email' => 'a@b.com']),
        );
    }
}
