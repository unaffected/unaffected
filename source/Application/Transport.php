<?php

declare(strict_types=1);

namespace Diagonal\Application;

/**
 * How a dispatch reached us. The application, gateway and actions stay
 * transport-agnostic — nothing branches on this to do its work — but it rides
 * the context so a hook can, which is the point of recording it.
 *
 * `Internal` is a call this process made itself, the way Feathers leaves
 * `params.provider` unset for internal calls.
 */
enum Transport: string
{
    case Internal = 'internal';
    case Http = 'http';
    case Socket = 'socket';
    case Cli = 'cli';

    /** Did this come from outside? */
    public function external(): bool
    {
        return $this !== self::Internal;
    }
}
