<?php

namespace Gems;

use Laminas\Validator\Translator\TranslatorInterface as LaminasTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;

class LaminasTranslator implements LaminasTranslatorInterface
{
    public function __construct(
        private readonly SymfonyTranslatorInterface $translator,
    ) {
    }

    public function translate($message, $textDomain = 'default', $locale = null)
    {
        if ($textDomain === 'default') {
            $textDomain = null;
        }

        return $this->translator->trans($message, [], $textDomain, $locale);
    }
}
