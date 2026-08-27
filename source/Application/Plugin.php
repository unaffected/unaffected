<?php

declare(strict_types=1);

namespace Diagonal\Application;

/**
 * A unit of composition. Plugins decorate the application and are installed
 * once per key, dependencies first.
 */
interface Plugin
{
    /** Stable identity. Installing the same key twice is a no-op. */
    public function key(): string;

    /** @return iterable<Plugin> Installed before this plugin. */
    public function dependencies(): iterable;

    public function install(Application $app): void;
}
