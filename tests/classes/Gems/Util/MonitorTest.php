<?php

namespace Gems\Util;

/**
 * Description of MonitorTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class MonitorTest extends \Gems_Test_DbTestAbstract
{

    /**
     *
     * @var \Gems\Util\Monitor
     */
    protected $object;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    public function setUp()
    {
        parent::setUp();

        $this->object = $this->loader->getUtil()->getMonitor();

        $settings         = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/project.example.ini', APPLICATION_ENV);
        $settings         = $settings->toArray();
        $settings['salt'] = 'vadf2646fakjndkjn24656452vqk';
        $project          = new \Gems_Project_ProjectSettings($settings);
        $this->project    = $project;

        $this->util = $this->loader->getUtil();
        $cache      = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        $roles      = new \Gems_Roles($cache);
        $acl        = $roles->getAcl();

        $dbLookup = $this->util->getDbLookup();
        $dbLookup->answerRegistryRequest('acl', $acl);
        $this->object->answerRegistryRequest('util', $this->util);
        $this->object->answerRegistryRequest('project', $project);

        // Get the mail system to write to file instead of sending an email
        $options = array(
            'path'     => GEMS_TEST_DIR . '/tmp',
            'callback' => array($this, '_getFileName')
        );

        $this->transport = new \Zend_Mail_Transport_File($options);
        \Zend_Mail::setDefaultTransport($this->transport);
        
        // Make sure the lock file can be written, not a problem outside test situations
        \MUtil_File::ensureDir(GEMS_ROOT_DIR . '/var/settings');
    }
    
    public static function tearDownAfterClass()
    {
        // Now we cleanup the mess we made so we don't harm other tests
        // Since we are in a static method we can not use this to acces the objects
        $path = GEMS_ROOT_DIR . '/var/settings/';
        $files = ['lock.txt', 'monitor.json'];
        foreach ($files as $file) {
            if (file_exists($path . $file)) { 
                unlink($path . $file);
            }
        }        
    }

    /**
     * Return filename to use for writing emails for this test
     * to the path <testdir>/tmp
     */
    public function _getFileName()
    {
        return 'monitorTest';
    }

    /**
     * When lock file is already present, should not return true
     */
    public function testMaintenanceModeTrue()
    {
        $lock = $this->util->getMaintenanceLock();
        $lock->lock();

        $result = $this->object->reverseMaintenanceMonitor();
        $this->assertFalse($result);
    }

    /**
     * When lock file is not present, should return true
     */
    public function testMaintenanceModeFalse()
    {
        $lock = $this->util->getMaintenanceLock();
        $lock->unlock();

        $result = $this->object->reverseMaintenanceMonitor();
        $this->assertTrue($result);
    }
    
    public function testUsersMaintenance() {
        $lock = $this->util->getMaintenanceLock();
        $lock->unlock();

        $this->object->reverseMaintenanceMonitor();
        $job      = $this->object->getReverseMaintenanceMonitor();
        $data     = $job->getArrayCopy();
        $actual   = $data['to'];
        $expected = [1 => 'test@gemstracker.org'];
        // Cleanup
        $lock->unlock();
        $this->assertEquals($expected, $actual);
    }
    
    public function testUsersCron() {
        $this->object->startCronMailMonitor();
        $job      = $this->object->getCronMailMonitor();
        $data     = $job->getArrayCopy();
        $actual   = $data['to'];
        $expected = [1 => 'test2@gemstracker.org'];
        // Cleanup
        $job->stop();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        //Dataset TokenTest.xml has the minimal data we need to perform our tests
        $classFile = str_replace('.php', '.xml', __FILE__);
        return $this->createFlatXMLDataSet($classFile);
    }

}
