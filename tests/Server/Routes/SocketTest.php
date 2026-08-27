<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server\Routes;

use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Transport;
use Diagonal\Gateway\Gateway;
use Diagonal\Server\Routes\Socket;
use Diagonal\Service\Health\Health;
use Diagonal\Service\Service;
use Diagonal\Tests\Application\Support\Configurable;
use Diagonal\Tests\Application\Support\Repeat;
use PHPUnit\Framework\TestCase;

final class SocketTest extends TestCase
{
    private Application $app;

    private Socket $socket;

    protected function setUp(): void
    {
        $this->app = Application::make(new Gateway(), key: 'gateway')->register(new Health()->add(new Repeat()));
        $this->socket = new Socket($this->app->gateway);
    }

    private function message(string $type, string $action, array $input = [], array $meta = ['id' => 'm1']): string
    {
        return json_encode([
            'type' => $type,
            'meta' => $meta,
            'data' => ['action' => $action, 'input' => $input],
        ]);
    }

    public function test_it_speaks_the_same_envelope_the_api_door_does(): void
    {
        $frame = $this->socket->frame($this->message('request', 'health.repeat', ['message' => 'hi']));

        $this->assertSame('response', $frame['type']);
        $this->assertSame(['id' => 'm1'], $frame['meta']);
        $this->assertSame(['ok' => true, 'result' => 'repeat: hi'], $frame['data']);
    }

    public function test_a_dispatch_off_the_socket_is_marked_as_such_on_the_context(): void
    {
        $seen = null;

        $action = new Configurable(function (Context $context) use (&$seen): string {
            $seen = $context->transport;

            return 'done';
        });

        $this->app->register(new Service('probe', [$action]));

        $this->socket->frame($this->message('request', 'probe.create'));

        $this->assertSame(Transport::Socket, $seen);
    }

    public function test_an_in_process_call_is_marked_internal(): void
    {
        $seen = null;

        $action = new Configurable(function (Context $context) use (&$seen): string {
            $seen = $context->transport;

            return 'done';
        });

        $this->app->register(new Service('probe', [$action]));
        $this->app->dispatch('probe', 'create');

        $this->assertSame(Transport::Internal, $seen);
        $this->assertFalse($seen->external());
    }

    public function test_a_queue_envelope_is_acknowledged_without_a_result(): void
    {
        $frame = $this->socket->frame($this->message('queue', 'health.repeat', ['message' => 'later']));

        $this->assertSame(['ok' => true, 'result' => null], $frame['data']);
    }

    public function test_a_failure_comes_back_as_a_frame_not_an_exception(): void
    {
        $frame = $this->socket->frame($this->message('request', 'health.repeat', ['message' => '']));

        $this->assertFalse($frame['data']['ok']);
        $this->assertSame('ValidationException', $frame['data']['error']['type']);
        $this->assertSame('message', $frame['data']['error']['findings'][0]['path']);
    }

    public function test_a_message_that_is_not_an_envelope_is_rejected(): void
    {
        $frame = $this->socket->frame('{not json');

        $this->assertSame('BadEnvelope', $frame['data']['error']['type']);
    }

    public function test_an_address_naming_no_service_is_rejected(): void
    {
        $frame = $this->socket->frame($this->message('request', 'lonely'));

        $this->assertSame('BadEnvelope', $frame['data']['error']['type']);
        $this->assertStringContainsString('lonely', $frame['data']['error']['message']);
    }

    public function test_an_unknown_service_answers_a_frame_carrying_the_failure(): void
    {
        $frame = $this->socket->frame($this->message('request', 'records.find'));

        $this->assertFalse($frame['data']['ok']);
        $this->assertSame('NotFoundException', $frame['data']['error']['type']);
    }
}
