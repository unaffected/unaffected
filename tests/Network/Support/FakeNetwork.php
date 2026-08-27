<?php

declare(strict_types=1);

namespace Diagonal\Tests\Network\Support;

use Diagonal\Network\Network;

/** Records what was sent and answers with a canned reply. */
final class FakeNetwork extends Network
{
    /** @param array<string,mixed> $reply */
    public function __construct(
        private readonly array $reply,
        private array &$sent,
    ) {
        parent::__construct();
    }

    public function call(string $subject, array $payload, ?float $timeout = null): array
    {
        $this->sent = ['subject' => $subject, 'payload' => $payload, 'timeout' => $timeout];

        return $this->reply;
    }

    public function publish(string $subject, array $payload): void
    {
        $this->sent = ['subject' => $subject, 'payload' => $payload, 'queued' => true];
    }
}
