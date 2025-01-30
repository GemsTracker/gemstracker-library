<?php

namespace GemsTest\Function\Messenger\Handler;

use Gems\Messenger\Handler\SendTokenMessageHandler;
use Gems\Messenger\Message\SendTokenMessage;
use GemsTest\TestData\General\TestCommJobSeed;
use GemsTest\TestData\General\TestCommMessengersSeed;
use GemsTest\TestData\General\TestCommTemplatesSeed;
use GemsTest\TestData\General\TestConsentsSeed;
use GemsTest\TestData\General\TestGroupsSeed;
use GemsTest\TestData\General\TestMailCodesSeed;
use GemsTest\TestData\General\TestOrganizationSeed;
use GemsTest\TestData\General\TestReceptionCodesSeed;
use GemsTest\TestData\General\TestRespondentSeed;
use GemsTest\TestData\General\TestRespondentTrackSeed;
use GemsTest\TestData\General\TestRolesSeed;
use GemsTest\TestData\General\TestRoundSeed;
use GemsTest\TestData\General\TestSurveySeed;
use GemsTest\TestData\General\TestTokenSeed;
use GemsTest\TestData\General\TestTrackSeed;
use GemsTest\testUtils\ConfigTrait;
use GemsTest\testUtils\ContainerTrait;
use GemsTest\testUtils\DatabaseTestCase;
use GemsTest\testUtils\MailTestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
class SendTokenMessageHandlerTest extends DatabaseTestCase
{
    use ConfigTrait;

    use ContainerTrait;
    use MailTestTrait;

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
        'gems__comm_jobs',
        'gems__comm_messengers',
        'gems__comm_templates',
        'gems__comm_template_translations',
        'gems__mail_codes',
        'gems__roles',
        'gems__groups',
        'gems__track_fields',
        'gems__track_appointments',
        'gems__translations',
        'gems__mail_servers',
        'gems__log_respondent_communications',
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
        TestCommJobSeed::class,
        TestCommMessengersSeed::class,
        TestCommTemplatesSeed::class,
        TestMailCodesSeed::class,
        TestRolesSeed::class,
        TestGroupsSeed::class,
    ];

    protected function getMessageHandler(): SendTokenMessageHandler
    {
        return $this->container->get(SendTokenMessageHandler::class);
    }

    public function testSendOneToken(): void
    {
        $messageHandler = $this->getMessageHandler();

        $message = new SendTokenMessage(
            800,
            '1234-abcd',
            [],
            false,
            false,
        );

        $messageHandler($message);

        $this->assertEquals(1, $this->getTokenSentCount($message->getTokenId()));
        $this->assertNumberOfMailsSent(1);
    }

    public function testSendOneTokenMarkTwo(): void
    {
        $messageHandler = $this->getMessageHandler();

        $message = new SendTokenMessage(
            800,
            '1234-abcd',
            ['4321-dcba'],
            false,
            false,
        );

        $messageHandler($message);

        $this->assertEquals(1, $this->getTokenSentCount($message->getTokenId()));
        $this->assertEquals(1, $this->getTokenSentCount('4321-dcba'));
        $this->assertNumberOfMailsSent(1);
    }

    protected function getTokenSentCount(string $tokenId): int
    {
        return $this->resultFetcher->fetchOne('SELECT gto_mail_sent_num FROM gems__tokens WHERE gto_id_token = ?', [$tokenId]) ?? 0;
    }
}