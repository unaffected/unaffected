<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server\Support;

use RuntimeException;

/**
 * A minimal RFC 6455 client — enough to drive the socket route in a test.
 * Text frames only, no continuation, no compression.
 */
class WebSocketClient
{
    /** @var resource */
    private $socket;

    public function __construct(string $host, int $port, string $path = '/socket', float $timeout = 5.0)
    {
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $code, $error, $timeout);

        if ($socket === false) {
            throw new RuntimeException("Could not reach {$host}:{$port}: {$error}");
        }

        $this->socket = $socket;
        stream_set_timeout($this->socket, (int) $timeout);

        $key = base64_encode(random_bytes(16));

        fwrite($this->socket, implode("\r\n", [
            "GET {$path} HTTP/1.1",
            "Host: {$host}:{$port}",
            'Upgrade: websocket',
            'Connection: Upgrade',
            "Sec-WebSocket-Key: {$key}",
            'Sec-WebSocket-Version: 13',
            '', '',
        ]));

        $headers = '';

        while (! str_contains($headers, "\r\n\r\n")) {
            $chunk = fread($this->socket, 1);

            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('The server closed during the handshake: '.$headers);
            }

            $headers .= $chunk;
        }

        if (! str_contains($headers, ' 101 ')) {
            throw new RuntimeException('Upgrade refused: '.strtok($headers, "\r\n"));
        }
    }

    public function send(string $text): void
    {
        $mask = random_bytes(4);
        $length = strlen($text);

        $header = chr(0x81);

        if ($length < 126) {
            $header .= chr(0x80 | $length);
        } elseif ($length < 65536) {
            $header .= chr(0x80 | 126).pack('n', $length);
        } else {
            $header .= chr(0x80 | 127).pack('J', $length);
        }

        $masked = '';

        for ($i = 0; $i < $length; $i++) {
            $masked .= $text[$i] ^ $mask[$i % 4];
        }

        fwrite($this->socket, $header.$mask.$masked);
    }

    /** @param array<string,mixed> $message */
    public function sendJson(array $message): void
    {
        $this->send(json_encode($message, JSON_THROW_ON_ERROR));
    }

    public function receive(float $timeout = 5.0): ?string
    {
        stream_set_timeout($this->socket, (int) $timeout, (int) (fmod($timeout, 1) * 1_000_000));

        $first = fread($this->socket, 2);

        if ($first === false || strlen($first) < 2) {
            return null;
        }

        $opcode = ord($first[0]) & 0x0F;
        $length = ord($first[1]) & 0x7F;

        if ($length === 126) {
            $length = unpack('n', fread($this->socket, 2))[1];
        } elseif ($length === 127) {
            $length = unpack('J', fread($this->socket, 8))[1];
        }

        $payload = '';

        while (strlen($payload) < $length) {
            $chunk = fread($this->socket, $length - strlen($payload));

            if ($chunk === false || $chunk === '') {
                break;
            }

            $payload .= $chunk;
        }

        // A close or ping frame is not a message; report nothing.
        return $opcode === 0x1 || $opcode === 0x2 ? $payload : null;
    }

    /** @return array<string,mixed>|null */
    public function receiveJson(float $timeout = 5.0): ?array
    {
        $raw = $this->receive($timeout);

        if ($raw === null) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }
}
