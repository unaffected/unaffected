<?php

declare(strict_types=1);

namespace Diagonal\Schema;

use Closure;
use InvalidArgumentException;

/**
 * A structural description of a value, plus the fine-grained rules it must
 * satisfy. Chains are immutable, so a schema is a reusable value.
 *
 * The `kind` is what a resolver occupying this slot must produce, which is how
 * the same declaration serves both a literal payload and a resolved one.
 */
class Schema
{
    /**
     * @param  array<string,Schema>  $properties
     * @param  list<Rule>  $rules
     */
    private function __construct(
        public readonly Value $kind,
        public readonly ?Schema $items = null,
        public readonly array $properties = [],
        public readonly array $rules = [],
        public readonly bool $optional = false,
    ) {}

    // Constructors

    public static function null(): self
    {
        return new self(Value::Null);
    }

    public static function toggle(): self
    {
        return new self(Value::Toggle);
    }

    public static function number(): self
    {
        return new self(Value::Number);
    }

    public static function text(): self
    {
        return new self(Value::Text);
    }

    public static function any(): self
    {
        return new self(Value::Any);
    }

    public static function list(?self $items = null): self
    {
        return new self(Value::List, items: $items);
    }

    /** @param array<string,Schema> $properties */
    public static function dictionary(array $properties = []): self
    {
        return new self(Value::Dictionary, properties: $properties);
    }

    // Composition — every one returns a new schema

    public function optional(bool $optional = true): self
    {
        return new self($this->kind, $this->items, $this->properties, $this->rules, $optional);
    }

    public function rule(Rule ...$rules): self
    {
        return new self(
            $this->kind,
            $this->items,
            $this->properties,
            [...$this->rules, ...array_values($rules)],
            $this->optional,
        );
    }

    /** @param array<string,mixed> $parameters */
    public function must(string $name, Closure $predicate, string $message, array $parameters = []): self
    {
        return $this->rule(new Callback($name, $predicate, $message, $parameters));
    }

    // Rule sugar

    public function length(?int $minimum = null, ?int $maximum = null): self
    {
        return $this->must(
            'length',
            static function (mixed $value) use ($minimum, $maximum): bool {
                $length = is_array($value) ? count($value) : mb_strlen((string) $value);

                return ($minimum === null || $length >= $minimum)
                    && ($maximum === null || $length <= $maximum);
            },
            sprintf('must be between %s and %s long', $minimum ?? 'any', $maximum ?? 'any'),
            ['minimum' => $minimum, 'maximum' => $maximum],
        );
    }

    public function between(int|float $minimum, int|float $maximum): self
    {
        return $this->must(
            'between',
            static fn (mixed $value): bool => $value >= $minimum && $value <= $maximum,
            "must be between {$minimum} and {$maximum}",
            ['minimum' => $minimum, 'maximum' => $maximum],
        );
    }

    public function minimum(int|float $minimum): self
    {
        return $this->must(
            'minimum',
            static fn (mixed $value): bool => $value >= $minimum,
            "must be at least {$minimum}",
            ['minimum' => $minimum],
        );
    }

    public function maximum(int|float $maximum): self
    {
        return $this->must(
            'maximum',
            static fn (mixed $value): bool => $value <= $maximum,
            "must be at most {$maximum}",
            ['maximum' => $maximum],
        );
    }

    public function integer(): self
    {
        return $this->must(
            'integer',
            static fn (mixed $value): bool => is_int($value) || (is_float($value) && floor($value) === $value),
            'must be a whole number',
        );
    }

    public function positive(): self
    {
        return $this->must('positive', static fn (mixed $value): bool => $value > 0, 'must be positive');
    }

    public function email(): self
    {
        return $this->must(
            'email',
            static fn (mixed $value): bool => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'must be an email address',
        );
    }

    public function uuid(): self
    {
        return $this->must(
            'uuid',
            static fn (mixed $value): bool => is_string($value)
                && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1,
            'must be a uuid',
        );
    }

    public function pattern(string $expression): self
    {
        return $this->must(
            'pattern',
            static fn (mixed $value): bool => is_string($value) && preg_match($expression, $value) === 1,
            "must match {$expression}",
            ['pattern' => $expression],
        );
    }

    /** @param list<mixed> $choices */
    public function in(array $choices): self
    {
        return $this->must(
            'in',
            static fn (mixed $value): bool => in_array($value, $choices, true),
            'must be one of: '.implode(', ', array_map(strval(...), $choices)),
            ['choices' => $choices],
        );
    }

    // Checking

    public function check(mixed $value, string $path = '', ?Report $report = null): Report
    {
        $report ??= new Report();

        if (! $this->kind->holds($value)) {
            // Stop here: reporting the children of a value that is not even the
            // right shape buries the one finding that matters.
            return $report->add(new Finding(
                $path,
                $this->kind->value,
                "must be {$this->kind->value}",
            ));
        }

        foreach ($this->rules as $rule) {
            if (! $rule->holds($value)) {
                $report->add(new Finding($path, $rule->name(), $rule->message(), $rule->parameters()));
            }
        }

        if ($this->kind === Value::List && $this->items !== null) {
            foreach ($value as $index => $item) {
                $this->items->check($item, self::at($path, (string) $index), $report);
            }
        }

        if ($this->kind === Value::Dictionary) {
            $this->checkProperties($value, $path, $report);
        }

        return $report;
    }

