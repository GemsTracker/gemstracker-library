<?php

namespace Gems\Messenger\Message;

class CommJob
{
    public function __construct(
        private readonly int $id,
    )
    {}

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}