<?php

declare(strict_types=1);

namespace Diagonal\Server\Routes;

use Diagonal\Application\Transport;
use Diagonal\Gateway\Gateway;
use Diagonal\Server\Exchange;
use Throwable;
use TrueAsync\HttpRequest;
use TrueAsync\WebSocket;
use TrueAsync\WebSocketUpgrade;

/**
 * The socket edge. Speaks the same envelope the /api door does, so a client
 * can move between transports without learning a second protocol — the only
 * difference the far side sees is `Transport::Socket` on the context.
 */
class Socket
{
    private readonly Exchange $exchange;

    public function __construct(Gateway $gateway)
    {
        $this->exchange = new Exchange($gateway);
    }

    /** The handler the server hands each upgraded connection. */
    public function handle(WebSocket $socket, HttpRequest $request, WebSocketUpgrade $upgrade): void
    {
        $parameters = [
            'remote' => $socket->getRemoteAddress(),
            'path' => $request->getPath(),
        ];

        while (($message = $socket->recv()) !== null) {
            $frame = $this->frame($message->data, $parameters);

            try {
                $socket->send(json_encode($frame, JSON_THROW_ON_ERROR));
            } catch (Throwable) {
                // The peer went away mid-write; nothing to answer to.
                break;
            }
        }
    }

    /**
     * Answer one message. Pure with respect to the socket, so the protocol can
     * be asserted without one.
     *
     * @param  array<string,mixed>  $parameters
     * @return array<string,mixed>
     */
    public function frame(string $raw, array $parameters = []): array
    {
        return $this->exchange->answer($raw, $parameters, Transport::Socket)['frame'];
    }
}
