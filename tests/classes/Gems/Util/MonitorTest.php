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
