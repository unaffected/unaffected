<?php

declare(strict_types=1);

namespace Diagonal\Service;

use Closure;
use Diagonal\Application\Context;
use Diagonal\Application\Hook\Hook;
use Diagonal\Application\Hook\Scope;
use Diagonal\Application\Hook\Type;
use Diagonal\Exception\AuthenticationException;
use Diagonal\Exception\AuthorizationException;
use Diagonal\Exception\ValidationException;
use Diagonal\Exception\VerificationException;
use Diagonal\Schema\Schema;

/**
 * One addressable unit of work. Extend it to supply platform functionality.
 *
 * Identity mirrors a Diagonal task — KEY, NAME, DESCRIPTION — so an action can
 * be published as an MCP tool without a second description of itself.
 *
 * The guards are plain methods an action overrides, never properties to assign.
 * `authenticate()` and `authorize()` each answer whether the call is permitted;
 * `validate()` and `verify()` return the schema the input and the result must
 * satisfy. All four take the context, so each can be shaped by the call being
 * guarded rather than declared once and for all.
 */
abstract class Action
{
    /** Key within the owning service. The full id is `<service>.<key>`. */
    public const string KEY = '';

    public const string NAME = '';

    public const string DESCRIPTION = '';

    /** Set when a service scopes this action. */
    protected ?string $service = null;

    /** @var list<Hook> */
    protected array $hooks = [];

    abstract public function handle(Context $context): mixed;

    // Guards

    /** The schema the input must satisfy. */
    public function validate(?Context $context = null): Schema
    {
        return Schema::any();
    }

    /** The schema the result must satisfy. */
    public function verify(?Context $context = null): Schema
    {
        return Schema::any();
    }

    /** Whether the caller is authenticated. Override to admit anonymous callers. */
    protected function authenticate(Context $context): bool
    {
        return $context->identity !== null;
    }

    /** Whether the caller is permitted to run this action. */
    protected function authorize(Context $context): bool
    {
        return true;
    }

    // Identity

    public function key(): string
    {
        return static::KEY;
    }

    public function name(): string
    {
        return static::NAME !== '' ? static::NAME : static::KEY;
    }

    public function description(): string
    {
        return static::DESCRIPTION;
    }

    /** Fully qualified: `<service>.<key>`, or null until a service scopes it. */
    public function id(): ?string
    {
        return $this->service === null ? null : "{$this->service}.{$this->key()}";
    }

    public function scopedTo(string $service): static
    {
        $scoped = clone $this;
        $scoped->service = $service;

        return $scoped;
    }

    // Hooks — an action carries its own, the same way a service does.

    public function hook(Type $type, Closure $handler): static
    {
        $this->hooks[] = new Hook(Scope::Action, $type, $handler, $this->service, $this->key());

        return $this;
    }

    /** @return list<Hook> */
    public function hooks(): array
    {
        return array_map(
            fn (Hook $hook): Hook => $this->service === null ? $hook : $hook->scopedTo($this->service),
            $this->hooks,
        );
    }

    // MCP

    /**
     * The MCP tool descriptor for this action.
     *
     * @return array{name:string,description:string,inputSchema:array<string,mixed>}
     */
    public function tool(?Context $context = null): array
    {
        return [
            'name' => $this->id() ?? $this->key(),
            'description' => $this->description(),
            'inputSchema' => $this->validate($context)->json(),
        ];
    }

    // Execution

    /**
     * The guard band: authenticate, authorize, validate, handle, verify. It sits
     * inside every registered hook, so no hook can skip it and a `before` hook
     * can still shape the data that `validate` sees.
     */
    public function execute(Context $context): mixed
    {
        if (! $this->authenticate($context)) {
            throw AuthenticationException::for($this->id());
        }

        if (! $this->authorize($context)) {
            throw AuthorizationException::for($this->id());
        }

        $report = $this->validate($context)->check($context->data);

        if ($report->failed()) {
            throw new ValidationException($report, $this->id());
        }

        $result = $this->handle($context);

        $report = $this->verify($context)->check($result);

        if ($report->failed()) {
            throw new VerificationException($report, $this->id());
        }

        return $result;
    }
}
