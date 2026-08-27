<?php

declare(strict_types=1);

use Diagonal\Service\Health\Health;

/**
 * Services this engine answers for. The gateway holds none of these — it puts
 * the work on the spine and an engine picks it up.
 *
 * @return list<\Diagonal\Service\Service>
 */
return [
    new Health(),
];
