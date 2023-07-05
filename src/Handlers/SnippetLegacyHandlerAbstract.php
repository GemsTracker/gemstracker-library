<?php

namespace Gems\Handlers;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class SnippetLegacyHandlerAbstract extends \MUtil\Handler\SnippetLegacyHandlerAbstract
{
    public function __construct(SnippetResponderInterface $responder, TranslatorInterface $translate)
    {
        parent::__construct($responder, $translate);
        if (! $this->html) {
            \Gems\Html::init();
        }
    }
}