<?php

namespace GemsTest\Function\Repository;

use Gems\Repository\CommJobRepository;
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
use GemsTest\TestData\General\TestSourceSeed;
use GemsTest\TestData\General\TestSurveySeed;
use GemsTest\TestData\General\TestTokenSeed;
use GemsTest\TestData\General\TestTrackSeed;
use GemsTest\testUtils\ConfigTrait;
use GemsTest\testUtils\ContainerTrait;
use GemsTest\testUtils\DatabaseTestCase;
use GemsTest\testUtils\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @group database
 */
class CommJobRepositoryTest extends DatabaseTestCase
{
    use ConfigTrait;
    use ContainerTrait;

    public array $dbTables = [
        'gems__organizations',
        'gems__respondents',
        'gems__respondent2org',
        'gems__consents',
        'gems__tracks',
        'gems__surveys',
        'gems__sources',
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
        'gems__respondent_relations',
        'gems__staff',
    ];

    protected array $seeds = [
        TestOrganizationSeed::class,
        TestRespondentSeed::class,
        TestConsentsSeed::class,
        TestTrackSeed::class,
        TestSurveySeed::class,
        TestSourceSeed::class,
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

    protected function getRepository(): CommJobRepository
    {
        return $this->container->get(CommJobRepository::class);
    }

    public function testGetAllJobs(): void
    {
        $repository = $this->getRepository();

        $jobs = $repository->getAllJobs();
        $this->assertCount(1, $jobs);
        $firstJob = reset($jobs);

        $this->assertEquals(800, $firstJob['gcj_id_job']);
    }

    public static function getJobFilterProvider(): array
    {
        return [
            [
                [
                    'gcj_id_organization' => 70,
                    'gcj_filter_mode' => 'N',
                    'gcj_to_method' => 'A',
                    'gcj_target' => 2,
                    'gcj_fallback_method' => 'O',
                    'gcj_id_track' => null,
                    'gcj_round_description' => null,
                    'gcj_id_survey' => null,
                    'gcj_filter_days_between' => 7,
                    'gcj_filter_max_reminders' => 3,
                ],
                null,
                null,
                false,
                [
                    'gto_id_organization' => 70,
                    'gto_mail_sent_date' => null,
                    [
                        'gto_id_relation' => 0,
                        'gto_id_relation IS NULL'
                    ],
                    'ggp_member_type != \'staff\'',
                    'can_email' => 1,
                ],
            ],
            [
                [
                    'gcj_id_organization' => 71,
                    'gcj_filter_mode' => 'N',
                    'gcj_to_method' => 'A',
                    'gcj_target' => 2,
                    'gcj_fallback_method' => 'O',
                    'gcj_id_track' => null,
                    'gcj_round_description' => null,
                    'gcj_id_survey' => null,
                    'gcj_filter_days_between' => 7,
                    'gcj_filter_max_reminders' => 3,
                ],
                null,
                70,
                false,
                [
                    '1=0',
                ],
            ],
            [
                [
                    'gcj_id_organization' => 70,
                    'gcj_filter_mode' => 'N',
                    'gcj_to_method' => 'A',
                    'gcj_target' => 2,
                    'gcj_fallback_method' => 'O',
                    'gcj_id_track' => null,
                    'gcj_round_description' => null,
                    'gcj_id_survey' => null,
                    'gcj_filter_days_between' => 7,
                    'gcj_filter_max_reminders' => 3,
                ],
                null,
                70,
                false,
                [
                    'gto_id_organization' => 70,
                    'gto_mail_sent_date' => null,
                    [
                        'gto_id_relation' => 0,
                        'gto_id_relation IS NULL'
                    ],
                    'ggp_member_type != \'staff\'',
                    'can_email' => 1,
                ],
            ],
        ];
    }

    #[DataProvider('getJobFilterProvider')]
    public function testGetJobFilter(array $jobSettings, ?int $respondentId = null, ?int $organizationId = null, bool $forceSent = false, array $expected = []): void
    {
        $repository = $this->getRepository();

        $result = $repository->getJobFilter($jobSettings, $respondentId, $organizationId, $forceSent);
        if ($expected != ['1=0']) {
            $expected = [
                'gtr_active' => 1,
                'gsu_active' => 1,
                'grc_success' => 1,
                'gto_completion_time' => null,
                'gto_valid_from <= CURRENT_TIMESTAMP',
                '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)',
                ...$expected,
            ];
        }

        $this->assertEquals($expected, $result);
    }

    public function testGetSendableTokensSendOneMarkTwo(): void
    {
        $repository = $this->getRepository();
        $this->resultFetcher->query('UPDATE gems__tokens SET gto_valid_from = NOW() WHERE gto_id_token IN (\'1234-abcd\', \'4321-dcba\')');
        $tokens = $repository->getSendableTokens(800);

        $expected = [
            'send' => [
                '1234-abcd',
            ],
            'markSent' => [
                '4321-dcba',
            ],
        ];

        $this->assertEquals($expected, $tokens);
    }

    public function testGetSendableTokensSendTwoMarkTwo(): void
    {
        $repository = $this->getRepository();
        $this->resultFetcher->query('UPDATE gems__tokens SET gto_valid_from = NOW() WHERE gto_id_token IN (\'1234-abcd\', \'4321-dcba\')');
        $this->resultFetcher->query('UPDATE gems__comm_jobs SET gcj_process_method = \'M\' WHERE gcj_id_job = 800');
        $tokens = $repository->getSendableTokens(800);

        $expected = [
            'send' => [
                '1234-abcd',
                '4321-dcba'
            ],
            'markSent' => [],
        ];

        $this->assertEquals($expected, $tokens);
    }

    public function testGetSendableTokensSendOneMarkOne(): void
    {
        $repository = $this->getRepository();
        $this->resultFetcher->query('UPDATE gems__tokens SET gto_valid_from = NOW() WHERE gto_id_token IN (\'1234-abcd\', \'4321-dcba\')');
        $this->resultFetcher->query('UPDATE gems__comm_jobs SET gcj_process_method = \'A\' WHERE gcj_id_job = 800');
        $tokens = $repository->getSendableTokens(800);

        $expected = [
            'send' => [
                '1234-abcd',
            ],
            'markSent' => [],
        ];

        $this->assertEquals($expected, $tokens);
    }

    public function testGetSendableTokensNestedSendOneMarkTwo(): void
    {
        $repository = $this->getRepository();
        $this->resultFetcher->query('UPDATE gems__tokens SET gto_valid_from = NOW() WHERE gto_id_token IN (\'1234-abcd\', \'4321-dcba\')');
        $tokens = $repository->getSendableTokensNested(800);

        $expected = [
            '1234-abcd' => [
                '4321-dcba',
            ],
        ];

        $this->assertEquals($expected, $tokens);
    }

    public function testGetSendableTokensNestedSendTwoMarkTwo(): void
    {
        $repository = $this->getRepository();
        $this->resultFetcher->query('UPDATE gems__tokens SET gto_valid_from = NOW() WHERE gto_id_token IN (\'1234-abcd\', \'4321-dcba\')');
        $this->resultFetcher->query('UPDATE gems__comm_jobs SET gcj_process_method = \'M\' WHERE gcj_id_job = 800');
        $tokens = $repository->getSendableTokensNested(800);

        $expected = [
            '1234-abcd' => [],
            '4321-dcba' => [],
        ];

        $this->assertEquals($expected, $tokens);
    }

    public function testGetSendableTokensNestedSendOneMarkOne(): void
    {
        $repository = $this->getRepository();
        $this->resultFetcher->query('UPDATE gems__tokens SET gto_valid_from = NOW() WHERE gto_id_token IN (\'1234-abcd\', \'4321-dcba\')');
        $this->resultFetcher->query('UPDATE gems__comm_jobs SET gcj_process_method = \'A\' WHERE gcj_id_job = 800');
        $tokens = $repository->getSendableTokensNested(800);

        $expected = [
            '1234-abcd' => [],
        ];

        $this->assertEquals($expected, $tokens);
    }

    public function testGetTokenData(): void
    {
        $repository = $this->getRepository();

        $this->resultFetcher->query('UPDATE gems__tokens SET gto_valid_from = NOW() WHERE gto_id_token IN (\'1234-abcd\', \'4321-dcba\')');

        $job = $repository->getJob(800);
        $tokenData = $repository->getTokenData($job);
        $this->assertCount(2, $tokenData);
    }
}