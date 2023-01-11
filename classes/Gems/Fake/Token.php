<?php

namespace Gems\Fake;

use Zalt\Loader\ProjectOverloader;
use DateTimeImmutable;
use DateInterval;

class Token extends \Gems\Tracker\Token
{
    public function __construct(protected ProjectOverloader $overloader)
    {
        //if ($tokenData === null) {
            $tokenData = $this->getTokenData();
        //}
        parent::__construct($tokenData);
    }

    public function getOrganization()
    {
        return new Organization();
    }

    public function getRespondent()
    {
        return $this->overloader->create(Respondent::class);
    }

    public function getRespondentTrack()
    {
        return $this->overloader->create(RespondentTrack::class);
    }

    public function getSurvey()
    {
        return $this->overloader->create(Survey::class);
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

    public function getTrackName()
    {
        return 'Example track';
    }

}