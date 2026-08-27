<?php

declare(strict_types=1);

namespace Diagonal\Tests\Application\Support;

use Diagonal\Application\Application;
use Diagonal\Application\Plugin;

final class RecordingPlugin implements Plugin
{
    /** @var list<string> */
    public static array $installed = [];

    /** @param list<Plugin> $dependencies */
    public function __construct(
        private string $key,
        private array $dependencies = [],
    ) {}

    public static function reset(): void
    {
        self::$installed = [];
    }

    public function key(): string
    {
        return $this->key;
    }

    public function dependencies(): iterable
    {
        return $this->dependencies;
    }

    public function install(Application $app): void
    {
        self::$installed[] = $this->key;
    }
}
