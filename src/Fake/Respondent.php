<?php

namespace Gems\Fake;

use Gems\Util\Translated;
use MUtil\Translate\Translator;
use Psr\EventDispatcher\EventDispatcherInterface;

class Respondent extends \Gems\Tracker\Respondent
{
    public function __construct(Translated $translatedUtil, Translator $translator, EventDispatcherInterface $eventDispatcher, array $config)
    {
        parent::__construct('EXAMPLE001', 0, 0);
        $this->translatedUtil = $translatedUtil;
        $this->translate = $translator;
        $this->event = $eventDispatcher;
        $this->config = $config;
        $this->initGenderTranslations();
        $this->refresh();
    }

    public function getOrganization()
    {
        return new Organization();
    }

    public function refresh()
    {
        $this->exists = true;
        $this->_gemsData = [
            'grs_iso_lang' => 'en',
            'grs_gender' => 'F',
            'grs_last_name' => 'Berg',
            'grs_surname_prefix' => 'van den',
            'grs_first_name' => 'Janneke',
            'gr2o_email' => 'janneke.van.den.berg@test.test',
        ];
    }
}