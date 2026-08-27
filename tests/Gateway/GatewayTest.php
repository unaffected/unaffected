<?php

declare(strict_types=1);

namespace Diagonal\Tests\Gateway;

use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Hook\Scope;
use Diagonal\Application\Hook\Type;
use Diagonal\Exception\NotFoundException;
use Diagonal\Gateway\Gateway;
use Diagonal\Gateway\Request;
use Diagonal\Service\Health\Health;
use Diagonal\Tests\Application\Support\Repeat;
use PHPUnit\Framework\TestCase;

final class GatewayTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = Application::make(new Gateway(), key: 'test');
        $this->app->register(new Health()->add(new Repeat()));
    }

    public function test_it_installs_itself_onto_the_application(): void
    {
        $this->assertTrue($this->app->installed('gateway'));
        $this->assertInstanceOf(Gateway::class, $this->app->gateway);
    }

    public function test_it_answers_a_request_with_the_action_result(): void
    {
        $response = $this->app->gateway->handle(new Request('health', 'check'));

        $this->assertTrue($response->succeeded());
        $this->assertSame('ok', $response->result);
    }

    public function test_it_carries_request_data_through_to_the_action(): void
    {
        $response = $this->app->gateway->handle(new Request('health', 'repeat', ['message' => 'hi']));

        $this->assertSame('repeat: hi', $response->result);
    }

    public function test_it_runs_the_full_hook_pipeline(): void
    {
        $log = [];

        foreach (Scope::cases() as $scope) {
            $this->app->hook($scope, Type::Before, function (Context $context) use (&$log, $scope): void {
                $log[] = $scope->value;
            });
        }

        $this->app->gateway->handle(new Request('health', 'check'));

        $this->assertSame(['network', 'application', 'service', 'action'], $log);
    }

    public function test_it_returns_a_failed_response_instead_of_throwing(): void
    {
        $response = $this->app->gateway->handle(new Request('records', 'find'));

        $this->assertFalse($response->succeeded());
        $this->assertInstanceOf(NotFoundException::class, $response->error);
        $this->assertNull($response->result);
    }

    public function test_a_network_hook_can_answer_without_reaching_the_service(): void
    {
        $this->app->hook(Scope::Network, Type::Around, function (Context $context, callable $next): Context {
            $context->result = 'short-circuited';

            return $context;
        });

        $response = $this->app->gateway->handle(new Request('health', 'check'));

        $this->assertSame('short-circuited', $response->result);
    }

    public function test_a_request_can_be_built_from_a_path(): void
    {
        $request = Request::path('health.repeat', ['message' => 'there']);

        $this->assertSame('health', $request->service);
        $this->assertSame('repeat', $request->action);
        $this->assertSame('repeat: there', $this->app->gateway->handle($request)->result);
    }
}
