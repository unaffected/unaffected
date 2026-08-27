<?php

declare(strict_types=1);

namespace Diagonal\Service;

use Closure;
use Diagonal\Application\Hook\Hook;
use Diagonal\Application\Hook\Scope;
use Diagonal\Application\Hook\Type;

/**
 * A collection of actions and the hooks that wrap them. The service id is the
 * prefix every action it holds is addressed by — `security.user` + `create`
 * gives `security.user.create`.
 */
class Service
{
    /** @var array<string,Action> */
    private array $actions = [];

    /** @var list<Hook> */
    private array $hooks = [];

    /**
     * @param  iterable<Action>  $actions
     * @param  iterable<Hook>  $hooks
     */
    public function __construct(
        public readonly string $id,
        iterable $actions = [],
        iterable $hooks = [],
        public readonly string $description = '',
    ) {
        foreach ($actions as $action) {
            $this->add($action);
        }

        foreach ($hooks as $hook) {
            $this->hooks[] = $hook;
        }
    }

    public function add(Action ...$actions): self
    {
        foreach ($actions as $action) {
            $this->actions[$action->key()] = $action->scopedTo($this->id);
        }

        return $this;
    }

    public function action(string $name): ?Action
    {
        return $this->actions[$name] ?? null;
    }

    /** @return array<string,Action> */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * Register a hook against this service. Without an action name it wraps
     * every action the service holds; with one it wraps only that action.
     */
    public function hook(Type $type, Closure $handler, ?string $action = null): self
    {
        $this->hooks[] = new Hook(
            $action === null ? Scope::Service : Scope::Action,
            $type,
            $handler,
            $this->id,
            $action,
        );

        return $this;
    }

    /**
     * Every hook this service contributes: its own, plus whatever its actions
     * declare for themselves.
     *
     * @return list<Hook>
     */
    public function hooks(): array
    {
        $own = array_map(
            fn (Hook $hook): Hook => $hook->service === null ? $hook->scopedTo($this->id) : $hook,
            $this->hooks,
        );

        $declared = [];

        foreach ($this->actions as $action) {
            $declared = [...$declared, ...$action->hooks()];
        }

        return [...$own, ...$declared];
    }
}