    /** @param array<string,mixed> $value */
    private function checkProperties(array $value, string $path, Report $report): void
    {
        foreach ($this->properties as $name => $schema) {
            $at = self::at($path, $name);

            if (! array_key_exists($name, $value)) {
                if (! $schema->optional) {
                    $report->add(new Finding($at, 'required', 'is required'));
                }

                continue;
            }

            $schema->check($value[$name], $at, $report);
        }

        foreach (array_keys($value) as $name) {
            if (! array_key_exists($name, $this->properties)) {
                $report->add(new Finding(self::at($path, (string) $name), 'unknown', 'is not a known property'));
            }
        }
    }

    public function isValid(mixed $value): bool
    {
        return ! $this->check($value)->failed();
    }

    public function assert(mixed $value): void
    {
        $report = $this->check($value);

        if ($report->failed()) {
            throw new InvalidArgumentException($report->message());
        }
    }

    // MCP / JSON Schema

    /**
     * The JSON Schema this node describes. Rules that have a keyword
     * equivalent become one; the rest stay runtime-only checks.
     *
     * @return array<string,mixed>
     */
    public function json(): array
    {
        $json = match ($this->kind) {
            Value::Null => ['type' => 'null'],
            Value::Toggle => ['type' => 'boolean'],
            Value::Number => ['type' => 'number'],
            Value::Text => ['type' => 'string'],
            Value::Any => [],
            Value::List => array_filter([
                'type' => 'array',
                'items' => $this->items?->json(),
            ], static fn (mixed $value): bool => $value !== null),
            Value::Dictionary => $this->jsonProperties(),
        };

        foreach ($this->rules as $rule) {
            $json = [...$json, ...self::keyword($rule, $this->kind)];
        }

        return $json;
    }

    /** @return array<string,mixed> */
    private function jsonProperties(): array
    {
        $properties = [];
        $required = [];

        foreach ($this->properties as $name => $schema) {
            $properties[$name] = $schema->json();

            if (! $schema->optional) {
                $required[] = $name;
            }
        }

        return array_filter([
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
        ], static fn (mixed $value): bool => $value !== [] );
    }

    /** @return array<string,mixed> */
    private static function keyword(Rule $rule, Value $kind): array
    {
        $parameters = $rule->parameters();

        return match ($rule->name()) {
            'length' => array_filter(
                $kind === Value::List
                    ? ['minItems' => $parameters['minimum'] ?? null, 'maxItems' => $parameters['maximum'] ?? null]
                    : ['minLength' => $parameters['minimum'] ?? null, 'maxLength' => $parameters['maximum'] ?? null],
                static fn (mixed $value): bool => $value !== null,
            ),
            'between' => ['minimum' => $parameters['minimum'], 'maximum' => $parameters['maximum']],
            'minimum' => ['minimum' => $parameters['minimum']],
            'maximum' => ['maximum' => $parameters['maximum']],
            'integer' => ['type' => 'integer'],
            'positive' => ['exclusiveMinimum' => 0],
            'email' => ['format' => 'email'],
            'uuid' => ['format' => 'uuid'],
            'pattern' => ['pattern' => $parameters['pattern']],
            'in' => ['enum' => $parameters['choices']],
            default => [],
        };
    }

    // Algebra

    public function property(string $name): ?self
    {
        return $this->properties[$name] ?? null;
    }

    /** Structurally identical? Rules are checks, not shape, so they do not count. */
    public function same(self $other): bool
    {
        if ($this->kind !== $other->kind) {
            return false;
        }

        if ($this->kind === Value::List) {
            return ($this->items === null) === ($other->items === null)
                && ($this->items === null || $this->items->same($other->items));
        }

        if ($this->kind === Value::Dictionary) {
            if (count($this->properties) !== count($other->properties)) {
                return false;
            }

            foreach ($this->properties as $name => $schema) {
                $match = $other->property($name);

                if ($match === null || ! $schema->same($match)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** Widen to the narrowest schema that describes both. */
    public function join(self $other): self
    {
        return $this->same($other) ? $this : self::any();
    }

    /** Can a value described by $actual stand in for this one? */
    public function assignable(self $actual): bool
    {
        if ($this->kind === Value::Any || $actual->kind === Value::Any) {
            return true;
        }

        if ($this->kind !== $actual->kind) {
            return false;
        }

        if ($this->kind === Value::List) {
            return $this->items === null
                || $actual->items === null
                || $this->items->assignable($actual->items);
        }

        if ($this->kind === Value::Dictionary) {
            foreach ($this->properties as $name => $schema) {
                $match = $actual->property($name);

                if ($match === null || ! $schema->assignable($match)) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function at(string $path, string $segment): string
    {
        return $path === '' ? $segment : "{$path}.{$segment}";
    }
}
