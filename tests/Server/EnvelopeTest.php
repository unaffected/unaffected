<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server;

use Diagonal\Gateway\Response;
use Diagonal\Server\Envelope;
use PHPUnit\Framework\TestCase;

final class EnvelopeTest extends TestCase
{
    private function raw(string $type = 'request'): string
    {
        return json_encode([
            'type' => $type,
            'meta' => ['id' => 'abc'],
            'data' => ['action' => 'security.user.create', 'input' => ['email' => 'a@b.com']],
        ]);
    }

    public function test_it_reads_an_envelope(): void
    {
        $envelope = Envelope::from($this->raw());

        $this->assertSame('request', $envelope->type);
        $this->assertSame('security.user.create', $envelope->action);
        $this->assertSame(['email' => 'a@b.com'], $envelope->input);
        $this->assertSame(['id' => 'abc'], $envelope->meta);
    }

    public function test_a_request_envelope_wants_an_answer_and_a_queue_envelope_does_not(): void
    {
        $this->assertTrue(Envelope::from($this->raw('request'))->wants());
        $this->assertFalse(Envelope::from($this->raw('queue'))->wants());
    }

    public function test_it_splits_a_dotted_action_into_service_and_action(): void
    {
        $request = Envelope::from($this->raw())->request();

        $this->assertSame('security.user', $request->service);
        $this->assertSame('create', $request->action);
    }

    public function test_it_rejects_a_message_that_is_not_json(): void
    {
        $this->assertNull(Envelope::from('{not json'));
    }

    public function test_it_rejects_a_message_naming_no_action(): void
    {
        $this->assertNull(Envelope::from('{"type":"request","data":{}}'));
    }

    public function test_it_rejects_an_unknown_envelope_type(): void
    {
        $this->assertNull(Envelope::from('{"type":"sneak","data":{"action":"a.b"}}'));
    }

    public function test_a_reply_carries_the_metadata_it_was_sent_with(): void
    {
        $reply = Envelope::from($this->raw())->reply(Response::of('created'));

        $this->assertSame('response', $reply['type']);
        $this->assertSame(['id' => 'abc'], $reply['meta']);
        $this->assertSame(['ok' => true, 'result' => 'created'], $reply['data']);
    }
}
