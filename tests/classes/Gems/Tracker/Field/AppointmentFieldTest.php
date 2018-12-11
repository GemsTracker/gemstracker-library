<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Tracker\Field;

use Gems\Agenda\AppointmentFilterInterface;

/**
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 27-Nov-2018 18:44:41
 */
class AppointmentFieldTest extends \Gems_Test_DbTestAbstract
{
    /**
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     * @var \Gems_Tracker_TrackerInterface
     */
    protected $tracker;

    /**
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $engine;

    private function _toDayString($value)
    {
        if ($value instanceof \MUtil_Date) {
            $value = $value->getDateTime();
        }
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    public function assertSameDay($value1, $value2)
    {
        $this->assertEquals($this->_toDayString($value1), $this->_toDayString($value2));
    }
    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        $testCase = $this->getName();
        $testFile =  str_replace('.php', "_$testCase.yml", __FILE__);
        if (file_exists($testFile)) {
            return $this->loadRelativeYaml($testFile);
        }

        //Dataset className.yml has the minimal data we need to perform our tests
        $classFile =  str_replace('.php', '.yml', __FILE__);
        return $this->loadRelativeYaml($classFile);
    }

    protected function loadRelativeYaml($filename)
    {
        $lastMonth = new \DateTime('first day of last month');
        $thisMonth = new \DateTime('first day of this month');
        $nextMonth = new \DateTime('first day of next month');
        $replacements = [
            '{LAST_MONTH}' => $lastMonth->format('Y-m'),
            '{THIS_MONTH}' => $thisMonth->format('Y-m'),
            '{NEXT_MONTH}' => $nextMonth->format('Y-m'),
        ];

        if (file_exists($filename)) {
            // echo "\n$filename\n";
            $content = str_replace(array_keys($replacements), $replacements, file_get_contents($filename));
            // echo "\n$content\n";
        } else {
            $content = '';
        }

        $contentBase = file_get_contents(str_replace('.php', "Base.yml", __FILE__));

        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($content . "\n" . $contentBase);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // \Zend_Application: loads the autoloader
        require_once 'Zend/Application.php';

        $iniFile = APPLICATION_PATH . '/configs/application.example.ini';

        // Use a database, can be empty but this speeds up testing a lot
        $config = new \Zend_Config_Ini($iniFile, 'testing', true);
        $config->merge(new \Zend_Config([
            'resources' => [
                'db' => [
                    'adapter' => 'Pdo_Sqlite',
                    'params'  => [
                        'dbname'   => ':memory:',
                        'username' => 'test'
                    ]
                ]
            ]
        ]));

        // Add our test loader dirs
        $dirs               = $config->loaderDirs->toArray();
        $config->loaderDirs = [GEMS_PROJECT_NAME_UC => GEMS_TEST_DIR . "/classes/" . GEMS_PROJECT_NAME_UC] +
                $dirs;

        // Create application, bootstrap, and run
        $application = new \Zend_Application(APPLICATION_ENV, $config);

        $this->bootstrap = $application;

        parent::setUp();

        $this->bootstrap->bootstrap('db');
        $this->bootstrap->getBootstrap()->getContainer()->db = $this->db;

        //*
        $this->bootstrap->bootstrap('cache');
        $this->bootstrap->getBootstrap()->getContainer()->cache = \Zend_Cache::factory(
                'Core',
                'Static',
                ['caching' => false],
                ['disable_caching' => true]
                ); // */

        $this->bootstrap->bootstrap();

        \Zend_Registry::set('db', $this->db);
        \Zend_Db_Table::setDefaultAdapter($this->db);

        $this->loader = $this->bootstrap->getBootstrap()->getResource('loader');

        $this->tracker = $this->loader->getTracker();
        $this->agenda  = $this->loader->getAgenda();

         // Now set some defaults
        $dateFormOptions['dateFormat']   = 'dd-MM-yyyy';
        $datetimeFormOptions['dateFormat']   = 'dd-MM-yyyy HH:mm';
        $timeFormOptions['dateFormat']   = 'HH:mm';

