<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server;

use Diagonal\Exception\AuthorizationException;
use Diagonal\Exception\ValidationException;
use Diagonal\Gateway\Response;
use Diagonal\Schema\Schema;
use Diagonal\Server\Envelope;
use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
    public function test_a_result_renders_as_ok(): void
    {
        $body = Envelope::body(Response::of(['id' => 'abc']));

        $this->assertSame(['ok' => true, 'result' => ['id' => 'abc']], $body);
    }

    public function test_a_failure_renders_its_type_and_message(): void
    {
        $error = AuthorizationException::for('security.user.create');

        $body = Envelope::body(Response::failed($error));

        $this->assertSame(403, Envelope::status($error));
        $this->assertFalse($body['ok']);
        $this->assertSame('AuthorizationException', $body['error']['type']);
    }

    public function test_a_validation_failure_carries_its_findings(): void
    {
        $report = Schema::dictionary(['email' => Schema::text()->email()])->check(['email' => 'nope']);

        $error = new ValidationException($report);

        $body = Envelope::body(Response::failed($error));

        $this->assertSame(422, Envelope::status($error));
        $this->assertSame([
            ['path' => 'email', 'rule' => 'email', 'message' => 'must be an email address'],
        ], $body['error']['findings']);
    }

    public function test_a_failure_without_a_report_carries_no_findings_key(): void
    {
        $body = Envelope::body(Response::failed(AuthorizationException::for('a.b')));

        $this->assertArrayNotHasKey('findings', $body['error']);
    }
}
