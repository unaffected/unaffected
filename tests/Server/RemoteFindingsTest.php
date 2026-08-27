<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server;

use Diagonal\Exception\RemoteException;
use Diagonal\Exception\ValidationException;
use Diagonal\Gateway\Response;
use Diagonal\Schema\Report;
use Diagonal\Schema\Schema;
use Diagonal\Server\Envelope;
use PHPUnit\Framework\TestCase;

/** A failure raised on an engine has to reach the caller intact. */
final class RemoteFindingsTest extends TestCase
{
    private function report(): Report
    {
        return Schema::dictionary([
            'email' => Schema::text()->email(),
            'age' => Schema::number()->positive(),
        ])->check(['email' => 'nope', 'age' => -1]);
    }

    public function test_a_validation_failure_serialises_its_findings_for_the_wire(): void
    {
        $wire = RemoteException::wire(new ValidationException($this->report(), 'security.user.create'));

        $this->assertSame(ValidationException::class, $wire['type']);
        $this->assertSame(
            [
                ['path' => 'email', 'rule' => 'email', 'message' => 'must be an email address'],
                ['path' => 'age', 'rule' => 'positive', 'message' => 'must be positive'],
            ],
            $wire['findings'],
        );
    }

    public function test_a_remote_failure_carries_the_findings_it_was_given(): void
    {
        $failure = RemoteException::from(RemoteException::wire(new ValidationException($this->report())));

        $this->assertSame('email', $failure->findings[0]['path']);
        $this->assertCount(2, $failure->findings);
    }

    public function test_the_caller_sees_the_type_that_actually_failed_not_the_transport(): void
    {
        $error = RemoteException::from(RemoteException::wire(new ValidationException($this->report())));

        $body = Envelope::body(Response::failed($error));

        $this->assertSame(422, Envelope::status($error));
        $this->assertSame('ValidationException', $body['error']['type']);
    }

    public function test_the_caller_sees_the_findings_the_engine_collected(): void
    {
        $body = Envelope::body(Response::failed(
            RemoteException::from(RemoteException::wire(new ValidationException($this->report()))),
        ));

        $this->assertSame(
            ['email', 'age'],
            array_column($body['error']['findings'], 'path'),
        );
    }
}
