<?php

namespace Gems\Fake;

use Gems\Db\ResultFetcher;
use Gems\Model;
use Gems\Repository\ConsentRepository;
use Gems\Repository\MailRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\User\Mask\MaskRepository;

class Respondent extends \Gems\Tracker\Respondent
{
    public function __construct(
        ConsentRepository $consentRepository,
        MailRepository $mailRepository,
        OrganizationRepository $organizationRepository,
        ReceptionCodeRepository $receptionCodeRepository,
        ResultFetcher $resultFetcher,
        MaskRepository $maskRepository,
        Model $modelLoader
    ) {
        parent::__construct(
            'EXAMPLE001',
            0,
            0,
            $consentRepository,
            $mailRepository,
            $organizationRepository,
            $receptionCodeRepository,
            $resultFetcher,
            $maskRepository,
            $modelLoader
        );
    }

    /*public function __construct(Translated $translatedUtil, Translator $translator, EventDispatcherInterface $eventDispatcher, array $config)
    {
        parent::__construct('EXAMPLE001', 0, 0);
        $this->translatedUtil = $translatedUtil;
        $this->translate = $translator;
        $this->event = $eventDispatcher;
        $this->config = $config;
        $this->initGenderTranslations();
        $this->refresh();
    }*/



    public function getOrganization(): Organization
    {
        return new Organization();
    }

    public function refresh(): void
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