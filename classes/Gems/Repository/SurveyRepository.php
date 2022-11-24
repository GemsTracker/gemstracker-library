<?php

namespace Gems\Repository;

use Gems\Util\UtilDbHelper;

class SurveyRepository
{
    public function __construct(protected UtilDbHelper $utilDbHelper)
    {}

    /**
     * @return array mailId => description
     */
    public function getSurveyMailCodes(): array
    {
        return $this->utilDbHelper->getTranslatedPairsCached(
            'gems__mail_codes',
            'gmc_id',
            'gmc_mail_cause_target',
            ['mailcodes'],
            [
                'gmc_for_surveys' => 1,
                'gmc_active' => 1
            ],
            'ksort'
        );
    }
}