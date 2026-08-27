<?php

declare(strict_types=1);

namespace Diagonal\Server;

use Diagonal\Application\Transport;
use Diagonal\Gateway\Gateway;

/**
 * One envelope in, one answer out. The /api door and the socket speak the same
 * protocol, so both ask this the same question and differ only in what they do
 * with the answer.
 */
final class Exchange
{
    public function __construct(private readonly Gateway $gateway) {}

    /**
     * @param  array<string,mixed>  $parameters
     * @return array{status:int, frame:array<string,mixed>}
     */
    public function answer(string $raw, array $parameters, Transport $transport): array
    {
        $envelope = Envelope::from($raw);

        if ($envelope === null) {
            return [
                'status' => 400,
                'frame' => Envelope::reject('Expected {type, meta, data:{action, input}}.'),
            ];
        }

        $routed = $envelope->request($parameters, $transport);

        if ($routed === null) {
            return [
                'status' => 400,
                'frame' => Envelope::reject(
                    "[{$envelope->action}] is not a <service>.<action> address.",
                    $envelope->meta,
                ),
            ];
        }

        if (! $envelope->wants()) {
            $this->gateway->queue($routed);

            return ['status' => 202, 'frame' => $envelope->acknowledge()];
        }

        $answered = $this->gateway->handle($routed);

        return [
            'status' => $answered->succeeded() ? 200 : Envelope::status($answered->error),
            'frame' => $envelope->reply($answered),
        ];
    }
}
