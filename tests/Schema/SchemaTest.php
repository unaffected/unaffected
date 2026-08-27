<?php

declare(strict_types=1);

namespace Diagonal\Tests\Schema;

use Diagonal\Schema\Finding;
use Diagonal\Schema\Schema;
use Diagonal\Schema\Value;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    // Kinds

    public function test_a_text_schema_accepts_text(): void
    {
        $this->assertTrue(Schema::text()->isValid('hello'));
        $this->assertFalse(Schema::text()->isValid(42));
    }

    public function test_a_number_schema_accepts_numbers(): void
    {
        $this->assertTrue(Schema::number()->isValid(42));
        $this->assertTrue(Schema::number()->isValid(4.2));
        $this->assertFalse(Schema::number()->isValid('42'));
    }

    public function test_a_toggle_schema_accepts_booleans(): void
    {
        $this->assertTrue(Schema::toggle()->isValid(true));
        $this->assertFalse(Schema::toggle()->isValid(1));
    }

    public function test_a_null_schema_accepts_only_null(): void
    {
        $this->assertTrue(Schema::null()->isValid(null));
        $this->assertFalse(Schema::null()->isValid(''));
    }

    public function test_an_any_schema_accepts_anything(): void
    {
        $this->assertTrue(Schema::any()->isValid('hello'));
        $this->assertTrue(Schema::any()->isValid(null));
        $this->assertTrue(Schema::any()->isValid([1, 2]));
    }

    // Composition

    public function test_rules_chain_and_all_must_hold(): void
    {
        $port = Schema::number()->integer()->between(1, 65535);

        $this->assertTrue($port->isValid(4222));
        $this->assertFalse($port->isValid(0));
        $this->assertFalse($port->isValid(4222.5));
    }

    public function test_a_chain_is_immutable_so_it_can_be_reused(): void
    {
        $text = Schema::text();
        $short = $text->length(1, 3);

        $this->assertTrue($text->isValid('longer'));
        $this->assertFalse($short->isValid('longer'));
    }

    public function test_a_list_checks_every_item_against_its_item_schema(): void
    {
        $tags = Schema::list(Schema::text()->length(1, 5));

        $this->assertTrue($tags->isValid(['php', 'nats']));
        $this->assertFalse($tags->isValid(['php', 'far-too-long']));
        $this->assertFalse($tags->isValid('php'));
    }

    public function test_a_dictionary_checks_each_property_against_its_schema(): void
    {
        $user = Schema::dictionary([
            'email' => Schema::text()->email(),
            'age' => Schema::number()->integer(),
        ]);

        $this->assertTrue($user->isValid(['email' => 'a@b.com', 'age' => 30]));
        $this->assertFalse($user->isValid(['email' => 'nope', 'age' => 30]));
    }

    public function test_a_missing_required_property_fails(): void
    {
        $user = Schema::dictionary(['email' => Schema::text()]);

        $this->assertFalse($user->isValid([]));
    }

    public function test_a_missing_optional_property_passes(): void
    {
        $user = Schema::dictionary([
            'email' => Schema::text(),
            'nickname' => Schema::text()->optional(),
        ]);

        $this->assertTrue($user->isValid(['email' => 'a@b.com']));
    }

    public function test_an_unknown_property_is_reported(): void
    {
        $user = Schema::dictionary(['email' => Schema::text()]);

        $report = $user->check(['email' => 'a@b.com', 'sneaky' => true]);

        $this->assertTrue($report->failed());
        $this->assertSame(['sneaky'], array_map(static fn (Finding $finding): string => $finding->path, $report->findings()));
    }

    // Reporting

    public function test_it_collects_every_finding_rather_than_stopping_at_the_first(): void
    {
        $user = Schema::dictionary([
            'email' => Schema::text()->email(),
            'age' => Schema::number()->positive(),
        ]);

        $report = $user->check(['email' => 'nope', 'age' => -1]);

        $this->assertSame(['email', 'age'], array_map(static fn (Finding $finding): string => $finding->path, $report->findings()));
    }

    public function test_a_finding_carries_the_path_it_was_found_at(): void
    {
        $schema = Schema::dictionary([
            'contact' => Schema::dictionary(['email' => Schema::text()->email()]),
        ]);

        $report = $schema->check(['contact' => ['email' => 'nope']]);

        $this->assertSame('contact.email', $report->findings()[0]->path);
        $this->assertSame('email', $report->findings()[0]->rule);
    }

    public function test_a_list_finding_carries_the_index(): void
    {
        $report = Schema::list(Schema::number())->check([1, 'two', 3]);

        $this->assertSame('1', $report->findings()[0]->path);
    }

    public function test_a_failing_kind_does_not_cascade_into_its_children(): void
    {
        $report = Schema::dictionary(['email' => Schema::text()])->check('not a dictionary');

        $this->assertCount(1, $report->findings());
        $this->assertSame('dictionary', $report->findings()[0]->rule);
    }

    public function test_assert_throws_and_carries_the_report(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Schema::text()->email()->assert('nope');
    }

    // Type algebra

    public function test_it_reports_its_kind(): void
    {
        $this->assertSame(Value::Text, Schema::text()->kind);
        $this->assertSame(Value::List, Schema::list(Schema::text())->kind);
    }

    public function test_two_schemas_of_the_same_shape_are_the_same_type(): void
    {
        $this->assertTrue(Schema::list(Schema::text())->same(Schema::list(Schema::text())));
        $this->assertFalse(Schema::list(Schema::text())->same(Schema::list(Schema::number())));
    }

    public function test_joining_disagreeing_types_widens_to_any(): void
    {
        $this->assertSame(Value::Any, Schema::text()->join(Schema::number())->kind);
        $this->assertSame(Value::Text, Schema::text()->join(Schema::text())->kind);
    }

    public function test_any_is_assignable_in_both_directions(): void
    {
        $this->assertTrue(Schema::text()->assignable(Schema::any()));
        $this->assertTrue(Schema::any()->assignable(Schema::text()));
        $this->assertFalse(Schema::text()->assignable(Schema::number()));
    }
}
