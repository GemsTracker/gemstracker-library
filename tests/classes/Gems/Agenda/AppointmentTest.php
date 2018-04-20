<?php
/**
 * Description of AppointmentTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class AppointmentTest  extends PHPUnit_Framework_TestCase {

    protected $appointment;
    
    public function setUp() {
        parent::setUp();
        
        $this->appointment = new \Gems_Agenda_Appointment(1);
    }
    
    protected function _checkCreate($type, $methodAsked, $expected, $filter = null, $existingTracks = []) {
        if (is_null($filter)) {
            $filter = new \Gems\Agenda\Filter\SubjectAppointmentFilter();
            $filter->exchangeArray(['gtap_create_track'=>$type]);
        }
        
        $this->assertEquals($methodAsked, $this->appointment->getCreatorCheckMethod($type));
        $this->assertEquals($expected, $this->appointment->$methodAsked($filter, $existingTracks));
    }
    
    public function testCreateAlways()
    {
        $this->_checkCreate(2, 'createAlways', true);
    }
    
    public function testCreateNever()
    {
        $this->_checkCreate(0, 'createNever', false);        
    }
    
    public function testCreateWhenNoOpen_NoTrack()
    {
        // When no track exists
        $this->_checkCreate(1, 'createWhenNoOpen', true);
    }
    
    public function testCreateWhenNoOpen_OtherTrackId()
    {
        // When no track exists
        $this->_checkCreate(1, 'createWhenNoOpen', true);
        
        // When track exists, but for a different id
        $existingTracks[9][] = 1;
        $filter = new \Gems\Agenda\Filter\SubjectAppointmentFilter();
        $filter->exchangeArray([
            'gtap_create_track'=>1,
            'gtap_id_track' => 2
            ]);
        
        $this->assertEquals(true, $this->appointment->createWhenNoOpen($filter, $existingTracks));        
    }
    
    public function testCreateWhenNoOpen_NoSuccess() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testCreateWhenNoOpen_NotOpen() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testCreateWhenNoOpen_NoWaitDays() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testCreateWhenNoOpen_NeEndDate() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testCreateWhenNoOpen_LessThanWaitDays() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testCreateWhenNoOpen_EqualsWaitDays() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testCreateWhenNoOpen_AlreadyAssgined() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testCreateWhenNoOpen_Create() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
