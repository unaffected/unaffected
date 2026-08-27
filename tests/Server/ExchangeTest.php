<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server;

use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Transport;
use Diagonal\Gateway\Gateway;
use Diagonal\Server\Exchange;
use Diagonal\Service\Health\Health;
use Diagonal\Service\Service;
use Diagonal\Tests\Application\Support\Configurable;
use Diagonal\Tests\Application\Support\Repeat;
use PHPUnit\Framework\TestCase;

/**
 * The single point both the /api door and the socket go through. Everything
 * here is plain PHP — no extension types — so the protocol is asserted without
 * a server, a socket or a spine.
 */
final class ExchangeTest extends TestCase
{
    private Application $app;

    private Exchange $exchange;

    protected function setUp(): void
    {
        $this->app = Application::make(new Gateway(), key: 'gateway')->register(new Health()->add(new Repeat()));
        $this->exchange = new Exchange($this->app->gateway);
    }

    /**
     * @param  array<string,mixed>  $input
     * @param  array<string,mixed>  $meta
     */
    private function envelope(string $type, string $action, array $input = [], array $meta = ['id' => 'm1']): string
    {
        return json_encode([
            'type' => $type,
            'meta' => $meta,
            'data' => ['action' => $action, 'input' => $input],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string,mixed>  $parameters
     * @return array{status:int, frame:array<string,mixed>}
     */
    private function answer(string $raw, array $parameters = [], Transport $transport = Transport::Http): array
    {
        return $this->exchange->answer($raw, $parameters, $transport);
    }

    // A request wants a result.

    public function test_a_request_envelope_is_answered_with_the_result(): void
    {
        $answered = $this->answer($this->envelope('request', 'health.check'));

        $this->assertSame(200, $answered['status']);
        $this->assertSame('response', $answered['frame']['type']);
        $this->assertSame(['ok' => true, 'result' => 'ok'], $answered['frame']['data']);
    }

    public function test_the_input_travels_to_the_action(): void
    {
        $answered = $this->answer($this->envelope('request', 'health.repeat', ['message' => 'hi']));

        $this->assertSame(200, $answered['status']);
        $this->assertSame('repeat: hi', $answered['frame']['data']['result']);
    }

    // A queue hands the work off.

    public function test_a_queue_envelope_is_accepted_and_acknowledged(): void
    {
        $answered = $this->answer($this->envelope('queue', 'health.repeat', ['message' => 'later']));

        $this->assertSame(202, $answered['status']);
        $this->assertSame(['ok' => true, 'result' => null], $answered['frame']['data']);
    }

    public function test_a_queued_dispatch_still_reaches_the_action(): void
    {
        $seen = null;

        $this->app->register(new Service('probe', [new Configurable(function (Context $context) use (&$seen): string {
            $seen = $context->parameters['queue'] ?? null;

            return 'done';
        })]));

        $this->answer($this->envelope('queue', 'probe.create'));

        $this->assertTrue($seen, 'the gateway marks a queued dispatch on the context');
    }

    // What is not an envelope.

    public function test_a_raw_string_that_does_not_decode_is_rejected(): void
    {
        $answered = $this->answer('{not json');

        $this->assertSame(400, $answered['status']);
        $this->assertSame('BadEnvelope', $answered['frame']['data']['error']['type']);
        $this->assertFalse($answered['frame']['data']['ok']);
    }

    public function test_an_envelope_with_no_action_is_rejected(): void
    {
        $answered = $this->answer(json_encode(['type' => 'request', 'data' => []], JSON_THROW_ON_ERROR));

        $this->assertSame(400, $answered['status']);
        $this->assertSame('BadEnvelope', $answered['frame']['data']['error']['type']);
    }

    public function test_an_action_that_is_not_a_service_action_address_is_rejected(): void
    {
        $answered = $this->answer($this->envelope('request', 'lonely'));

        $this->assertSame(400, $answered['status']);
        $this->assertSame('BadEnvelope', $answered['frame']['data']['error']['type']);
        $this->assertStringContainsString('lonely', $answered['frame']['data']['error']['message']);
    }

    // What the address named but the application does not hold.

    public function test_an_action_that_does_not_exist_answers_a_frame_carrying_the_failure(): void
    {
        $answered = $this->answer($this->envelope('request', 'health.nope'));

        $this->assertSame(404, $answered['status']);
        $this->assertFalse($answered['frame']['data']['ok']);
        $this->assertSame('NotFoundException', $answered['frame']['data']['error']['type']);
    }

    public function test_a_service_that_does_not_exist_answers_the_same_way(): void
    {
        $answered = $this->answer($this->envelope('request', 'records.find'));

        $this->assertSame(404, $answered['status']);
        $this->assertSame('NotFoundException', $answered['frame']['data']['error']['type']);
    }

    public function test_a_validation_failure_reports_the_findings_and_its_own_status(): void
    {
        $answered = $this->answer($this->envelope('request', 'health.repeat', ['message' => '']));

        $this->assertSame(422, $answered['status']);
        $this->assertSame('ValidationException', $answered['frame']['data']['error']['type']);
        $this->assertSame('message', $answered['frame']['data']['error']['findings'][0]['path']);
    }

    // Metadata is the caller's, and comes back untouched.

    public function test_metadata_is_echoed_on_a_result(): void
    {
        $answered = $this->answer($this->envelope('request', 'health.check', [], ['id' => 'corr-1']));

        $this->assertSame(['id' => 'corr-1'], $answered['frame']['meta']);
    }

    public function test_metadata_is_echoed_on_an_acknowledgement(): void
    {
        $answered = $this->answer($this->envelope('queue', 'health.check', [], ['id' => 'corr-2']));

        $this->assertSame(['id' => 'corr-2'], $answered['frame']['meta']);
    }

    public function test_metadata_is_echoed_on_a_bad_address(): void
    {
        $answered = $this->answer($this->envelope('request', 'lonely', [], ['id' => 'corr-3']));

        $this->assertSame(['id' => 'corr-3'], $answered['frame']['meta']);
    }

    public function test_metadata_is_echoed_on_a_failure(): void
    {
        $answered = $this->answer($this->envelope('request', 'health.nope', [], ['id' => 'corr-4']));

        $this->assertSame(['id' => 'corr-4'], $answered['frame']['meta']);
    }

    public function test_an_unreadable_envelope_still_carries_a_metadata_key_there_was_none_to_echo(): void
    {
        $answered = $this->answer('{not json');

        $this->assertArrayHasKey('meta', $answered['frame']);
        $this->assertSame([], $answered['frame']['meta']);
    }

    // What the edge knows about the call.

    public function test_the_transport_the_edge_names_reaches_the_context(): void
    {
        $seen = null;

        $this->app->register(new Service('probe', [new Configurable(function (Context $context) use (&$seen): string {
            $seen = $context->transport;

            return 'done';
        })]));

        $this->answer($this->envelope('request', 'probe.create'), transport: Transport::Socket);

        $this->assertSame(Transport::Socket, $seen);
    }

    public function test_the_parameters_the_edge_collected_reach_the_context(): void
    {
        $seen = [];

        $this->app->register(new Service('probe', [new Configurable(function (Context $context) use (&$seen): string {
            $seen = $context->parameters;

            return 'done';
        })]));

        $this->answer($this->envelope('request', 'probe.create'), ['remote' => '10.0.0.1']);

        $this->assertSame('10.0.0.1', $seen['remote']);
        $this->assertSame('m1', $seen['id'], 'the envelope meta rides the parameters too');
    }
}
