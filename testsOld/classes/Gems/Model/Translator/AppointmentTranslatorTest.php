<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Model\Translator;

use ControllerTestAbstract;

/**
 * Description of AppointmentTranslatorTest
 *
 * @author 175780
 */
class AppointmentTranslatorTest extends ControllerTestAbstract
{

    /**
     * @var \Gems\Model\Translator\AppointmentTranslator
     */
    protected $object;

    public function setUp()
    {
        $this->setPath(GEMS_TEST_DIR . '/data/model');
        parent::setUp();
        $this->fixUser();

        $this->loader = $this->bootstrap->getBootstrap()->loader;

        \Zend_Registry::set('loader', $this->loader);

        $importLoader      = $this->loader->getImportLoader();
        $defaultTranslator = $importLoader->getDefaultTranslator('calendar');
        $translators       = $importLoader->getTranslators('calendar');
        $object            = $translators[$defaultTranslator];
        $object->answerRegistryRequest('loader', $this->loader);
        $object->afterRegistry();

        $object->setTargetModel($this->loader->getModels()->createAppointmentModel());

        $this->object = $object;
    }

    protected function fixUser()
    {
        // Fix user
        $currentUser = $this->getMockBuilder('\\Gems\\User\\User')
                ->disableOriginalConstructor()
                ->getMock();
        $currentUser->expects($this->any())
                ->method('getGroup')
                ->will($this->returnValue(1));
        $currentUser->expects($this->any())
                ->method('getUserId')
                ->will($this->returnValue(1));

        \Gems\Escort::getInstance()->currentUser = $currentUser;
        $this->currentUser                      = $currentUser;
    }

    public function testFilter()
    {
        $data = [
            [
                'gap_id_in_source'    => '12345',
                'gap_id_user'         => 990001,
                'gap_attended_by'     => 'Include',
                'gap_referred_by'     => 'Include',
                'gap_id_organization' => 70
            ],
            [
                'gap_id_in_source'    => '12345',
                'gap_id_user'         => 990001,
                'gap_attended_by'     => 'Exclude',
                'gap_referred_by'     => 'Include',
                'gap_id_organization' => 70
            ],
        ];

        $actual   = $this->object->translateImport($data);

        $expected = [
            [
                'gap_id_organization'  => 70,
                'gap_id_in_source'     => '12345',
                'gas_name_attended_by' => 'Include',
                'gap_id_user'          => 990001,
                'gap_attended_by'      => 'Include',
                'gap_referred_by'     => 'Include',
                'gap_source'           => 'import',
                'gap_manual_edit'      => 0,
                'gap_id_attended_by'   => '1',
                'gas_name_referred_by' => 'Include',
                'gap_id_referred_by' => '1'
            ]
        ];

        $this->saveTables(['gems__agenda_staff'], 'AppointmentTranslatorTest');
        
        // This field does not count
        unset($actual[0]['gap_last_synch']);

        $this->assertEquals($expected, $actual);
    }

}
