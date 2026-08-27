<?php

declare(strict_types=1);

namespace Diagonal\Tests\Application\Hook;

use Closure;
use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Hook\Hook;
use Diagonal\Application\Hook\Hooks;
use Diagonal\Application\Hook\Scope;
use Diagonal\Application\Hook\Type;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HooksTest extends TestCase
{
    /** @var list<string> */
    private array $log = [];

    private Application $app;

    protected function setUp(): void
    {
        $this->log = [];
        $this->app = Application::make(key: 'test');
    }

    private function context(): Context
    {
        return new Context($this->app, 'health', 'check');
    }

    private function terminal(): Closure
    {
        return function (Context $context): Context {
            $this->log[] = 'action';
            $context->result = 'ok';

            return $context;
        };
    }

    private function note(string $entry): Closure
    {
        return function (Context $context) use ($entry): void {
            $this->log[] = $entry;
        };
    }

    public function test_scopes_nest_network_outermost_and_action_innermost(): void
    {
        $hooks = new Hooks();

        foreach (Scope::cases() as $scope) {
            $hooks->add(new Hook($scope, Type::Before, $this->note("{$scope->value}.before")));
            $hooks->add(new Hook($scope, Type::After, $this->note("{$scope->value}.after")));
        }

        $context = $hooks->pipe($this->context(), $this->terminal());

        $this->assertSame([
            'network.before',
            'application.before',
            'service.before',
            'action.before',
            'action',
            'action.after',
            'service.after',
            'application.after',
            'network.after',
        ], $this->log);

        $this->assertSame('ok', $context->result);
    }

    public function test_an_around_hook_spans_both_sides_of_the_action(): void
    {
        $hooks = new Hooks();

        $hooks->add(new Hook(Scope::Application, Type::Around, function (Context $context, callable $next): Context {
            $this->log[] = 'in';
            $context = $next($context);
            $this->log[] = 'out';

            return $context;
        }));

        $hooks->pipe($this->context(), $this->terminal());

        $this->assertSame(['in', 'action', 'out'], $this->log);
    }

    public function test_a_hook_short_circuits_by_not_calling_next(): void
    {
        $hooks = new Hooks();

        $hooks->add(new Hook(Scope::Network, Type::Around, function (Context $context, callable $next): Context {
            $context->result = 'cached';

            return $context;
        }));

        $context = $hooks->pipe($this->context(), $this->terminal());

        $this->assertSame([], $this->log);
        $this->assertSame('cached', $context->result);
    }

    public function test_an_error_hook_catches_what_the_scopes_inside_it_throw(): void
    {
        $hooks = new Hooks();

        $hooks->add(new Hook(Scope::Network, Type::Error, function (Context $context): void {
            $this->log[] = 'caught:'.$context->error?->getMessage();
            $context->result = 'recovered';
        }));

        $hooks->add(new Hook(Scope::Action, Type::Before, function (Context $context): void {
            throw new RuntimeException('boom');
        }));

        $context = $hooks->pipe($this->context(), $this->terminal());

        $this->assertSame(['caught:boom'], $this->log);
        $this->assertSame('recovered', $context->result);
    }

    public function test_service_hooks_only_run_for_their_own_service(): void
    {
        $hooks = new Hooks();

        $hooks->add(new Hook(Scope::Service, Type::Before, $this->note('health'), service: 'health'));
        $hooks->add(new Hook(Scope::Service, Type::Before, $this->note('records'), service: 'records'));

        $hooks->pipe($this->context(), $this->terminal());

        $this->assertSame(['health', 'action'], $this->log);
    }

    public function test_action_hooks_only_run_for_their_own_action(): void
    {
        $hooks = new Hooks();

        $hooks->add(new Hook(Scope::Action, Type::Before, $this->note('check'), service: 'health', action: 'check'));
        $hooks->add(new Hook(Scope::Action, Type::Before, $this->note('repeat'), service: 'health', action: 'repeat'));

        $hooks->pipe($this->context(), $this->terminal());

        $this->assertSame(['check', 'action'], $this->log);
    }

    public function test_the_context_reports_which_hook_type_is_running(): void
    {
        $hooks = new Hooks();
        $seen = [];

        $hooks->add(new Hook(Scope::Application, Type::Before, function (Context $context) use (&$seen): void {
            $seen[] = $context->type;
        }));
        $hooks->add(new Hook(Scope::Application, Type::After, function (Context $context) use (&$seen): void {
            $seen[] = $context->type;
        }));

        $hooks->pipe($this->context(), $this->terminal());

        $this->assertSame([Type::Before, Type::After], $seen);
    }

    public function test_registering_a_hook_invalidates_a_compiled_pipeline(): void
    {
        $hooks = new Hooks();

        $hooks->pipe($this->context(), $this->terminal());

        $hooks->add(new Hook(Scope::Application, Type::Before, $this->note('late')));

        $hooks->pipe($this->context(), $this->terminal());

        $this->assertSame(['action', 'late', 'action'], $this->log);
    }
}
