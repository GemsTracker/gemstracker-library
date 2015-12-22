<?php
class Gems_Tracker_RespondentTrackTest extends Gems_Test_DbTestAbstract
{
    /**
     * @var Gems_Tracker_TrackerInterface
     */
    protected $tracker;

    /**
     * @var Gems_Tracker_Engine_TrackEngineInterface
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

        MUtil_Model_Bridge_FormBridge::setFixedOptions(array(
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
        
        $this->assertEquals($expected, $this->tracker->getTrackEngine(1)->getFields());
    }

    public function testGetFields()
    {
        $trackData = array('gr2t_id_respondent_track' => 1, 'gr2t_id_track' => 1);
        $respondentTrack = new Gems_Tracker_RespondentTrack($trackData);
        $respondentTrack->answerRegistryRequest('tracker', $this->tracker);
        $date = new MUtil_Date('2010-10-08', 'yyyy-MM-dd');
        $expected = array(
            'f__1' => 'test',
            'code' => 'test',
            'f__2' => $date,
            'datecode' => $date
            );
        $this->assertEquals($expected, $respondentTrack->getFieldData());
    }
    
    /**
     * Test if settings fields via code works
     */
    public function testSetFields()
    {
        $trackData = array('gr2t_id_respondent_track' => 1, 'gr2t_id_track' => 1);
        $respondentTrack = new Gems_Tracker_RespondentTrack($trackData);
        $respondentTrack->answerRegistryRequest('tracker', $this->tracker);
        $expected = $respondentTrack->getFieldData();
        $date = new MUtil_Date('2010-11-09', 'yyyy-MM-dd');
        $expected = array(
            'f__1' => 'newvalue',
            'code' => 'newvalue',
            'f__2' => $date,
            'datecode' => $date
            );
        $this->assertEquals($expected, $respondentTrack->setFieldData(array('code' => 'newvalue', 'datecode' => $date)));
    }
    
    /**
     * When saving a date by using a string, it should work too
     */
    public function testSetDateFields()
    {
        $trackData = array('gr2t_id_respondent_track' => 1, 'gr2t_id_track' => 1);
        $respondentTrack = new Gems_Tracker_RespondentTrack($trackData);
        $respondentTrack->answerRegistryRequest('tracker', $this->tracker);
        $expected = $respondentTrack->getFieldData();
        $date = new MUtil_Date('2010-11-09', 'yyyy-MM-dd');
        $expected = array(
            'f__1' => 'newvalue',
            'code' => 'newvalue',
            'f__2' => $date,
            'datecode' => $date
            );
        $this->assertEquals($expected, $respondentTrack->setFieldData(array('code' => 'newvalue', 'datecode' => $date->toString('yyy-MM-dd'))));
    }
    
    /**
     * When only providing one or two fields, the others should not get nulled
     */
    public function testSetFieldsPartial()
    {
        $trackData = array('gr2t_id_respondent_track' => 1, 'gr2t_id_track' => 1);
        $respondentTrack = new Gems_Tracker_RespondentTrack($trackData);
        $respondentTrack->answerRegistryRequest('tracker', $this->tracker);
        $expected = $respondentTrack->getFieldData();
        $expected['f__1'] = $expected['code'] = 'newvalue';
        $this->assertEquals($expected, $respondentTrack->setFieldData(array('code' => 'newvalue')));
    }
}