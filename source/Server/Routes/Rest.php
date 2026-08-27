<?php

declare(strict_types=1);

namespace Diagonal\Server\Routes;

use Diagonal\Application\Transport;
use Diagonal\Exception\Failure;
use Diagonal\Gateway\Gateway;
use Diagonal\Gateway\Request;
use Diagonal\Server\Route;
use LogicException;
use TrueAsync\HttpRequest;
use TrueAsync\HttpResponse;

/**
 * A plain HTTP API, the way Feathers serves one.
 *
 *   GET    /health          health.find
 *   POST   /health          health.create
 *   GET    /health/check    health.check
 *   POST   /health/check    health.check  (the body is the input)
 *   GET    /health/1        health.get
 *   PUT    /health/1        health.update
 *   PATCH  /health/1        health.patch
 *   DELETE /health/1        health.remove
 *
 * The method and the path say what to do, so nothing needs to be restated in
 * the body. The body is the input and nothing else — the query string is read
 * only for `find` — and the result is the answer and nothing else.
 */
class Rest implements Route
{
    /** Feathers' service methods, mapped from the verb when no action is named. */
    private const array COLLECTION = [
        'GET' => 'find',
        'POST' => 'create',
    ];

    private const array RESOURCE = [
        'GET' => 'get',
        'PUT' => 'update',
        'PATCH' => 'patch',
        'DELETE' => 'remove',
    ];

    public function __construct(
        protected readonly string $prefix = '',
    ) {}

    public function matches(HttpRequest $request): bool
    {
        return $this->segments($request) !== null;
    }

    public function handle(HttpRequest $request, HttpResponse $response, Gateway $gateway): void
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $segments = $this->segments($request);

        if ($segments === null) {
            throw new LogicException("[{$path}] is not a path this route answers.");
        }

        $resolved = self::resolve($segments, $method);

        if ($resolved === null) {
            $this->respond($response, [
                'name' => 'MethodNotAllowed',
                'message' => "[{$method}] is not allowed on [{$path}].",
                'code' => 405,
            ], 405);

            return;
        }

        [$service, $action, $id] = $resolved;

        $input = $this->input($request, $action);

        if ($id !== null) {
            $input['id'] = $id;
        }

        $answered = $gateway->handle(new Request(
            $service,
            $action,
            $input,
            [
                'remote' => $request->getRemoteAddress(),
                'protocol' => $request->getHttpVersion(),
            ],
            Transport::Http,
        ));

        if (! $answered->succeeded()) {
            $failure = Failure::from($answered->error);

            $this->respond($response, $failure->feathers(), $failure->status());

            return;
        }

        $this->respond($response, $answered->result, $action === 'create' ? 201 : 200);
    }

    /** json() forwards a string verbatim, so everything is encoded here. */
    private function respond(HttpResponse $response, mixed $body, int $status): void
    {
        $response->json(json_encode($body, JSON_THROW_ON_ERROR), $status);
    }

    /**
     * `<service>` or `<service>/<action-or-id>`. A dotted first segment keeps
     * nested services addressable: /security.user/create.
     *
     * @param  list<string>  $segments
     * @return array{0:string,1:string,2:string|null}|null  null when the method means nothing here
     */
    private static function resolve(array $segments, string $method): ?array
    {
        if (count($segments) === 1) {
            if (! array_key_exists($method, self::COLLECTION)) {
                return null;
            }

            return [$segments[0], self::COLLECTION[$method], null];
        }

        [$service, $tail] = $segments;

        // A numeric or uuid-looking tail is a record, not an action, so the
        // verb decides the method the way it does for any REST resource.
        if (! self::identifier($tail)) {
            return [$service, $tail, null];
        }

        if (! array_key_exists($method, self::RESOURCE)) {
            return null;
        }

        return [$service, self::RESOURCE[$method], $tail];
    }

    /** @return list<string>|null null when the path is not ours to answer */
    private function segments(HttpRequest $request): ?array
    {
        $path = $request->getPath();

        if ($this->prefix !== '' && ! str_starts_with($path, $this->prefix)) {
            return null;
        }

        $path = substr($path, strlen($this->prefix));

        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== ''));

        if ($segments === [] || count($segments) > 2) {
            return null;
        }

        return $segments;
    }

    private static function identifier(string $segment): bool
    {
        if (ctype_digit($segment)) {
            return true;
        }

        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $segment) === 1;
    }

    /**
     * The body is the input. The query string is only read for `find`, where
     * filtering and pagination genuinely belong in the URL — everywhere else
     * input travels in the body, so nothing is addressable that should not be.
     *
     * @return array<string,mixed>
     */
    private function input(HttpRequest $request, string $action): array
    {
        if ($action === 'find') {
            return $request->getQuery();
        }

        $decoded = json_decode($request->getBody() ?: '{}', true);

        return is_array($decoded) ? $decoded : [];
    }
}
