<?php

declare(strict_types=1);

namespace Diagonal\Application\Hook;

use Closure;
use Diagonal\Application\Context;
use Throwable;

/**
 * The hook registry. Selecting and ordering the hooks for a given dispatch is
 * done once per path and cached; only the fold is paid per dispatch.
 */
class Hooks
{
    /** @var list<Hook> */
    private array $hooks = [];

    /** @var array<string,list<Closure(Context, callable): Context>> */
    private array $compiled = [];

    public function add(Hook $hook): self
    {
        $this->hooks[] = $hook;

        // A late registration has to be visible to paths already dispatched.
        $this->compiled = [];

        return $this;
    }

    /**
     * Build the pipeline this context addresses and run the context through it.
     *
     * @param  Closure(Context): Context  $terminal
     */
    public function pipe(Context $context, Closure $terminal): Context
    {
        return $this->pipeline($context, $terminal)($context);
    }

    /**
     * Build the chain for whatever the context addresses, wrapped around the
     * terminal. Selection and ordering are cached per path; only the fold is
     * paid per call.
     *
     * @param  Closure(Context): Context  $terminal
     * @return Closure(Context): Context
     */
    private function pipeline(Context $context, Closure $terminal): Closure
    {
        $path = $context->path();

        $this->compiled[$path] ??= $this->compile($context->service, $context->action);

        return self::fold($this->compiled[$path], $terminal);
    }

    /** @return list<Closure(Context, callable): Context> */
    private function compile(string $service, string $action): array
    {
        $matching = array_values(array_filter(
            $this->hooks,
            static fn (Hook $hook): bool => $hook->matches($service, $action),
        ));

        usort($matching, static fn (Hook $first, Hook $second): int
            => [$first->scope->depth(), $first->type->depth()] <=> [$second->scope->depth(), $second->type->depth()]);

        return array_map(self::adapt(...), $matching);
    }

    /** @return Closure(Context, callable): Context */
    private static function adapt(Hook $hook): Closure
    {
        $handler = $hook->handler;

        return match ($hook->type) {
            Type::Around => static function (Context $context, callable $next) use ($handler): Context {
                $context->type = Type::Around;

                return $handler($context, $next);
            },

            Type::Before => static function (Context $context, callable $next) use ($handler): Context {
                $context->type = Type::Before;
                $handler($context);

                return $next($context);
            },

            Type::After => static function (Context $context, callable $next) use ($handler): Context {
                $context = $next($context);
                $context->type = Type::After;
                $handler($context);

                return $context;
            },

            // A catch block. The handler recovers by returning; to propagate,
            // it rethrows. With no Error hook registered the throwable simply
            // travels out of the pipeline.
            Type::Error => static function (Context $context, callable $next) use ($handler): Context {
                try {
                    return $next($context);
                } catch (Throwable $error) {
                    $context->type = Type::Error;
                    $context->error = $error;

                    $handler($context);

                    return $context;
                }
            },
        };
    }

    /**
     * Nest the handlers into one closure. First in the list ends up outermost.
     *
     * @param  list<Closure(Context, callable): Context>  $handlers
     * @param  Closure(Context): Context  $terminal
     * @return Closure(Context): Context
     */
    private static function fold(array $handlers, Closure $terminal): Closure
    {
        $next = $terminal;

        foreach (array_reverse($handlers) as $handler) {
            $inner = $next;
            $next = static fn (Context $context): Context => $handler($context, $inner);
        }

        return $next;
    }
}
