<?php

declare(strict_types=1);

namespace Diagonal\Application;

use Closure;
use Diagonal\Application\Hook\Hook;
use Diagonal\Application\Hook\Hooks;
use Diagonal\Application\Hook\Scope;
use Diagonal\Application\Hook\Type;
use Diagonal\Exception\NotFoundException;
use Diagonal\Service\Action;
use Diagonal\Service\Service;
use OutOfBoundsException;

class Application
{
    public readonly string $key;

    /** @var list<string> */
    private array $plugins = [];

    /** @var array<string,mixed> */
    private array $container = [];

    /** @var array<string,Service> */
    private array $services = [];

    private Hooks $hooks;

    public function __construct(?string $key = null)
    {
        $this->key = $key ?? bin2hex(random_bytes(16));
        $this->hooks = new Hooks();
    }

    /**
     * @param  Plugin|iterable<Plugin>  $plugins
     */
    public static function make(Plugin|iterable $plugins = [], ?string $key = null): self
    {
        return (new self($key))->install($plugins);
    }

    /**
     * @param  Plugin|iterable<Plugin>  $plugin
     */
    public function install(Plugin|iterable $plugin): self
    {
        if (! $plugin instanceof Plugin) {
            foreach ($plugin as $one) {
                $this->install($one);
            }

            return $this;
        }

        if ($this->installed($plugin->key())) {
            return $this;
        }

        $this->install($plugin->dependencies());

        $this->plugins[] = $plugin->key();

        $plugin->install($this);

        return $this;
    }

    public function installed(string $key): bool
    {
        return in_array($key, $this->plugins, true);
    }

    public function set(string $key, mixed $value): self
    {
        $this->container[$key] = $value;

        return $this;
    }

    public function get(string $key): mixed
    {
        return $this->container[$key]
            ?? throw new OutOfBoundsException("Application has nothing bound to [{$key}].");
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->container);
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    // Services — a collection of actions and the hooks that wrap them.

    public function register(Service ...$services): self
    {
        foreach ($services as $service) {
            $this->services[$service->id] = $service;

            foreach ($service->hooks() as $hook) {
                $this->hooks->add($hook);
            }
        }

        return $this;
    }

    public function service(string $id): Service
    {
        return $this->services[$id] ?? throw NotFoundException::service($id);
    }

    public function action(string $service, string $action): Action
    {
        return $this->service($service)->action($action)
            ?? throw NotFoundException::action($service, $action);
    }

    /** @return array<string,Service> */
    public function services(): array
    {
        return $this->services;
    }

    // Hooks

    public function hooks(): Hooks
    {
        return $this->hooks;
    }

    public function hook(
        Scope $scope,
        Type $type,
        Closure $handler,
        ?string $service = null,
        ?string $action = null,
    ): self {
        $this->hooks->add(new Hook($scope, $type, $handler, $service, $action));

        return $this;
    }

    // Dispatch

    /**
     * Run an action through the hook pipeline and return its result.
     *
     * @param  array<string,mixed>  $data
     * @param  array<string,mixed>  $parameters
     */
    public function dispatch(
        string $service,
        string $action,
        array $data = [],
        array $parameters = [],
        mixed $identity = null,
        Transport $transport = Transport::Internal,
    ): mixed {
        return $this->run(new Context(
            app: $this,
            service: $service,
            action: $action,
            parameters: $parameters,
            data: $data,
            identity: $identity,
            transport: $transport,
        ))->result;
    }

    /** Run a context that is already built — the gateway and engine enter here. */
    public function run(Context $context): Context
    {
        return $this->hooks->pipe($context, $this->execute(...));
    }

    private function execute(Context $context): Context
    {
        $context->result = $this
            ->action($context->service, $context->action)
            ->execute($context);

        return $context;
    }
}
