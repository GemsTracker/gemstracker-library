<?php

namespace Gems\Event\Application;

use MUtil\Translate\Translator;

trait SymfonyTranslationEventTrait
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * @param Translator $translator
     */
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }
}