<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server\Routes;

use Diagonal\Tests\Server\Support\WebSocketClient;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * The real thing: a socket client, the gateway, NATS, an engine. Skipped when
 * the stack is not up, so the unit suite stays runnable on its own.
 */
final class SocketLiveTest extends TestCase
{
    private WebSocketClient $client;

    protected function setUp(): void
    {
        $host = getenv('GATEWAY_HOST') ?: 'gateway';
        $port = (int) (getenv('GATEWAY_PORT') ?: 8080);

        try {
            $this->client = new WebSocketClient($host, $port, '/socket', 2.0);
        } catch (Throwable $error) {
            $this->markTestSkipped('Gateway is not reachable; run `make up`. '.$error->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }

    /** @return array<string,mixed> */
    private function call(string $type, string $action, array $input = [], string $id = 'live'): array
    {
        $this->client->sendJson([
            'type' => $type,
            'meta' => ['id' => $id],
            'data' => ['action' => $action, 'input' => $input],
        ]);

        $frame = $this->client->receiveJson(5.0);

        $this->assertIsArray($frame, 'no frame came back over the socket');

        return $frame;
    }

    public function test_a_socket_dispatch_reaches_an_engine_and_comes_back(): void
    {
        $frame = $this->call('request', 'health.check');

        $this->assertSame('response', $frame['type']);
        $this->assertSame('ok', $frame['data']['result']);
    }

    public function test_one_connection_carries_many_dispatches(): void
    {
        $this->assertSame('ok', $this->call('request', 'health.check', [], 'a')['data']['result']);
        $this->assertSame('ok', $this->call('request', 'health.check', [], 'b')['data']['result']);
        $this->assertSame('ok', $this->call('request', 'health.check', [], 'c')['data']['result']);
    }

    public function test_metadata_is_echoed_so_a_client_can_correlate_replies(): void
    {
        $this->assertSame(['id' => 'corr-1'], $this->call('request', 'health.check', [], 'corr-1')['meta']);
    }

    public function test_a_failure_on_the_engine_arrives_as_a_frame_carrying_its_type(): void
    {
        $frame = $this->call('request', 'health.nope');

        $this->assertFalse($frame['data']['ok']);
        $this->assertSame('NotFoundException', $frame['data']['error']['type']);
    }
}
