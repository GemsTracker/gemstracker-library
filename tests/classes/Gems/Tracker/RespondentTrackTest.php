<?php
/**
 * MD 20151125 - This is a really messy test class to expose a problem and test the fix. Use and / or extend with care. 
 */

class containerStub {
    public function getEvents() {
        return array();
    }
}       

//include_once 'Gems/Tracker/Engine/FieldsDefinition.php';
//include_once 'Gems/Tracker/Field/FieldInterface.php';
//include_once 'Gems/Tracker/Field/FieldAbstract.php';
//include_once 'Gems/Tracker/Field/TextField.php';

//use Gems\Tracker\Engine\FieldsDefinition as FieldsDefinitionAlias;

class Gems_Tracker_RespondentTrackTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Gems_Tracker_TrackerInterface
     */
    protected $tracker;

    /**
     * @var Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $engine;

    public function __construct() {
        parent::__construct();
        
        $fieldsData = array('f__1' => 'test');        
        $fieldsArray = array('f__1' => 'codename');
        $s = new \Gems\Tracker\Engine\FieldsDefinition(1);
        $fieldsDefStub = $this->getMock('Gems\\Tracker\\Engine\\FieldsDefinition',
             array('getFieldCodes', 'saveFields'),
             array(1),
             'MockFieldsDef',
              true);
        
        $fieldsDefStub->expects($this->any())
            ->method('getFieldCodes')
            ->will($this->returnValue($fieldsArray));
        
        $fieldsDefStub->expects($this->any())
            ->method('saveFields')
            ->will($this->returnValue(1));
        
        $fieldsDefStub->answerRegistryRequest('_fields', array('f__1' => new Gems\Tracker\Field\TextField(1, 'f__1',
            array(
                'sub' => 'f',
                'gtf_id_field' => 1,
                'gtf_field_name' => 'Name',
                'gtf_field_code' => 'codename'
            )
            )));
        $fieldsDefStub->answerRegistryRequest('exists', true);


        // Create a stub for the tracker to return an engine
        $trackData = array('gtr_id_track' => 1);
        $engineStub = $this->getMock('Gems_Tracker_Engine_AnyStepEngine',
                     array('getFieldsData'),
                     array($trackData),
                     'MockEngine',
                      true);

        $engineStub->expects($this->any())
            ->method('getFieldsData')
            ->will($this->returnValue($fieldsData));
        
        $engineStub->answerRegistryRequest('_fieldsDefinition', $fieldsDefStub);

        // Create a stub for the tracker to return an engine
        
        $container = new stdClass();
        $container->loader = new containerStub();
        $trackerStub = $this->getMock('Gems_Tracker',
                     array('getTrackEngine'),
                     array($container, array()),
                     'MockTracker',
                     true);

        $trackerStub->expects($this->any())
                ->method('getTrackEngine')
                ->will($this->returnValue($engineStub));

        $this->tracker = $trackerStub;
    }
    
    public function testGetFieldCodes()
    {
        $expected = array(
            'f__1' => 'codename'
            );
        
        $this->assertEquals($expected, $this->tracker->getTrackEngine(1)->getFields());
    }

    public function testGetFields()
    {
        $trackData = array('gr2t_id_respondent_track' => 1, 'gr2t_id_track' => 1);
        $respondentTrack = new Gems_Tracker_RespondentTrack($trackData);
        $respondentTrack->answerRegistryRequest('tracker', $this->tracker);
        $expected = array(
            'f__1' => 'test',
            'codename' => 'test'
            );
        $this->assertEquals($expected, $respondentTrack->getFieldData());
    }
    
    public function testSetFields()
    {
        $trackData = array('gr2t_id_respondent_track' => 1, 'gr2t_id_track' => 1);
        $respondentTrack = new Gems_Tracker_RespondentTrack($trackData);
        $respondentTrack->answerRegistryRequest('tracker', $this->tracker);
        $expected = $respondentTrack->getFieldData();
        foreach($expected as $key => &$value) {
            $value = 'newvalue';
        }
        $this->assertEquals($expected, $respondentTrack->setFieldData(array('codename' => 'newvalue')));
    }
}