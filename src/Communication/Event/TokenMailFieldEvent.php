<?php

namespace Gems\Communication\Event;

use Gems\Tracker\Token;

class TokenMailFieldEvent
{
    private array $mailFields = [];
    public function __construct(
        public readonly Token $token,
        public readonly string|null $language = null,
        public readonly array $jobContext = [],
    )
    {
    }

    public function addMailField(string $variable, mixed $value): void
    {
        $this->mailFields[$variable] = $value;
    }

    public function addMailFields(array $mailFields): void
    {
        foreach ($mailFields as $variable => $value) {
            $this->addMailField($variable, $value);
        }
    }

    public function getMailFields(): array
    {
        return $this->mailFields;
    }
}