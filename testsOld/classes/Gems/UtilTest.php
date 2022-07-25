<?php

/**
 * Description of \Gems\UtilTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
namespace Gems;

class UtilTest extends \Gems\Test\TestAbstract
{
    /**
     * @var \Gems\Util
     */
    protected $object;

    /**
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    public function setUp()
    {
        parent::setUp();

        //Now load the object we are going to test
        $settings = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/project.example.ini', APPLICATION_ENV);
        $settings = $settings->toArray();
        $settings['salt'] = 'vadf2646fakjndkjn24656452vqk';
        $project = new \Gems\Project\ProjectSettings($settings);
        $this->project = $project;
        $this->loader->addRegistryContainer(array('project' => $project));
        $this->object = $this->loader->getUtil();
    }

    /**
     * @dataProvider IPTestDataProvider
     */
    public function testAllowedIP($result, $ip, $ranges)
    {
        $this->assertEquals($result, $this->object->isAllowedIP($ip, $ranges));
    }

    public function testAllowedIPEmptyRange()
    {
        $this->assertTrue($this->object->isAllowedIP('127.0.0.1', ''));
    }

    public function testConsentTypes()
    {
        //check the ini file to be the default
        $expected = array(
            'do not use'    => 'do not use',
            'consent given' => 'consent given'
        );
        $actual         = $this->object->getConsentTypes();
        $this->assertEquals($expected, $actual);

        //Check if we can read from an altered ini file
        $project  = $this->project;
        $project->consentTypes = 'test|test2|test3';
        $expected = array(
            'test'  => 'test',
            'test2' => 'test2',
            'test3' => 'test3',
        );
        $actual = $this->object->getConsentTypes();
        $this->assertEquals($expected, $actual);

        //Check for class default when not found in ini
        unset($project->consentTypes);
        $expected = array(
            'do not use'    => 'do not use',
            'consent given' => 'consent given'
        );
        $actual         = $this->object->getConsentTypes();
        $this->assertEquals($expected, $actual);
    }

    public function testConsentRejected()
    {
        //Check the ini default
        $expected = 'do not use';
        $actual   = $this->object->getConsentRejected();
        $this->assertEquals($expected, $actual);

        //Check if we can read from an altered ini file
        $project  = $this->project;
        $expected = 'test';
        $project->consentRejected = $expected;
        $actual   = $this->object->getConsentRejected();
        $this->assertEquals($expected, $actual);

        //Check for class default when not found in ini
        unset($project->consentRejected);
        $expected = 'do not use';
        $actual   = $this->object->getConsentRejected();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    public function ipTestDataProvider()
    {
        return array(
            // Old tests
            array(true, '10.0.0.1', '10.0.0.0-10.0.0.255'),
            array(false, '10.0.1.1', '10.0.0.0-10.0.0.255'),
            array(true, '127.0.0.1', '127.0.0.1'),
            array(false, '127.0.0.1', '192.168.0.1'),
            array(true, '127.0.0.1', '192.168.0.1|127.0.0.1'),

            // New tests
            array(true, '10.0.1.0', '10.0.1.0'),
            array(true, '10.0.2.15', '10.0.1.0-10.0.3.255'),
            array(false, '10.0.4.1', '10.0.1.0-10.0.3.255'),
        );
    }
}