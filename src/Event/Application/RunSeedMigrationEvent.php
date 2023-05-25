<?php

namespace Gems\Event\Application;

use Gems\Event\DatabaseMigrationEvent;
use DateTimeInterface;

class RunSeedMigrationEvent extends DatabaseMigrationEvent
{
    use EventDuration;
    public function __construct(
        private readonly string $type,
        private readonly int|string $version,
        private readonly string $module,
        private readonly string $name,
        private readonly string $status,
        private readonly string $sql,
        private readonly string|null $comment = null,
        DateTimeInterface|float|null $start = null,
        DateTimeInterface|float|null $end = null,
    )
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int|string
     */
    public function getVersion(): int|string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }
}