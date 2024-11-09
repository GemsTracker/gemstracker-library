<?php

namespace Gems\Fake;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Log\Loggers;
use Gems\Project\ProjectSettings;
use Gems\Repository\ConsentRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use DateTimeImmutable;
use DateInterval;

class Token extends \Gems\Tracker\Token
{
    /*public function __construct(protected ProjectOverloader $overloader)
    {
        //if ($tokenData === null) {
            $tokenData = $this->getTokenData();
        //}
        parent::__construct($tokenData);
    }*/

    /*
    public function __construct(
        ResultFetcher $resultFetcher,
        MaskRepository $maskRepository,
        Tracker $tracker,
        ProjectSettings $projectSettings,
        ConsentRepository $consentRepository,
        OrganizationRepository $organizationRepository,
        ReceptionCodeRepository $receptionCodeRepository,
        RespondentRepository $respondentRepository,
        ProjectOverloader $projectOverloader,
        Translated $translatedUtil,
        Locale $locale,
        TokenRepository $tokenRepository,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        MessageBusInterface $messageBus,
        Loggers $loggers,
        CurrentUserRepository $currentUserRepository
    ) {
        parent::__construct(
            $this->getTokenData(),
            $resultFetcher,
            $maskRepository,
            $tracker,
            $projectSettings,
            $consentRepository,
            $organizationRepository,
            $receptionCodeRepository,
            $respondentRepository,
            $projectOverloader,
            $translatedUtil,
            $locale,
            $tokenRepository,
            $eventDispatcher,
            $translator,
            $messageBus,
            $loggers,
            $currentUserRepository
        );
    } -- */

    public function getOrganization(): Organization
    {
        return new Organization($this->organizationRepository->getSiteUrls());
    }

    public function getRespondent(): Respondent
    {
        return $this->projectOverloader->create(Respondent::class);
    }

    public function getRespondentTrack(): RespondentTrack
    {
        return $this->projectOverloader->create(RespondentTrack::class, ['gr2t_id_respondent_track' => 0], $this->currentUser->getUserId());
    }

    public function getSurvey(): Survey
    {
        return $this->projectOverloader->create(Survey::class);
    }

    public function getTokenData(): array
    {
        $organization = $this->getOrganization();

        $now = new DateTimeImmutable();
        $nextMonth = $now->add(new DateInterval('P1M'));

        return [
            'gto_id_token' => 'abcd-1234',
            'gto_id_respondent' => 0,
            'gto_id_organization' => $organization->getId(),
            'gto_id_survey' => 9999,
            'gto_id_respondent_track' => 987654321,
            'gto_round_description' => 'Test round',
            'gto_id_track' => 123456789123456789,
            'grs_id_user' => 0,
            'gto_valid_from' => $now,
            'gto_valid_until' => $nextMonth,


            'gr2o_id_user' => 0,
            'gr2o_patient_nr' => 'TEST001',
            'gco_code' => 'OK',

        ];
    }

    public function getTrackName(): string
    {
        return 'Example track';
    }

}