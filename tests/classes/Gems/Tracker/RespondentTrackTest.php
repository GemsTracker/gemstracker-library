<?php
class Gems_Tracker_RespondentTrackTest extends \Gems_Test_DbTestAbstract
{
    /**
     * @var \Gems_Tracker_TrackerInterface
     */
    protected $tracker;

    /**
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $engine;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // \Zend_Application: loads the autoloader
        require_once 'Zend/Application.php';

        // Create application, bootstrap, and run
        $application = new \Zend_Application(
            APPLICATION_ENV,
            GEMS_ROOT_DIR . '/configs/application.example.ini'
        );

        $this->bootstrap = $application;

        include_once GEMS_TEST_DIR . '/library/Gems/Cookies.php';   // Dirty fix for cookie problem during tests

        parent::setUp();

        $this->bootstrap->bootstrap('db');
        $this->bootstrap->getBootstrap()->getContainer()->db = $this->db;

        $this->bootstrap->bootstrap();

        \Zend_Registry::set('db', $this->db);
        \Zend_Db_Table::setDefaultAdapter($this->db);

        $this->loader = $this->bootstrap->getBootstrap()->getResource('loader');

        $this->tracker = $this->loader->getTracker();

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

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Assert that two arrays match and allow a diff in seconds for the date(time) elements and another one for the others
     *
     * @param mixed   $expected
     * @param mixed   $actual
     * @param string  $message
     * @param integer $deltaSeconds
     * @param integer $deltaGlobal
     */
    protected function assertArrayWithDateMatch($expected, $actual, $message, $deltaSeconds = 0, $deltaGlobal = 0)
    {
        // First sort both arrays and see if the keys match
        ksort($expected);
        ksort($actual);
        $this->assertEquals(array_keys($expected), array_keys($actual), $message);

        // If this worked, we can check all elements and make sure we allow a delta in seconds if provided
        foreach ($expected as $key => $value) {
            if (($value instanceof \Zend_Date) && ($actual[$key] instanceof \Zend_Date)) {
                $this->assertEquals(0, $expected[$key]->diffSeconds($actual[$key]), $message, $deltaSeconds);
            } else {
                $this->assertEquals($expected[$key], $actual[$key], $message, $deltaGlobal);
            }
        }
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        //Dataset TokenTest.xml has the minimal data we need to perform our tests
        $classFile =  str_replace('.php', '.xml', __FILE__);
        return $this->createFlatXMLDataSet($classFile);
    }

    public function testGetFieldCodes()
    {
        $expected = array(
            'f__1' => 'code',
            'f__2' => 'datecode'
            );

        $this->assertEquals($expected, $this->tracker->getTrackEngine(1)->getFieldCodes());
    }

    public function testGetFields()
    {
        $trackData = array('gr2t_id_respondent_track' => 1, 'gr2t_id_track' => 1);
        $respondentTrack = new \Gems_Tracker_RespondentTrack($trackData);
        $respondentTrack->answerRegistryRequest('tracker', $this->tracker);
        $date = new MUtil_Date('2010-10-08', 'yyyy-MM-dd');
        $expected = array(
            'f__1' => 'test',
            'code' => 'test',
            'f__2' => $date,
            'datecode' => $date
            );
        $actual = $respondentTrack->getFieldData();

        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
    }

    /**
     * Test if settings fields via code works
     */
    public function testSetFields()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);

        $date = new \MUtil_Date('2010-11-09', 'yyyy-MM-dd');
        $expected = array(
            'f__1' => 'newvalue',
            'code' => 'newvalue',
            'f__2' => $date,
            'datecode' => $date
            );
        $actual = $respondentTrack->setFieldData(array('code' => 'newvalue', 'datecode' => $date));

        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
    }

    /**
     * When saving a date by using a string, it should work too
     */
    public function testSetDateFields()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);

        // $expected = $respondentTrack->getFieldData();
        $date = new \MUtil_Date('2010-11-09', 'yyyy-MM-dd');
        $expected = array(
            'f__1' => 'newvalue',
            'code' => 'newvalue',
            'f__2' => $date,
            'datecode' => $date
            );
        $actual = $respondentTrack->setFieldData(array('code' => 'newvalue', 'datecode' => $date->toString('yyyy-MM-dd')));

        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
    }

    /**
     * When only providing one or two fields, the others should not get nulled
     */
    public function testSetFieldsPartial()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);

        $expected = $respondentTrack->getFieldData();
        $expected['f__1'] = $expected['code'] = 'newvalue';
        $actual = $respondentTrack->setFieldData(array('code' => 'newvalue'));

        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
    }
}