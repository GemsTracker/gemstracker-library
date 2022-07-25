<?php
class RespondentTrackTest extends \Gems\Test\DbTestAbstract
{
    /**
     * @var \Gems\Tracker\TrackerInterface
     */
    protected $tracker;

    /**
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $engine;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->tracker = $this->loader->getTracker();
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
        $classFile =  str_replace('.php', '.yml', __FILE__);
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($classFile);
    }

    public function testGetCodeFields()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);

        $actual = $respondentTrack->getCodeFields();

        $date = new MUtil\Date('2010-10-08', 'yyyy-MM-dd');
        $expected = array(
            'code' => 'test',
            'datecode' => $date->toString('dd MMM yyyy'),
            'rel'  => 'Johnny Walker'
        );
        $this->assertEquals($expected, $actual);
    }

    public function testGetFieldCodes()
    {
        $expected = array(
            'f__1' => 'code',
            'f__2' => 'datecode',
            'f__5' => 'rel'
            );

        $this->assertEquals($expected, $this->tracker->getTrackEngine(1)->getFieldCodes());
    }

    public function testGetFields()
    {
        $trackData = array('gr2t_id_respondent_track' => 1, 'gr2t_id_track' => 1);
        $respondentTrack = new \Gems\Tracker\RespondentTrack($trackData);
        $respondentTrack->answerRegistryRequest('tracker', $this->tracker);
        $date = new MUtil\Date('2010-10-08', 'yyyy-MM-dd');
        $expected = array(
            'f__1' => 'test',
            'code' => 'test',
            'f__2' => $date,
            'datecode' => $date,
            'f__5' => 21,
            'rel'  => 21
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

        $date = new \MUtil\Date('2010-11-09', 'yyyy-MM-dd');
        $expected = array(
            'f__1' => 'newvalue',
            'code' => 'newvalue',
            'f__2' => $date,
            'datecode' => $date,
            'f__5' => 21,
            'rel'  => 21
            );
        $actual = $respondentTrack->setFieldData(array('code' => 'newvalue', 'datecode' => $date));

        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
    }
   
    /**
     * When saving a date by using a string, it should work too
     * 
     * @dataProvider dateStringProvider
     */
    public function testSetDateString($date, $expected)
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);

        $expectedResult = array(
            'f__1' => 'test',
            'code' => 'test',
            'f__2' => $expected,
            'datecode' => $expected,
            'f__5' => 21,
            'rel'  => 21
            );
        $actual = $respondentTrack->setFieldData(array('datecode' => $date));
        
        $this->assertArrayWithDateMatch($expectedResult, $actual, '', 1, 0);
    }
    
    public function dateStringProvider()
    {
        return [
            'date' => [
                '2019-03-22',
                new \MUtil\Date('2019-03-22')
            ],
            'datetime' => [
                '2019-03-23 15:45:59',
                new \MUtil\Date('2019-03-23')
            ],
            'dateshorttime' => [
                '2019-03-24 15:45',
                new \MUtil\Date('2019-03-24')
            ]
        ];
    }
    
    /**
     * We have an appointment field and a field that copies the date from the appointment
     * Setting through the normal interface should work just fine
     */
    public function testSetFieldsAppointment()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(2);
        $expected = $respondentTrack->getFieldData();
        $expected['a__1'] = 1;
        $expected['f__6'] = new \MUtil\Date('2017-10-01', 'yyyy-MM-dd');
        $expected['f__6__manual'] = 0;
        $actual   = $respondentTrack->setFieldData(array('a__1' => 1));

        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
    }
    
    /**
     * We have an appointment field and a field that copies the date from the appointment
     * Setting through the normal interface should work just fine
     */
    public function testSetFieldsAppointmentViaModel()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(2);
        $engine = $respondentTrack->getTrackEngine();
        $model = $this->loader->getTracker()->getRespondentTrackModel()->applyEditSettings($engine);
        $data = $model->loadFirst(['gr2t_id_track' => 2]);
        $data['a__1'] = 1;
        
        $result   = $model->save($data);
        $expected = '2017-10-01';
        $actual   = $result['f__6']->toString('yyyy-MM-dd');
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * We have an appointment field and a field that copies the date from the appointment
     * Setting through the normal interface should work just fine
     */
    public function testCreateTrackWithAppointmentViaModel()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(2);
        $engine = $respondentTrack->getTrackEngine();
        $model = $this->loader->getTracker()->getRespondentTrackModel()->applyEditSettings($engine);
        
        // New track
        $data = $model->loadNew();
        $data['gr2t_id_track'] = 2;
        $data['gr2t_id_user'] = '1234';
        $data['gr2t_id_organization'] = 1;
        $data['a__1'] = 1;
        
        $result   = $model->save($data);
        $expected = '2017-10-01';
        $actual   = $result['f__6']->toString('yyyy-MM-dd');
        $this->assertEquals($expected, $actual);    
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
    
    /**
     * What happens with a field with a default value, when we create without providing data?
     */
    public function testCreateTrackDefaultFields()
    {
        $trackData = [
            'gr2t_start_date' => new \MUtil\Date('2000-01-01')
            ];
        \MUtil\Batch\BatchAbstract::unload('tmptrack3');
        $respondentTrack = $this->loader->getTracker()->createRespondentTrack(1234, 1, 1, 1, $trackData);

        $actual = $expected = $respondentTrack->getFieldData();
        $expected['f__1'] = $expected['code'] = 'default';

        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
        \MUtil\Batch\BatchAbstract::unload('tmptack2');  // Make sure there are no leftovers
    }
    
    /**
     * We have an appointment field and a field that copies the date from the appointment
     * Setting through the normal interface should work just fine
     */
    public function testCreateTrackDefaultFieldsViaModel()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);
        $engine = $respondentTrack->getTrackEngine();
        $model = $this->loader->getTracker()->getRespondentTrackModel()->applyEditSettings($engine);
        
        // New track
        $data = $model->loadNew();
        $data = [
            'gr2t_id_track'        => 1,
            'gr2t_id_user'         => '1234',
            'gr2t_id_organization' => 1,
            'gr2t_start_date'      => new \MUtil\Date('2000-01-01')
        ] + $data;
        
        $newData = $model->save($data);
        $expected['f__1'] = $expected['code'] = 'default';
        $actual = array_intersect_key($newData, $expected);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test if settings fields via code works
     */
    public function testSetFieldToEmptyWithCode()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);

        $expected = array(
            'f__1' => 'newvalue',
            'code' => 'newvalue',
            'f__2' => '',
            'datecode' => '',
            'f__5' => 21,
            'rel'  => 21
            );
        $actual = $respondentTrack->setFieldData(array('code' => 'newvalue', 'datecode' => null));
        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
    }
    
    /**
     * Test if settings fields via code works
     */
    public function testSetFieldToEmptyWithoutCode()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(2);

        $expected = array(
            'f__3' => 'newvalue',
            'f__4' => '',
            'f__6' => '',
            'a__1' => '',
            'f__6__manual' => '0',
            );
        $actual = $respondentTrack->setFieldData(array('f__3' => 'newvalue', 'f__4' => null));
        $this->assertArrayWithDateMatch($expected, $actual, '', 1, 0);
    }
    
    public function testGetTrackCode()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);
        $result = $respondentTrack->getCode();

        $this->assertEquals('test', $result);
    }

    public function testGetTrackName()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);
        $result = $respondentTrack->getTrackName();

        $this->assertEquals('Test Track', $result);
    }

    public function testGetTrackActive()
    {
        $respondentTrack = $this->loader->getTracker()->getRespondentTrack(1);
        $result = $respondentTrack->getTrackActive();

        $this->assertEquals(true, $result);
    }
}