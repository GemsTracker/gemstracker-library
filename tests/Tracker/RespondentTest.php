<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GemsTest\Tracker;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\ConsentRepository;
use Gems\Repository\MailRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Tracker;
use Gems\Tracker\Respondent;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use MUtil\Translate\Translator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Description of RespondentTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class RespondentTest extends TestCase
{
    use ProphecyTrait;
    /**
     * @param array                  $respondentData
     * @param DateTimeInterface|null $date
     * @param bool                   $months
     * @param                        $expected
     * @return void
     * @dataProvider getAgeProvider
     */
    public function testGetAge(array $respondentData, ?DateTimeInterface $date, bool $months, $expected)
    {
        $respondent = $this->getRespondent(1,1,1, $respondentData);

        $actual = $respondent->getAge($date, $months);
        $this->assertEquals($expected, $actual);
    }

    public static function getAgeProvider()
    {
        $date = new DateTimeImmutable();
        $ageNine = $date->sub(new DateInterval('P10Y'))->add(new DateInterval('P1D'));
        return [
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-15'), true, 120],  // Happy birthday!
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-16'), true, 120],  // The day after
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-04-14'), true, 120],  // Almost a month
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-14'), true, 119],  // Tomorrow

            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-15'), false, 10],  // Happy birthday!
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-04-14'), false, 10],  // Almost another month
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-04-15'), false, 10],  // One month is nothing
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-14'), false, 9],   // One more day

            [['grs_birthday' => $ageNine], null, false, 9],

            [['grs_birthday' => 5], null, false, null],
        ];
    }

    protected function getRespondent(int $patientId = 1, int $organizationId = 1, int $respondentId = 1, array $data = []): Respondent
    {
        $data['gr2o_id_user'] = 1;
        $data['gr2o_patient_nr'] = 1;
        $data['gr2o_id_organization'] = 1;

        $consentRepository = $this->prophesize(ConsentRepository::class)->reveal();
        $mailRepository = $this->prophesize(MailRepository::class)->reveal();
        $organizationRepository = $this->prophesize(OrganizationRepository::class)->reveal();
        $receptionCodeRepository = $this->prophesize(ReceptionCodeRepository::class)->reveal();
        $resultFetcher = $this->prophesize(ResultFetcher::class)->reveal();

        $maskRepositoryPropecy = $this->prophesize(MaskRepository::class);
        $maskRepositoryPropecy->applyMaskToRow(Argument::type('array'))->willReturnArgument(0);
        $maskRepository = $maskRepositoryPropecy->reveal();

        $translator = $this->prophesize(Translator::class)->reveal();
        $translatedUtil = $this->prophesize(Translated::class)->reveal();
        $tracker = $this->prophesize(Tracker::class)->reveal();

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $currentUserRepositoryProphecy->getCurrentUserId()->willReturn(1);
        $currentUserRepository = $currentUserRepositoryProphecy->reveal();

        $respondentModelProphecy = $this->prophesize(RespondentModel::class);
        $respondentModelProphecy->loadFirst(Argument::type('array'))->willReturn($data);
        $respondentModelProphecy->applyStringAction(Argument::type('string'), Argument::type('bool'));

        return new Respondent(
            $patientId,
            $organizationId,
            $respondentId,
            $consentRepository,
            $mailRepository,
            $maskRepository,
            $organizationRepository,
            $receptionCodeRepository,
            $respondentModelProphecy->reveal(),
            $resultFetcher,
            $translator,
            $translatedUtil,
            $tracker,
            $currentUserRepository,
        );
    }
}
