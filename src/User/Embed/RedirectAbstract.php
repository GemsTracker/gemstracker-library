<?php

namespace Gems\User\Embed;

use Zalt\Base\TranslatorInterface;

abstract class RedirectAbstract implements RedirectInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
    )
    {}
}