        \MUtil_Model_Bridge_FormBridge::setFixedOptions(array(
            'date'     => $dateFormOptions,
            'datetime' => $datetimeFormOptions,
            'time'     => $timeFormOptions,
            ));
    }

    public function testAppointmentTrackAssignment()
    {
        $appointment = $this->agenda->getAppointment(1);
        $nextMonth   = new \DateTime('first day of next month');
        $respTrack   = $this->tracker->getRespondentTrack(1);
        $token       = $respTrack->getFirstToken();

        $input = $respTrack->getFieldData();
        $this->assertNull($input['testApp']);
        $this->assertNull($token->getValidFrom());

        $output = $respTrack->setFieldData(['testApp' => $appointment->getId()]);
        // echo "\n" . print_r($output, true) . "\n";
        $this->assertEquals($output['testApp'], $appointment->getId());


        $respTrack->checkTrackTokens(1);
        $this->assertSameDay($appointment->getAdmissionTime(), $token->getValidFrom());
        $this->assertSameDay($appointment->getAdmissionTime()->addMonth(1), $token->getValidUntil());
    }

    public function testAppointmentTrackAutoAssignment()
    {
        $nextMonth   = new \DateTime('first day of next month');
        $respTrack   = $this->tracker->getRespondentTrack(1);
        $token       = $respTrack->getFirstToken();

        $input = $respTrack->getFieldData();
        $this->assertNull($input['testApp']);
        $this->assertNull($token->getValidFrom());

        $model = $this->loader->getModels()->createAppointmentModel();
        $model->save([
            'gap_id_user' => 1,
            'gap_id_organization' => 1,
            'gap_manual_edit' => 0,
            'gap_code' => 'A',
            'gap_status' => 'AC',
            'gap_admission_time' => new \MUtil_Date($nextMonth),
            'gap_id_attended_by' => 1,
            'gap_id_referred_by' => 1,
            'gap_id_activity' => 1,
            'gap_id_procedure' => 1,
            'gap_id_location' => 1,
            'gap_subject' => 'Do trigger this',
        ]);
        // echo "\n" . print_r($respTrack->getFieldData(), true) . "\n";

        $appointment = $this->agenda->getAppointment(1);
        $this->assertSameDay($nextMonth, $appointment->getAdmissionTime());
        $this->assertSameDay($appointment->getAdmissionTime(), $token->getValidFrom());
        $this->assertSameDay($appointment->getAdmissionTime()->addMonth(1), $token->getValidUntil());
    }

    public function testAppointmentTrackNotAssignment()
    {
        $nextMonth   = new \DateTime('first day of next month');
        $respTrack   = $this->tracker->getRespondentTrack(1);
        $token       = $respTrack->getFirstToken();

        $input = $respTrack->getFieldData();
        $this->assertNull($input['testApp']);
        $this->assertNull($token->getValidFrom());

        $model = $this->loader->getModels()->createAppointmentModel();
        $model->save([
            'gap_id_user' => 1,
            'gap_id_organization' => 1,
            'gap_manual_edit' => 0,
            'gap_code' => 'A',
            'gap_status' => 'AC',
            'gap_admission_time' => new \MUtil_Date($nextMonth),
            'gap_id_attended_by' => 1,
            'gap_id_referred_by' => 1,
            'gap_id_activity' => 1,
            'gap_id_procedure' => 1,
            'gap_id_location' => 1,
            'gap_subject' => 'Do not trig this',
        ]);
        // echo "\n" . print_r($respTrack->getFieldData(), true) . "\n";

        $appointment = $this->agenda->getAppointment(1);
        $this->assertSameDay($nextMonth, $appointment->getAdmissionTime());
        $this->assertNull($token->getValidFrom());
        $this->assertNull($token->getValidUntil());
    }

    public function testAppointmentTrackCreate()
    {
        $nextMonth     = new \DateTime('first day of next month');
        $preRespTracks = $this->tracker->getRespondentTracks(1, 1);
        $this->assertEquals(0, count($preRespTracks));

        $model = $this->loader->getModels()->createAppointmentModel();
        $model->save([
            'gap_id_user' => 1,
            'gap_id_organization' => 1,
            'gap_manual_edit' => 0,
            'gap_code' => 'A',
            'gap_status' => 'AC',
            'gap_admission_time' => new \MUtil_Date($nextMonth),
            'gap_id_attended_by' => 1,
            'gap_id_referred_by' => 1,
            'gap_id_activity' => 1,
            'gap_id_procedure' => 1,
            'gap_id_location' => 1,
            'gap_subject' => 'Do trigger this',
        ]);
        // $respTrack->checkTrackTokens(1);
        // echo "\n" . print_r($respTrack->getFieldData(), true) . "\n";
        // echo "\n" . print_r($app, true) . "\n";

        $respTracks = $this->tracker->getRespondentTracks(1, 1);
        $this->assertEquals(1, count($respTracks));

        $appointment = $this->agenda->getAppointment(1);
        $respTrack   = reset($respTracks);
        $token       = $respTrack->getFirstToken();

        $this->assertSameDay($nextMonth, $appointment->getAdmissionTime());
        $this->assertSameDay($appointment->getAdmissionTime(), $token->getValidFrom());
        $this->assertSameDay($appointment->getAdmissionTime()->addMonth(1), $token->getValidUntil());
    }
}
