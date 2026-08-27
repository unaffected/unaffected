<?php

declare(strict_types=1);

namespace Diagonal\Network;

use InvalidArgumentException;

/**
 * The address of an action on the spine: `<prefix>.<service>.<action>`.
 *
 * The prefix is injectable so separate environments can share one cluster
 * without an engine in one answering for work belonging to another.
 */
class Subject
{
    public const PREFIX = 'service';

    public static function for(string $service, string $action, string $prefix = self::PREFIX): string
    {
        return "{$prefix}.{$service}.{$action}";
    }

    /** Wildcard covering every service, for an engine that serves all of them. */
    public static function all(string $prefix = self::PREFIX): string
    {
        return "{$prefix}.>";
    }

    /**
     * The last segment is the action; everything between the prefix and it is
     * the service, so a dotted service id like `security.user` round-trips.
     *
     * @return array{0:string,1:string} service, action
     */
    public static function parse(string $subject, string $prefix = self::PREFIX): array
    {
        $segments = explode('.', $subject);

        if (count($segments) < 3 || $segments[0] !== $prefix || in_array('', $segments, true)) {
            throw new InvalidArgumentException("[{$subject}] is not a `{$prefix}.<service>.<action>` subject.");
        }

        $action = array_pop($segments);
        array_shift($segments);

        return [implode('.', $segments), $action];
    }
}
