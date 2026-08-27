<?php

declare(strict_types=1);

namespace Diagonal\Tests\Network;

use Diagonal\Application\Application;
use Diagonal\Application\Context;
use Diagonal\Application\Hook\Scope;
use Diagonal\Application\Hook\Type;
use Diagonal\Exception\NotFoundException;
use Diagonal\Exception\RemoteException;
use Diagonal\Gateway\Gateway;
use Diagonal\Gateway\Request;
use Diagonal\Tests\Network\Support\FakeNetwork;
use PHPUnit\Framework\TestCase;

final class ForwardTest extends TestCase
{
    public function test_a_network_hook_answers_for_a_service_this_process_does_not_have(): void
    {
        $app = Application::make(key: 'gateway');

        $app->hook(Scope::Network, Type::Around, function (Context $context, callable $next): Context {
            $context->result = 'from elsewhere';

            return $context;
        });

        // No service is registered here at all.
        $this->assertSame('from elsewhere', $app->dispatch('security.user', 'create'));
    }

    public function test_without_such_a_hook_an_unregistered_service_still_fails(): void
    {
        $this->expectException(NotFoundException::class);

        Application::make(key: 'gateway')->dispatch('security.user', 'create');
    }

    public function test_forward_sends_the_dispatch_over_the_spine_and_returns_the_reply(): void
    {
        $sent = [];

        $app = Application::make(key: 'gateway');
        $app->hook(Scope::Network, Type::Around, new FakeNetwork(['ok' => true, 'result' => 'created'], $sent)->forward());

        $this->assertSame('created', $app->dispatch('security.user', 'create', ['email' => 'a@b.com'], ['ip' => '::1']));

        $this->assertSame('service.security.user.create', $sent['subject']);
        $this->assertSame(['email' => 'a@b.com'], $sent['payload']['data']);
        $this->assertSame(['ip' => '::1'], $sent['payload']['parameters']);
    }

    public function test_forward_raises_a_failure_the_far_side_reported(): void
    {
        $sent = [];

        $app = Application::make(key: 'gateway');
        $app->hook(Scope::Network, Type::Around, new FakeNetwork(['ok' => false, 'error' => ['type' => NotFoundException::class, 'message' => 'nope']], $sent)->forward());

        $this->expectException(RemoteException::class);
        $this->expectExceptionMessage('nope');

        $app->dispatch('security.user', 'create');
    }

    public function test_a_queued_dispatch_is_published_and_never_waits(): void
    {
        $sent = [];

        $app = Application::make(new Gateway(), key: 'gateway');
        $app->hook(Scope::Network, Type::Around, new FakeNetwork(['ok' => true, 'result' => 'never read'], $sent)->forward());

        $app->gateway->queue(new Request('security.user', 'create', ['email' => 'a@b.com']));

        $this->assertTrue($sent['queued']);
        $this->assertSame('service.security.user.create', $sent['subject']);
    }

    public function test_the_gateway_forwards_without_holding_any_service(): void
    {
        $sent = [];

        $app = Application::make(new Gateway(), key: 'gateway');
        $app->hook(Scope::Network, Type::Around, new FakeNetwork(['ok' => true, 'result' => 'created'], $sent)->forward());

        $response = $app->gateway->handle(new Request('security.user', 'create', ['email' => 'a@b.com']));

        $this->assertTrue($response->succeeded());
        $this->assertSame('created', $response->result);
        $this->assertSame([], $app->services());
    }
}
