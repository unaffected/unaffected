<?php

declare(strict_types=1);

namespace Diagonal\Schema;

/**
 * Everything a walk found. Collected rather than thrown so one pass reports
 * every problem instead of only the first.
 */
class Report
{
    /** @var list<Finding> */
    private array $findings = [];

    public function add(Finding $finding): self
    {
        $this->findings[] = $finding;

        return $this;
    }

    /** @return list<Finding> */
    public function findings(): array
    {
        return $this->findings;
    }

    public function failed(): bool
    {
        return $this->findings !== [];
    }

    public function message(): string
    {
        return implode('; ', array_map(strval(...), $this->findings));
    }
}
