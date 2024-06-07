<?php

namespace GemsTest\Messenger\Handler;

use Gems\Messenger\Handler\SendTokenMessageHandler;
use Gems\Messenger\Message\SendTokenMessage;
use GemsTest\TestData\General\TestReceptionCodesSeed;
use GemsTest\testUtils\ConfigModulesTrait;
use GemsTest\testUtils\ConfigTrait;
use GemsTest\testUtils\ContainerTrait;
use GemsTest\testUtils\DatabaseTestCase;
use GemsTest\TestData\General\TestOrganizationSeed;
use GemsTest\TestData\General\TestRespondentSeed;
use GemsTest\TestData\General\TestRespondentTrackSeed;
use GemsTest\TestData\General\TestRoundSeed;
use GemsTest\TestData\General\TestSurveySeed;
use GemsTest\TestData\General\TestTokenSeed;
use GemsTest\TestData\General\TestTrackSeed;
use GemsTest\TestData\General\TestConsentsSeed;

class SendTokenMessageHandlerTest extends DatabaseTestCase
{
    use ConfigTrait, ConfigModulesTrait {
        ConfigModulesTrait::getModules insteadof ConfigTrait;
    }

    use ContainerTrait;

    protected array $dbTables = [
        'gems__organizations',
        'gems__respondents',
        'gems__respondent2org',
        'gems__consents',
        'gems__tracks',
        'gems__surveys',
        'gems__rounds',
        'gems__reception_codes',
        'gems__respondent2track',
        'gems__tokens',
    ];

    protected array $seeds = [
        TestOrganizationSeed::class,
        TestRespondentSeed::class,
        TestConsentsSeed::class,
        TestTrackSeed::class,
        TestSurveySeed::class,
        TestRoundSeed::class,
        TestRespondentTrackSeed::class,
        TestReceptionCodesSeed::class,
        TestTokenSeed::class,
    ];

    protected function getMessageHandler(): SendTokenMessageHandler
    {
        return $this->container->get(SendTokenMessageHandler::class);
    }

    public function testSendOneToken(): void
    {
        $messageHandler = $this->getMessageHandler();

        $message = new SendTokenMessage(
            1,
            '1234-abcd',
            [],
            true,
            false,
        );

        $messageHandler($message);

        $this->assertTrue(true);
    }
}