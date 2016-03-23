<?php
require_once 'ControllerTestAbstract.php';

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Util
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Test class for Gems_Util
 *
 * As this class depends on all sorts of stuff being loaded we extend the IndexControllerTest
 *
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_UtilTest extends \Gems_Test_TestAbstract
{
    /**
     * @var Gems_Util
     */
    protected $object;

    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    public function setUp()
    {
        parent::setUp();

        //Now load the object we are going to test
        $settings = new \Zend_Config_Ini(GEMS_ROOT_DIR . '/configs/project.example.ini', APPLICATION_ENV);
        $settings = $settings->toArray();
        $settings['salt'] = 'vadf2646fakjndkjn24656452vqk';
        $project = new \Gems_Project_ProjectSettings($settings);
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

        //Check for incorrect spelling used before 1.5.2
        $project->concentRejected = 'test2';
        try {
            $actual   = $this->object->getConsentRejected();
        } catch (Exception $e) {
        }
        $this->assertInstanceOf('Gems_Exception_Coding', $e, 'No failure on misspelled concentRejected in project.ini');
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