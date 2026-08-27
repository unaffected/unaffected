<?php

declare(strict_types=1);

namespace Diagonal\Tests\Network;

use Diagonal\Network\Subject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SubjectTest extends TestCase
{
    public function test_it_builds_a_subject_from_a_service_and_action(): void
    {
        $this->assertSame('service.health.check', Subject::for('health', 'check'));
    }

    public function test_it_exposes_a_wildcard_covering_every_service(): void
    {
        $this->assertSame('service.>', Subject::all());
    }

    public function test_it_parses_a_subject_back_into_a_service_and_action(): void
    {
        $this->assertSame(['health', 'check'], Subject::parse('service.health.check'));
    }

    public function test_a_dotted_service_id_round_trips(): void
    {
        $subject = Subject::for('security.user', 'create');

        $this->assertSame('service.security.user.create', $subject);
        $this->assertSame(['security.user', 'create'], Subject::parse($subject));
    }

    public function test_it_rejects_a_subject_outside_the_service_prefix(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Subject::parse('other.health.check');
    }

    public function test_it_rejects_a_subject_missing_an_action(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Subject::parse('service.health');
    }
}
