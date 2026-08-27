<?php

declare(strict_types=1);

namespace Diagonal\Tests\Engine;

use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Hook\Scope;
use Diagonal\Application\Hook\Type;
use Diagonal\Application\Transport;
use Diagonal\Engine\Engine;
use Diagonal\Network\Network;
use Diagonal\Network\Subject;
use Diagonal\Service\Health\Health;
use Diagonal\Service\Service;
use Diagonal\Tests\Application\Support\Configurable;
use Diagonal\Tests\Application\Support\Repeat;
use PHPUnit\Framework\TestCase;

/**
 * The spine: a caller on one connection, an engine on another, NATS between.
 */
final class EngineTest extends TestCase
{
    private Network $caller;

    private Network $network;

    private Engine $engine;

    private Application $app;

    /** Unique per test, so a running engine on the same cluster cannot answer for us. */
    private string $prefix;

    protected function setUp(): void
    {
        $this->network = new Network();

        if (! $this->network->reachable()) {
            $this->markTestSkipped('NATS is not reachable; run `docker compose up -d nats`.');
        }

        $this->caller = new Network();
        $this->prefix = 'test'.bin2hex(random_bytes(6));
        $this->engine = new Engine($this->network, $this->prefix);

        $this->app = Application::make($this->engine, key: 'engine');
        $this->app->register(new Health()->add(new Repeat()));

        $this->engine->serve();
    }

    protected function tearDown(): void
    {
        $this->network->disconnect();
        $this->caller->disconnect();
    }

    /** @return array<string,mixed> */
    private function call(string $service, string $action, array $data = []): array
    {
        $reply = null;

        $this->caller->request(Subject::for($service, $action, $this->prefix), ['data' => $data], function (array $answer) use (&$reply): void {
            $reply = $answer;
        });

        $this->engine->tick(2);
        $this->caller->process(2);

        $this->assertIsArray($reply, 'no reply came back over NATS');

        return $reply;
    }

    public function test_it_installs_itself_and_its_connection(): void
    {
        $this->assertTrue($this->app->installed('engine'));
        $this->assertTrue($this->app->installed('network'));
        $this->assertInstanceOf(Network::class, $this->app->network);
    }

    public function test_it_answers_a_request_that_arrived_over_the_network(): void
    {
        $reply = $this->call('health', 'repeat', ['message' => 'hi']);

        $this->assertTrue($reply['ok']);
        $this->assertSame('repeat: hi', $reply['result']);
    }

    public function test_it_runs_the_hook_pipeline_for_work_that_arrived_over_the_network(): void
    {
        $log = [];

        foreach (Scope::cases() as $scope) {
            $this->app->hook($scope, Type::Before, function (Context $context) use (&$log, $scope): void {
                $log[] = $scope->value;
            });
        }

        $this->call('health', 'check');

        $this->assertSame(['network', 'application', 'service', 'action'], $log);
    }

    public function test_the_transport_a_call_arrived_on_survives_the_spine(): void
    {
        $seen = null;

        $action = new Configurable(function (Context $context) use (&$seen): string {
            $seen = $context->transport;

            return 'done';
        });

        $this->app->register(new Service('probe', [$action]));

        $reply = null;

        $this->caller->request(
            Subject::for('probe', 'create', $this->prefix),
            ['data' => [], 'transport' => 'http'],
            function (array $answer) use (&$reply): void { $reply = $answer; },
        );

        $this->engine->tick(2);
        $this->caller->process(2);

        $this->assertTrue($reply['ok']);
        // The engine sees the transport the caller actually used, not the
        // spine that carried the work to it.
        $this->assertSame(Transport::Http, $seen);
    }

    public function test_it_reports_a_failure_back_to_the_caller(): void
    {
        $reply = $this->call('records', 'find');

        $this->assertFalse($reply['ok']);
        $this->assertSame('No service is registered as [records].', $reply['error']['message']);
    }
}
