<?php

declare(strict_types=1);

namespace Diagonal\Tests\Application;

use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Hook\Scope;
use Diagonal\Application\Hook\Type;
use Diagonal\Exception\NotFoundException;
use Diagonal\Service\Health\Health;
use Diagonal\Tests\Application\Support\Repeat;
use PHPUnit\Framework\TestCase;

final class DispatchTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = Application::make(key: 'test');
        $this->app->register(new Health()->add(new Repeat()));
    }

    public function test_it_dispatches_an_action_to_its_service(): void
    {
        $this->assertSame('ok', $this->app->dispatch('health', 'check'));
    }

    public function test_it_passes_data_to_the_action(): void
    {
        $this->assertSame('repeat: hello', $this->app->dispatch('health', 'repeat', ['message' => 'hello']));
    }

    public function test_it_rejects_an_unknown_service(): void
    {
        $this->expectException(NotFoundException::class);

        $this->app->dispatch('records', 'find');
    }

    public function test_it_rejects_an_unknown_action(): void
    {
        $this->expectException(NotFoundException::class);

        $this->app->dispatch('health', 'explode');
    }

    public function test_hooks_registered_on_the_application_wrap_the_dispatch(): void
    {
        $log = [];

        $this->app->hook(Scope::Application, Type::Before, function (Context $context) use (&$log): void {
            $log[] = 'before:'.$context->path();
        });

        $this->app->hook(Scope::Action, Type::After, function (Context $context) use (&$log): void {
            $log[] = 'after:'.$context->result;
        }, service: 'health', action: 'check');

        $this->app->dispatch('health', 'check');

        $this->assertSame(['before:health.check', 'after:ok'], $log);
    }

    public function test_a_hook_can_rewrite_the_result(): void
    {
        $this->app->hook(Scope::Network, Type::After, function (Context $context): void {
            $context->result = strtoupper((string) $context->result);
        });

        $this->assertSame('OK', $this->app->dispatch('health', 'check'));
    }

    public function test_a_hook_can_rewrite_the_data_before_the_action_sees_it(): void
    {
        $this->app->hook(Scope::Service, Type::Before, function (Context $context): void {
            $context->data['message'] = 'rewritten';
        }, service: 'health');

        $this->assertSame('repeat: rewritten', $this->app->dispatch('health', 'repeat', ['message' => 'hello']));
    }
}
