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
class MailJobTest extends \Gems\Test\DbTestAbstract
{
    /**
     * @var \Gems\Util\CommJobsUtil
     */
    protected $commJobsUtil;
    
    /**
     * @var \Gems\Tracker\TrackerInterface
     */
    protected $tracker;

    protected function assertTokenAddresses(array $jobData, \Gems\Tracker\Token $token, $from, $fromName, $to, $fallback)
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
     * @param int   , $relationId Optional
     * @return \Gems\Tracker\Token|null
     */
    protected function assertTokenExists(array $multipleTokensData, $respondentId, $roundOrder, $relationId = null)
    {
        $token = $this->findToken($multipleTokensData, $respondentId, $roundOrder, $relationId);
        
        if ($relationId) {
            $this->assertNotEmpty($token, "A token for respondent $respondentId, round $roundOrder, $relationId is nott in the set.");
        } else {
            $this->assertNotEmpty($token, "A token for respondent $respondentId, round $roundOrder is not in the set.");
        }
        
        return $token;
    }

    /**
     * @param       $tokenId
     * @param array $mailTestData
     */
    protected function assertTokenMailFields($tokenId, array $mailTestData)
    {
        $mailLoader = $this->loader->getMailLoader();
        $mailer     = $mailLoader->getMailer('token', $tokenId);

        $mailFields = $mailer->getMailFields(false);
        // print_r($mailFields);
        foreach ($mailTestData as $name => $value) {
            $this->assertArrayHasKey($name, $mailFields);
            $this->assertEquals($value, $mailFields[$name], "Failed asserting that the value of mailfield $name is correct.");
        }
    }

    /**
     * @param array $multipleTokensData
     * @param int   $respondentId
     * @param int   $roundOrder
     * @throws \Gems\Exception
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
     * @return \Gems\Tracker\RespondentTrack
     * @throws \Zend_Date_Exception
     */
    protected function createRespondentTrack($respondentId, $organizationsId, $trackId, $subDays, $relationId = null, $mailCode = 100)
    {
        static $respondentTrackid = 0;
        
        if (! $respondentTrackid) {
            // Calculate a new respondent track id as otherwise $tracker->createRespondentTrack() will fail
            // because two batch commands will get the same id within a session  
            $respondentTrackid = $this->db->fetchOne("SELECT COALESCE(MAX(gr2t_id_respondent_track), 0) + 1 AS newId FROM gems__respondent2track");
        }
        
        $startDate = new \MUtil\Date();
        $data      = [
            'gr2t_id_respondent_track' => ++$respondentTrackid,
            'gr2t_start_date'          => $startDate->subDay($subDays),
            'gr2t_mailable'            => $mailCode,
            ];
        $fields    = [];
        if ($relationId) {
            $key = FieldsDefinition::makeKey(FieldMaintenanceModel::FIELDS_NAME, 1);
            $fields[$key] = $relationId;
        }
        
        return $this->tracker->createRespondentTrack($respondentId, $organizationsId, $trackId, 1, $data, $fields);
    }

