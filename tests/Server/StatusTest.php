<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server;

use Diagonal\Exception\AuthenticationException;
use Diagonal\Exception\AuthorizationException;
use Diagonal\Exception\NotFoundException;
use Diagonal\Exception\RemoteException;
use Diagonal\Exception\ValidationException;
use Diagonal\Exception\VerificationException;
use Diagonal\Schema\Report;
use Diagonal\Server\Envelope;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StatusTest extends TestCase
{
    public function test_an_unauthenticated_caller_is_401(): void
    {
        $this->assertSame(401, Envelope::status(AuthenticationException::for('a.b')));
    }

    public function test_an_unauthorized_caller_is_403(): void
    {
        $this->assertSame(403, Envelope::status(AuthorizationException::for('a.b')));
    }

    public function test_an_unknown_service_or_action_is_404(): void
    {
        $this->assertSame(404, Envelope::status(NotFoundException::service('records')));
        $this->assertSame(404, Envelope::status(NotFoundException::action('health', 'boom')));
    }

    public function test_invalid_input_is_422(): void
    {
        $this->assertSame(422, Envelope::status(new ValidationException(new Report())));
    }

    public function test_a_result_that_fails_verification_is_our_fault_not_theirs(): void
    {
        $this->assertSame(500, Envelope::status(new VerificationException(new Report())));
    }

    public function test_anything_else_is_500(): void
    {
        $this->assertSame(500, Envelope::status(new RuntimeException('boom')));
    }

    public function test_a_remote_failure_is_mapped_by_the_type_the_far_side_reported(): void
    {
        $this->assertSame(403, Envelope::status(RemoteException::from([
            'type' => AuthorizationException::class,
            'message' => 'nope',
        ])));
    }

    public function test_a_remote_failure_of_an_unknown_type_is_a_bad_gateway(): void
    {
        $this->assertSame(502, Envelope::status(RemoteException::from(['type' => 'Some\\Other', 'message' => 'nope'])));
    }
}
