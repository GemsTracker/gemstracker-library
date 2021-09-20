<?php

/**
 *
 * @package    Gems
 * @subpackage Communication
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Communication;

use Gems\Communication\JobMessenger\JobMessengerAbstract;
use Gems\Communication\JobMessenger\MailJobMessenger;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 * @package    Gems
 * @subpackage Communication
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class MailJobTest extends \Gems_Test_DbTestAbstract
{
    /**
     * @var \Gems\Util\CommJobsUtil
     */
    protected $commJobsUtil;
    
    /**
     * @var \Gems_Tracker_TrackerInterface
     */
    protected $tracker;

    protected function assertTokenAddresses(array $jobData, \Gems_Tracker_Token $token, $from, $fromName, $to, $fallback)
    {
        $mailLoader = $this->loader->getMailLoader();
        $mailer     = $mailLoader->getMailer('token', $token->getTokenId());
        
        $messenger = $this->commJobsUtil->getJobMessenger($jobData);
        $this->assertInstanceOf('\Gems\Communication\JobMessenger\MailJobMessenger', $messenger, 'Unexpected messenger class.');
        
        if ($messenger instanceof MailJobMessenger) {
            $this->assertEquals($from, $messenger->getFromEmail($jobData, $mailer));
            $this->assertEquals($fromName, $messenger->getFromName($jobData, $mailer));
            $this->assertEquals($to, $messenger->getToEmail($jobData, $mailer, $token, true));
            $this->assertEquals($fallback, $messenger->getFallbackEmail($jobData, $mailer));
        }
    }
    
    /**
     * @param array $multipleTokensData
     * @param int   $respondentId
     * @param int   $roundOrder
     * @return \Gems_Tracker_Token|null
     */
    protected function assertTokenExists(array $multipleTokensData, $respondentId, $roundOrder)
    {
        $token = $this->findToken($multipleTokensData, $respondentId, $roundOrder);
        
        $this->assertNotEmpty($token, "A token for respondent $respondentId, round $roundOrder is not in the set.");
        
        return $token;
    }

    /**
     * @param array $multipleTokensData
     * @param int   $respondentId
     * @param int   $roundOrder
     * @throws \Gems_Exception
     */
    protected function assertTokenNotExists(array $multipleTokensData, $respondentId, $roundOrder)
    {
        $token = $this->findToken($multipleTokensData, $respondentId, $roundOrder);

        $this->assertEmpty($token, "A token for respondent $respondentId, round $roundOrder is in the set.");
    }

    /**
     * @param int $respondentId
     * @param int $organizationsId
     * @param int $subDays The number of days to subtract to get the start date
     * @param null|int $relationId Optional relation Id
     * @return \Gems_Tracker_RespondentTrack
     * @throws \Zend_Date_Exception
     */
    protected function createRespondentTrack($respondentId, $organizationsId, $subDays, $relationId = null)
    {
        static $respondentTrackid = 0;
        
        if (! $respondentTrackid) {
            // Calculate a new respondent track id as otherwise $tracker->createRespondentTrack() will fail
            // because two batch commands will get the same id within a session  
            $respondentTrackid = $this->db->fetchOne("SELECT COALESCE(MAX(gr2t_id_respondent_track), 0) + 1 AS newId FROM gems__respondent2track");
        }
        
        $startDate = new \MUtil_Date();
        $data      = [
            'gr2t_id_respondent_track' => ++$respondentTrackid,
            'gr2t_start_date' => $startDate->subDay($subDays),
            ];
        $fields    = [];
        if ($relationId) {
            $key = FieldsDefinition::makeKey(FieldMaintenanceModel::FIELDS_NAME, 1);
            $fields[$key] = $relationId;
        }
        
        return $this->tracker->createRespondentTrack($respondentId, $organizationsId, 1, 1, $data, $fields);
    }

    /**
     * @param array $multipleTokensData
     * @param int   $respondentId
     * @param int   $roundOrder
     * @return \Gems_Tracker_Token|null
     * @throws \Gems_Exception
     */
    protected function findToken(array $multipleTokensData, $respondentId, $roundOrder)
    {
        foreach ($multipleTokensData as $tokenData) {
            $token = $this->tracker->getToken($tokenData['gto_id_token']);
            // echo $token->getRespondentId() . '--'  . $token->getRoundOrder() . "\n";
            if ($token->exists && ($token->getRespondentId() == $respondentId) && ($token->getRoundOrder() == $roundOrder)) {
                return $token;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function getDataSet()
    {
        $testCase = $this->getName();
        $testFile =  str_replace('.php', "_$testCase.yml", __FILE__);
        if (file_exists($testFile)) {
            return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($testFile);
        }

        //Dataset className.yml has the minimal data we need to perform our tests
        $classFile =  str_replace('.php', '.yml', __FILE__);
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($classFile);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->commJobsUtil = $this->loader->getUtil()->getCommJobsUtil();
        $this->tracker      = $this->loader->getTracker();
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testNoMailInitial()
    {
        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);
        
        $this->assertEmpty($multipleTokensData);
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testRespondentOneNoRelation()
    {
        $this->createRespondentTrack(10, 70, 1);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertCount(1, $multipleTokensData, 'We expected 1 token for one respondents as no relation was set.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testRespondentOneWithRelation()
    {
        $this->createRespondentTrack(10, 70, 1, 10);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        // print_r($this->db->fetchAll("SELECT * FROM gems__tokens"));

        $this->assertCount(2, $multipleTokensData, 'We expected 2 tokens for one respondents and one relation.');
    }

    /**
     * Uses relations only job 2
     */
    public function testRespondentAllOnlyRelationsJob()
    {
        $this->createRespondentTrack(10, 70, 1, 10);
        $this->createRespondentTrack(20, 70, 1);
        $this->createRespondentTrack(30, 71, 1, 30);

        $jobData            = $this->commJobsUtil->getJob(2);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        // print_r($this->db->fetchAll("SELECT * FROM gems__tokens"));

        $this->assertCount(2, $multipleTokensData, 'We expected 2 tokens only for relations.');

        // print_r($multipleTokensData);
        $token10 = $this->assertTokenExists($multipleTokensData, 10, 30);
        $this->assertTokenNotExists($multipleTokensData, 20, 30);
        $token30 = $this->assertTokenExists($multipleTokensData, 30, 30);
        
        $this->assertTokenAddresses($jobData, $token10, 'test1@org.com', 'Test one', 'johnny@who.is', 'test1@org.com');
        $this->assertTokenAddresses($jobData, $token30, 'test2@org.com', 'Test two', 'janet@who.is', 'test2@org.com');
    }

    /**
     * Uses respondents only, no relations job 3
     */
    public function testRespondentsAllOnlyRespondentJobs()
    {
        $this->createRespondentTrack(10, 70, 1, 10);
        $this->createRespondentTrack(20, 70, 1);
        $this->createRespondentTrack(30, 71, 1, 30);

        $jobData            = $this->commJobsUtil->getJob(3);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        // print_r($this->db->fetchAll("SELECT * FROM gems__tokens"));

        $this->assertCount(3, $multipleTokensData, 'We expected 3 tokens only for respondents, not relations.');
        
        $token10 = $this->assertTokenExists($multipleTokensData, 10, 20);
        $token20 = $this->assertTokenExists($multipleTokensData, 20, 20);
        $token30 = $this->assertTokenExists($multipleTokensData, 30, 20);
        
        $this->assertTokenAddresses($jobData, $token10, 'test1@org.com', 'Test one', 'a123@test.nl', 'test1@org.com');
        $this->assertTokenAddresses($jobData, $token20, 'test1@org.com', 'Test one', 'b123@test.nl', 'test1@org.com');
        $this->assertTokenAddresses($jobData, $token30, 'test2@org.com', 'Test two', 'c234@test.nl', 'test2@org.com');
    }
}