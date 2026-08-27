<?php

declare(strict_types=1);

namespace Diagonal\Tests\Service;

use Diagonal\Schema\Schema;
use Diagonal\Service\Health\Health;
use Diagonal\Service\Service;
use Diagonal\Tests\Application\Support\Repeat;
use PHPUnit\Framework\TestCase;

/** An action describes itself well enough to be published as an MCP tool. */
final class ToolTest extends TestCase
{
    public function test_an_action_carries_a_key_name_and_description(): void
    {
        $repeat = new Repeat();

        $this->assertSame('repeat', $repeat->key());
        $this->assertSame('Repeat', $repeat->name());
        $this->assertSame('Echo a message back.', $repeat->description());
    }

    public function test_the_tool_name_is_the_fully_scoped_id(): void
    {
        $tool = new Health()->add(new Repeat())->action('repeat')->tool();

        $this->assertSame('health.repeat', $tool['name']);
        $this->assertSame('Echo a message back.', $tool['description']);
    }

    public function test_the_tool_input_schema_is_json_schema(): void
    {
        $tool = new Health()->add(new Repeat())->action('repeat')->tool();

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 256],
            ],
            'required' => ['message'],
            'additionalProperties' => false,
        ], $tool['inputSchema']);
    }

    public function test_an_action_with_no_declared_input_advertises_an_open_schema(): void
    {
        $this->assertSame([], new Health()->action('check')->tool()['inputSchema']);
    }

    public function test_scalar_kinds_map_to_json_schema_types(): void
    {
        $this->assertSame(['type' => 'string'], Schema::text()->json());
        $this->assertSame(['type' => 'number'], Schema::number()->json());
        $this->assertSame(['type' => 'integer'], Schema::number()->integer()->json());
        $this->assertSame(['type' => 'boolean'], Schema::toggle()->json());
        $this->assertSame(['type' => 'null'], Schema::null()->json());
    }

    public function test_rules_become_json_schema_keywords(): void
    {
        $this->assertSame(['type' => 'string', 'format' => 'email'], Schema::text()->email()->json());
        $this->assertSame(['type' => 'string', 'format' => 'uuid'], Schema::text()->uuid()->json());
        $this->assertSame(['type' => 'number', 'minimum' => 1, 'maximum' => 10], Schema::number()->between(1, 10)->json());
        $this->assertSame(['type' => 'number', 'exclusiveMinimum' => 0], Schema::number()->positive()->json());
        $this->assertSame(['type' => 'string', 'enum' => ['a', 'b']], Schema::text()->in(['a', 'b'])->json());
    }

    public function test_a_list_carries_its_item_schema(): void
    {
        $this->assertSame(
            ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1, 'maxItems' => 5],
            Schema::list(Schema::text())->length(1, 5)->json(),
        );
    }

    public function test_an_optional_property_is_not_required(): void
    {
        $json = Schema::dictionary([
            'a' => Schema::text(),
            'b' => Schema::text()->optional(),
        ])->json();

        $this->assertSame(['a'], $json['required']);
        $this->assertArrayHasKey('b', $json['properties']);
    }

    public function test_every_action_a_service_holds_can_be_listed_as_a_tool(): void
    {
        $tools = array_map(
            static fn ($action): array => $action->tool(),
            array_values(new Health()->add(new Repeat())->actions()),
        );

        $this->assertSame(['health.check', 'health.repeat'], array_column($tools, 'name'));
    }
}
