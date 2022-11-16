<?php

namespace Gems\Tracker\TrackEvent;

use MUtil\Translate\Translator;

abstract class TranslatableEventAbstract
{
    public function __construct(protected Translator $translator)
    {}
}