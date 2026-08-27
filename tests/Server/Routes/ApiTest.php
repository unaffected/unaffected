<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server\Routes;

use Diagonal\Tests\Server\Support\HttpClient;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * The envelope door. Its own logic is a path check, a verb check and the status
 * it puts on what Exchange answered — and both of its arguments are final
 * internal extension types, so it is exercised over real HTTP. The protocol it
 * carries is asserted without a server in ExchangeTest.
 */
final class ApiTest extends TestCase
{
    private static bool $warm = false;

    private HttpClient $client;

    protected function setUp(): void
    {
        $host = getenv('GATEWAY_HOST') ?: 'gateway';
        $port = (int) (getenv('GATEWAY_PORT') ?: 8080);

        try {
            $this->client = new HttpClient($host, $port, 5.0);
        } catch (Throwable $error) {
            $this->markTestSkipped('Gateway is not reachable; run `make up`. '.$error->getMessage());
        }

        if (! self::$warm) {
            self::$warm = true;

            try {
                $this->client->send('GET', '/health/check');
            } catch (Throwable) {
                // The first dispatch after an idle spell can time out on the
                // spine; spend it here so no assertion wears it.
            }
        }
    }

    /**
     * @param  array<string,mixed>  $input
     * @return array{status:int, headers:array<string,string>, body:string}
     */
    private function post(string $type, string $action, array $input = [], string $id = 'api-1'): array
    {
        return $this->client->sendJson('POST', '/api', [
            'type' => $type,
            'meta' => ['id' => $id],
            'data' => ['action' => $action, 'input' => $input],
        ]);
    }

    public function test_a_request_envelope_reaches_an_engine_and_comes_back(): void
    {
        $answer = $this->post('request', 'health.check');

        $body = json_decode($answer['body'], true);

        $this->assertSame(200, $answer['status']);
        $this->assertSame('response', $body['type']);
        $this->assertSame(['ok' => true, 'result' => 'ok'], $body['data']);
    }

    public function test_metadata_is_echoed_so_a_client_can_correlate_replies(): void
    {
        $answer = $this->post('request', 'health.check', id: 'corr-9');

        $this->assertSame(['id' => 'corr-9'], json_decode($answer['body'], true)['meta']);
    }

    public function test_a_queue_envelope_is_accepted_rather_than_answered(): void
    {
        $answer = $this->post('queue', 'health.check');

        $this->assertSame(202, $answer['status']);
        $this->assertSame(['ok' => true, 'result' => null], json_decode($answer['body'], true)['data']);
    }

    public function test_a_failure_arrives_as_a_frame_carrying_its_type(): void
    {
        $answer = $this->post('request', 'health.nope');

        $body = json_decode($answer['body'], true);

        $this->assertSame(404, $answer['status']);
        $this->assertFalse($body['data']['ok']);
        $this->assertSame('NotFoundException', $body['data']['error']['type']);
    }

    public function test_a_body_that_is_not_an_envelope_is_refused(): void
    {
        $answer = $this->client->send('POST', '/api', 'garbage', ['Content-Type' => 'application/json']);

        $this->assertSame(400, $answer['status']);
        $this->assertSame('BadEnvelope', json_decode($answer['body'], true)['data']['error']['type']);
    }

    public function test_the_answer_is_declared_as_json(): void
    {
        $answer = $this->post('request', 'health.check');

        $this->assertStringContainsString('application/json', $answer['headers']['content-type'] ?? '');
    }
}
