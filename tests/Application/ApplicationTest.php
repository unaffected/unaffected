<?php

declare(strict_types=1);

namespace Diagonal\Tests\Application;

use Diagonal\Application\Application;
use Diagonal\Tests\Application\Support\RecordingPlugin;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        RecordingPlugin::reset();
    }

    public function test_it_installs_a_plugin(): void
    {
        $app = Application::make([new RecordingPlugin('cache')]);

        $this->assertSame(['cache'], RecordingPlugin::$installed);
        $this->assertTrue($app->installed('cache'));
    }

    public function test_it_installs_dependencies_before_the_plugin_that_needs_them(): void
    {
        Application::make([
            new RecordingPlugin('gateway', [
                new RecordingPlugin('transport', [new RecordingPlugin('config')]),
            ]),
        ]);

        $this->assertSame(['config', 'transport', 'gateway'], RecordingPlugin::$installed);
    }

    public function test_it_installs_a_plugin_key_only_once(): void
    {
        Application::make([
            new RecordingPlugin('transport'),
            new RecordingPlugin('gateway', [new RecordingPlugin('transport')]),
        ]);

        $this->assertSame(['transport', 'gateway'], RecordingPlugin::$installed);
    }

    public function test_plugins_decorate_the_application_container(): void
    {
        $app = Application::make();

        $app->set('transport', 'in-memory');

        $this->assertSame('in-memory', $app->transport);
        $this->assertTrue(isset($app->transport));
    }
}
