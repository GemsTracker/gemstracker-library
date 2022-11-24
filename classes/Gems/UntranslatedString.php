<?php

namespace Gems;

use Symfony\Contracts\Translation\TranslatorInterface;

class UntranslatedString
{
    public function __construct(private readonly string $id)
    {
    }

    public function trans(TranslatorInterface $translator): string
    {
        return $translator->trans($this->id);
    }
}
