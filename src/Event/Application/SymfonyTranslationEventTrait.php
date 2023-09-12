<?php

namespace Gems\Event\Application;

use Zalt\Base\TranslatorInterface;

trait SymfonyTranslationEventTrait
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }
}