<?php

declare(strict_types=1);

/**
 * @package    GemsTest
 * @subpackage Condition
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace GemsTest\Condition;

use Gems\Condition\ConditionLoader;
use Gems\Condition\Round\RepeatLessCondition;
use Gems\Condition\RoundConditionInterface;
use Gems\Tracker;
use Gems\Tracker\Engine\AnyStepEngine;
use Gems\Tracker\Token;
use GemsTest\TestData\General\Condition\TestConditionSeed;
use GemsTest\TestData\General\Condition\TestRoundConditionSeed;
use GemsTest\TestData\General\Condition\TestSurveyConditionSeed;
use GemsTest\TestData\General\Condition\TestTokenConditionSeed;
use GemsTest\TestData\General\Condition\TestTrackConditionSeed;
use GemsTest\TestData\General\TestConsentsSeed;
use GemsTest\TestData\General\TestGroupsSeed;
use GemsTest\TestData\General\TestOrganizationSeed;
use GemsTest\TestData\General\TestReceptionCodesSeed;
use GemsTest\TestData\General\TestRespondentSeed;
use GemsTest\TestData\General\TestRespondentTrackSeed;
use GemsTest\TestData\General\TestRolesSeed;
use GemsTest\TestData\General\TestSourceSeed;
use GemsTest\testUtils\ContainerTrait;
use GemsTest\testUtils\DatabaseTestCase;

/**
 * @package    GemsTest
 * @subpackage Condition
 * @since      Class available since version 1.0
 */
class RepeatLessConditionTest extends DatabaseTestCase
{
    use ContainerTrait;

    protected array $dbTables = [
        'gems__roles',
        'gems__groups',
        'gems__organizations',
        'gems__respondents',
        'gems__respondent2org',
        'gems__consents',
        'gems__tracks',
        'gems__track_appointments',
        'gems__track_fields',
        'gems__sources',
        'gems__surveys',
        'gems__conditions',
        'gems__rounds',
        'gems__reception_codes',
        'gems__respondent2track',
        'gems__tokens',
        'gems__translations',
    ];

    protected array $seeds = [
        TestRolesSeed::class,
        TestGroupsSeed::class,
        TestOrganizationSeed::class,
        TestConsentsSeed::class,
        TestRespondentSeed::class,
        TestConditionSeed::class,
        TestSourceSeed::class,
        TestSurveyConditionSeed::class,
        TestTrackConditionSeed::class,
        TestRoundConditionSeed::class,
        TestReceptionCodesSeed::class,
        TestRespondentTrackSeed::class,
        TestTokenConditionSeed::class,
    ];

    public static function provideTokens(): array
    {
        return [
            'skipTrackSurvey' => ['1234-comp', '1234-skip', 'skip'],
            'validTrackSurvey' => ['1234-comp', '1234-cont', 'OK'],
        ];
    }

    public function testLoadCondition()
    {
        $conditionLoader = $this->container->get(ConditionLoader::class);
        $this->assertInstanceOf(ConditionLoader::class, $conditionLoader);

        $condition = $conditionLoader->loadCondition('1000');
        $this->assertInstanceOf(RoundConditionInterface::class, $condition);
        $this->assertInstanceOf(RepeatLessCondition::class, $condition);
    }


    /**
     * @dataProvider provideTokens
     * @param string $firstToken
     * @param string $secondToken
     * @param string $receptionCode
     * @return void
     * @throws \Gems\Exception\Coding
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function testRunCondition(string $firstToken, string $secondToken, string $receptionCode)
    {
        $tracker = $this->container->get(Tracker::class);
        $this->assertInstanceOf(Tracker::class, $tracker);

        $firstToken = $tracker->getToken('1234-comp');
        $this->assertInstanceOf(Token::class, $firstToken);

        $respTrack = $firstToken->getRespondentTrack();
        $this->assertInstanceOf(Tracker\RespondentTrack::class, $respTrack);
        $trackEngine = $respTrack->getTrackEngine();
        $this->assertInstanceOf(AnyStepEngine::class, $trackEngine);

        $this->assertEquals(2, $trackEngine->checkTokensFrom($respTrack, $firstToken, 1));

        $token = $tracker->getToken('1234-skip');
        $this->assertEquals('skip', $token->getReceptionCode()->getCode());
    }
}