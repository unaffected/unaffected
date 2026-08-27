<?php

declare(strict_types=1);

namespace Diagonal\Tests\Server\Support;

use RuntimeException;

/**
 * A minimal HTTP/1.1 client — enough to drive the REST and envelope routes in
 * a test. One connection per request, `Connection: close`, no keep-alive and no
 * redirects, so the raw body comes back exactly as the edge wrote it.
 *
 * The raw body matters: the REST route encodes its own JSON and hands the
 * string to `HttpResponse::json()`, so a scalar result has to arrive quoted.
 */
class HttpClient
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly float $timeout = 5.0,
    ) {
        $this->connect();
    }

    /**
     * @param  array<string,string>  $headers
     * @return array{status:int, headers:array<string,string>, body:string}
     */
    public function send(string $method, string $path, ?string $body = null, array $headers = []): array
    {
        $socket = $this->connect();

        $lines = [
            "{$method} {$path} HTTP/1.1",
            "Host: {$this->host}:{$this->port}",
            'Connection: close',
        ];

        foreach ($headers as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }

        if ($body !== null) {
            $lines[] = 'Content-Length: '.strlen($body);
        }

        fwrite($socket, implode("\r\n", $lines)."\r\n\r\n".($body ?? ''));

        $raw = '';

        while (! feof($socket)) {
            $chunk = fread($socket, 8192);

            if ($chunk === false || $chunk === '') {
                break;
            }

            $raw .= $chunk;
        }

        fclose($socket);

        return self::parse($raw);
    }

    /**
     * @param  array<string,mixed>  $message
     * @return array{status:int, headers:array<string,string>, body:string}
     */
    public function sendJson(string $method, string $path, array $message): array
    {
        return $this->send($method, $path, json_encode($message, JSON_THROW_ON_ERROR), [
            'Content-Type' => 'application/json',
        ]);
    }

    /** @return resource */
    private function connect(): mixed
    {
        $socket = @stream_socket_client("tcp://{$this->host}:{$this->port}", $code, $error, $this->timeout);

        if ($socket === false) {
            throw new RuntimeException("Could not reach {$this->host}:{$this->port}: {$error}");
        }

        stream_set_timeout($socket, (int) $this->timeout, (int) (fmod($this->timeout, 1) * 1_000_000));

        return $socket;
    }

    /** @return array{status:int, headers:array<string,string>, body:string} */
    private static function parse(string $raw): array
    {
        $boundary = strpos($raw, "\r\n\r\n");

        if ($boundary === false) {
            throw new RuntimeException('The response carried no header boundary: '.$raw);
        }

        $head = explode("\r\n", substr($raw, 0, $boundary));
        $body = substr($raw, $boundary + 4);

        $status = (int) (explode(' ', array_shift($head) ?? '')[1] ?? 0);

        $headers = [];

        foreach ($head as $line) {
            $split = strpos($line, ':');

            if ($split === false) {
                continue;
            }

            $headers[strtolower(substr($line, 0, $split))] = trim(substr($line, $split + 1));
        }

        if (strtolower($headers['transfer-encoding'] ?? '') === 'chunked') {
            $body = self::dechunk($body);
        }

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    private static function dechunk(string $body): string
    {
        $decoded = '';

        while ($body !== '') {
            $break = strpos($body, "\r\n");

            if ($break === false) {
                break;
            }

            $length = (int) hexdec(trim(substr($body, 0, $break)));

            if ($length === 0) {
                break;
            }

            $decoded .= substr($body, $break + 2, $length);
            $body = substr($body, $break + 2 + $length + 2);
        }

        return $decoded;
    }
}
