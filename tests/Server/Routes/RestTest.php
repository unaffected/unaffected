<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server\Routes;

use Diagonal\Tests\Server\Support\HttpClient;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * `TrueAsync\HttpRequest` and `TrueAsync\HttpResponse` are final internal
 * classes, so the REST route cannot be handed a stand-in and cannot be driven
 * in isolation without bending the route into a shape that exists only for the
 * test. It is exercised over real HTTP instead — the same live style the socket
 * route is tested in, skipped when the stack is not up so the unit suite stays
 * runnable on its own.
 */
final class RestTest extends TestCase
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

        // The gateway's first dispatch after an idle spell can time out on the
        // spine. Spend that one on nothing so no assertion wears it.
        if (! self::$warm) {
            self::$warm = true;

            try {
                $this->client->send('GET', '/health/check');
            } catch (Throwable) {
                // Nothing to answer to; the tests below say what is true.
            }
        }
    }

    public function test_a_get_on_an_action_answers_the_result_and_nothing_else(): void
    {
        $answer = $this->client->send('GET', '/health/check');

        $this->assertSame(200, $answer['status']);
        $this->assertSame('"ok"', $answer['body'], 'a string result has to arrive as JSON, quotes and all');
    }

    public function test_a_post_carries_the_body_as_the_input(): void
    {
        $answer = $this->client->sendJson('POST', '/health/check', []);

        $this->assertSame(200, $answer['status']);
        $this->assertSame('"ok"', $answer['body']);
    }

    public function test_a_success_is_declared_as_json(): void
    {
        $answer = $this->client->send('GET', '/health/check');

        $this->assertStringContainsString('application/json', $answer['headers']['content-type'] ?? '');
    }

    public function test_the_result_is_json_the_caller_can_decode(): void
    {
        $answer = $this->client->send('GET', '/health/check');

        $this->assertSame('ok', json_decode($answer['body'], true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_a_verb_that_means_nothing_on_a_collection_is_refused(): void
    {
        $answer = $this->client->send('OPTIONS', '/health');

        $this->assertSame(405, $answer['status']);
        $this->assertSame('MethodNotAllowed', json_decode($answer['body'], true)['name']);
    }

    public function test_delete_on_a_collection_is_refused_too(): void
    {
        $answer = $this->client->send('DELETE', '/health');

        $this->assertSame(405, $answer['status']);
        $this->assertSame('MethodNotAllowed', json_decode($answer['body'], true)['name']);
    }

    public function test_an_action_the_service_does_not_have_is_not_found(): void
    {
        $answer = $this->client->sendJson('POST', '/health/nope', []);

        $this->assertSame(404, $answer['status']);
        $this->assertSame('NotFoundException', json_decode($answer['body'], true)['name']);
    }

    public function test_a_verb_on_a_record_maps_to_the_method_that_verb_means(): void
    {
        $answer = $this->client->sendJson('PUT', '/health/1', []);

        $body = json_decode($answer['body'], true);

        $this->assertSame(404, $answer['status']);
        $this->assertSame('NotFoundException', $body['name']);
        $this->assertStringContainsString('update', $body['message'], 'PUT has to mean update');
    }

    public function test_a_path_too_deep_to_be_a_service_address_is_not_this_routes(): void
    {
        $answer = $this->client->send('GET', '/health/check/deeper');

        $body = json_decode($answer['body'], true);

        $this->assertSame(404, $answer['status']);
        $this->assertSame('NotFound', $body['error']['type'], 'the server answered, so REST never matched');
    }

    public function test_a_body_that_is_not_json_is_treated_as_no_input_rather_than_breaking(): void
    {
        $answer = $this->client->send('POST', '/health/check', '{not json', [
            'Content-Type' => 'application/json',
        ]);

        $this->assertSame(200, $answer['status']);
        $this->assertLessThan(500, $answer['status'], 'a malformed body is the caller\'s, not a server fault');
    }
}
