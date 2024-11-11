<?php

namespace Gems\SnippetsActions\Vue;

use Gems\Snippets\Vue\PatientVueSnippet;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ParameterActionInterface;

class VueParameterAction extends VueAction implements ParameterActionInterface
{
    protected array $_snippets = [
        PatientVueSnippet::class,
    ];
}