    /**
     * @param array $multipleTokensData
     * @param int   $respondentId
     * @param int   $roundOrder
     * @param int   $relationId Optional
     * @return \Gems\Tracker\Token|null
     * @throws \Gems\Exception
     */
    protected function findToken(array $multipleTokensData, $respondentId, $roundOrder, $relationId = null)
    {
        foreach ($multipleTokensData as $tokenData) {
            $token = $this->tracker->getToken($tokenData['gto_id_token']);
            // echo $token->getRespondentId() . '--'  . $token->getRoundOrder() . "\n";
            if ($token->exists && ($token->getRespondentId() == $respondentId) && ($token->getRoundOrder() == $roundOrder)) {
                if ((! $relationId) || ($relationId == $token->getRelationId())) {
                    return $token;
                }
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
    public function testMailCodesNotUsed()
    {
        $this->createRespondentTrack(10, 70, 2, 1);
        $this->createRespondentTrack(20, 70, 2, 1);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertCount(4, $multipleTokensData, 'We expected 4 tokens for 2 respondents as all have the mail code 100.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testMailCodesUsedForRespondents()
    {
        $this->createRespondentTrack(10, 70, 2, 1);
        $this->createRespondentTrack(20, 71, 2, 1);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertCount(3, $multipleTokensData, 'We expected 3 tokens for 2 respondents as one respondent has the mail code 50.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testMailCodesUsedForTracks()
    {
        $this->createRespondentTrack(10, 70, 2, 1, null, 50);
        $this->createRespondentTrack(20, 70, 2, 1, null, 50);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertCount(2, $multipleTokensData, 'We expected 2 tokens for 2 respondents as both tracks have the mail code 50.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testMailCodesUsedForTracksAndRespondents()
    {
        $this->createRespondentTrack(10, 70, 2, 1, null, 50);
        $this->createRespondentTrack(20, 71, 2, 1, null, 100);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertCount(2, $multipleTokensData, 'We expected 2 tokens for 2 respondents as one tracks and one respondent have the mail code 50.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testMailCodesZeroForRespondent()
    {
        $this->createRespondentTrack(30, 70, 2, 1);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertEmpty($multipleTokensData, 'We expected no tokens for 1 respondent as the respondent has the mail code 0.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testMailCodesZeroForTracks()
    {
        $this->createRespondentTrack(10, 70, 2, 1, null, 0);
        $this->createRespondentTrack(20, 71, 2, 1, null, 0);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertEmpty($multipleTokensData, 'We expected no tokens for 2 respondents as both tracks have the mail code 0.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testMailCodesZeroForTracksOrRespondents()
    {
        $this->createRespondentTrack(10, 70, 2, 1, null, 0);
        $this->createRespondentTrack(30, 70, 2, 1, null, 100);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertEmpty($multipleTokensData, 'We expected no tokens for 2 respondents as both either the track or the respondent has the mail code 0.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testNoMailInitial()
    {
        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);
        
        $this->assertEmpty($multipleTokensData, 'We expected no tokens as there are no respondent tracks.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testRespondentOneNoRelation()
    {
        $this->createRespondentTrack(10, 70, 1, 1);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        $this->assertCount(1, $multipleTokensData, 'We expected 1 token for 1 respondent as no relation was set.');
    }

    /**
     * Uses generic (respondents & relations) job 1
     */
    public function testRespondentOneWithRelation()
    {
        $this->createRespondentTrack(10, 70, 1, 1, 10);

        $jobData            = $this->commJobsUtil->getJob(1);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        // print_r($this->db->fetchAll("SELECT * FROM gems__tokens"));

        $this->assertCount(2, $multipleTokensData, 'We expected 2 tokens for 1 respondent and 1 relation.');
    }

    /**
     * Uses relations only job 2
     */
    public function testRespondentAllOnlyRelationsJob()
    {
        $this->createRespondentTrack(10, 70, 1, 1, 10);
        $this->createRespondentTrack(10, 70, 1, 1, 20);
        $this->createRespondentTrack(20, 70, 1, 1);
        $this->createRespondentTrack(30, 71, 1, 1, 30);

        $jobData            = $this->commJobsUtil->getJob(2);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        // print_r($this->db->fetchAll("SELECT * FROM gems__tokens"));

        $this->assertCount(3, $multipleTokensData, 'We expected 2 tokens only for relations.');

        // print_r($multipleTokensData);
        $token10 = $this->assertTokenExists($multipleTokensData, 10, 30, 10);
        $token12 = $this->assertTokenExists($multipleTokensData, 10, 30, 20);
        $this->assertTokenNotExists($multipleTokensData, 20, 30);
        $token30 = $this->assertTokenExists($multipleTokensData, 30, 30);
        
        $this->assertTokenMailFields($token10->getTokenId(), [
            'dear' => 'Dear mr. Walker',
            'name' => 'Johnny Walker',
            'last_name' => 'Walker',
            'full_name' => 'Mr. Walker',
            'greeting' => 'mr. Walker',
            'relation_about' => 'Test With relation',
            'relation_about_first_name' => 'Test',
            'relation_about_last_name' => 'With relation',
            'relation_about_full_name' => 'Mr. Test With relation',
            'relation_about_dear' => 'Dear mr. With relation',
            'relation_about_greeting' => 'mr. With relation',
            'relation_field_name' => 'relation',
            ]);
        $this->assertTokenMailFields($token12->getTokenId(), [
            'dear' => 'Dear mrs. Walker',
            'name' => 'Janine Walker',
            'last_name' => 'Walker',
            'full_name' => 'Mrs. Walker',
            'greeting' => 'mrs. Walker',
            'relation_about' => 'Test With relation',
            'relation_about_first_name' => 'Test',
            'relation_about_last_name' => 'With relation',
            'relation_about_full_name' => 'Mr. Test With relation',
            'relation_about_dear' => 'Dear mr. With relation',
            'relation_about_greeting' => 'mr. With relation',
            'relation_field_name' => 'relation',
        ]);
        $this->assertTokenMailFields($token30->getTokenId(), [
            'dear' => 'Beste mevrouw Walker',
            'name' => 'Janet Walker',
            'last_name' => 'Walker',
            'full_name' => 'Mevrouw Walker',
            'greeting' => 'mevrouw Walker',
            'relation_about' => 'Other org with Relation',
            'relation_about_first_name' => 'Other org',
            'relation_about_last_name' => 'with Relation',
            'relation_about_full_name' => 'De heer/Mevrouw Other org with Relation',
            'relation_about_dear' => 'Beste heer/mevrouw with Relation',
            'relation_about_greeting' => 'heer/mevrouw with Relation',
            'relation_field_name' => 'relation',
        ]);
        
        $this->assertTokenAddresses($jobData, $token10, 'test1@org.com', 'Test one', 'johnny@who.is', 'test1@org.com');
        $this->assertTokenAddresses($jobData, $token12, 'test1@org.com', 'Test one', 'janine@who.is', 'test1@org.com');
        $this->assertTokenAddresses($jobData, $token30, 'test2@org.com', 'Test two', 'janet@who.is', 'test2@org.com');
    }

    /**
     * Uses respondents only, no relations job 3
     */
    public function testRespondentsAllOnlyRespondentJobs()
    {
        $this->createRespondentTrack(10, 70, 1, 1, 10);
        $this->createRespondentTrack(20, 70, 1, 1);
        $this->createRespondentTrack(30, 71, 1, 1, 30);

        $jobData            = $this->commJobsUtil->getJob(3);
        $multipleTokensData = $this->commJobsUtil->getTokenData($jobData);

        // print_r($this->db->fetchAll("SELECT * FROM gems__tokens"));

        $this->assertCount(3, $multipleTokensData, 'We expected 3 tokens only for respondents, not relations.');
        
        $token10 = $this->assertTokenExists($multipleTokensData, 10, 20);
        $token20 = $this->assertTokenExists($multipleTokensData, 20, 20);
        $token30 = $this->assertTokenExists($multipleTokensData, 30, 20);

        $this->assertTokenMailFields($token10->getTokenId(), [
            'dear' => 'Dear mr. With relation',
            'name' => 'Test With relation',
            'last_name' => 'With relation',
            'full_name' => 'Mr. Test With relation',
            'greeting' => 'mr. With relation',
            'relation_about' => 'Test With relation',
            'relation_about_first_name' => 'Test',
            'relation_about_last_name' => 'With relation',
            'relation_about_full_name' => 'Mr. Test With relation',
            'relation_about_dear' => 'Dear mr. With relation',
            'relation_about_greeting' => 'mr. With relation',
            'relation_field_name' => '',
        ]);
        $this->assertTokenMailFields($token20->getTokenId(), [
            'dear' => 'Dear mrs. No relation',
            'name' => 'Test No relation',
            'last_name' => 'No relation',
            'full_name' => 'Mrs. Test No relation',
            'greeting' => 'mrs. No relation',
            'relation_about' => 'Test No relation',
            'relation_about_first_name' => 'Test',
            'relation_about_last_name' => 'No relation',
            'relation_about_full_name' => 'Mrs. Test No relation',
            'relation_about_dear' => 'Dear mrs. No relation',
            'relation_about_greeting' => 'mrs. No relation',
            'relation_field_name' => '',
        ]);
        $this->assertTokenMailFields($token30->getTokenId(), [
            'dear' => 'Beste heer/mevrouw with Relation',
            'name' => 'Other org with Relation',
            'last_name' => 'with Relation',
            'full_name' => 'De heer/Mevrouw Other org with Relation',
            'greeting' => 'heer/mevrouw with Relation',
            'relation_about' => 'Other org with Relation',
            'relation_about_first_name' => 'Other org',
            'relation_about_last_name' => 'with Relation',
            'relation_about_full_name' => 'De heer/Mevrouw Other org with Relation',
            'relation_about_dear' => 'Beste heer/mevrouw with Relation',
            'relation_about_greeting' => 'heer/mevrouw with Relation',
            'relation_field_name' => '',
        ]);
        
        $this->assertTokenAddresses($jobData, $token10, 'test1@org.com', 'Test one', 'a123@test.nl', 'test1@org.com');
        $this->assertTokenAddresses($jobData, $token20, 'test1@org.com', 'Test one', 'b123@test.nl', 'test1@org.com');
        $this->assertTokenAddresses($jobData, $token30, 'test2@org.com', 'Test two', 'c2345@test.nl', 'test2@org.com');
    }
}