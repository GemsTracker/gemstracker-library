<?php

namespace Gems\User\Embed;

use MUtil\Translate\Translator;

abstract class RedirectAbstract implements RedirectInterface
{
    public function __construct(
        protected Translator $translator,
    )
    {}
}