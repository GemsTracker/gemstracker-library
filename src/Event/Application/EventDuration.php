<?php

namespace Gems\Event\Application;

use DateTimeInterface;

trait EventDuration
{
    private DateTimeInterface|float|null $start = null;
    private DateTimeInterface|float|null $end = null;

    public function getDuration(): float|int|null
    {
        if ($this->start instanceof \DateTimeInterface) {
            $end = new \DateTimeImmutable();
            if ($this->end instanceof DateTimeInterface) {
                $end = $this->end;
            }

            return $end->getTimestamp() - $this->start->getTimestamp();
        }
        if (is_numeric($this->start)) {
            $end = microtime(true);
            if (is_numeric($this->end)) {
                $end = $this->end;
            }
            return $end - $this->start;
        }
        return null;
    }

    public function setEnd(DateTimeInterface|float|null $end = null): void
    {
        $this->end = $end;
        if ($end === null) {
            $this->end = microtime(true);
        }
    }

    public function setStart(DateTimeInterface|float|null $start = null): void
    {
        $this->start = $start;
        if ($start === null) {
            $this->start = microtime(true);
        }
    }

}