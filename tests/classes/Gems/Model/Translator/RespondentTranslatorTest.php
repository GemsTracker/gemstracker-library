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
class RespondentTranslatorTest extends ControllerTestAbstract
{

    /**
     * @var \Gems_Model_Translator_AppointmentTranslator
     */
    protected $object;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    public function setUp()
    {
        $this->setPath(GEMS_TEST_DIR . '/data/model');
        parent::setUp();
        $this->fixUser();

        $this->loader = $this->bootstrap->getBootstrap()->loader;

        \Zend_Registry::set('loader', $this->loader);

        $importLoader      = $this->loader->getImportLoader();
        $defaultTranslator = $importLoader->getDefaultTranslator('respondent');
        $translators       = $importLoader->getTranslators('respondent');
        $object            = $translators[$defaultTranslator];
        $object->answerRegistryRequest('loader', $this->loader);
        $object->afterRegistry();

        $object->setTargetModel($this->loader->getModels()->getRespondentModel(false));

        $this->object = $object;
    }

    protected function fixUser()
    {
        // Fix user
        $currentUser = $this->getMockBuilder('Gems_User_User')
                ->disableOriginalConstructor()
                ->getMock();
        $currentUser->expects($this->any())
                ->method('getGroup')
                ->will($this->returnValue(1));
        $currentUser->expects($this->any())
                ->method('getUserId')
                ->will($this->returnValue(1));

        \GemsEscort::getInstance()->currentUser = $currentUser;
        $this->currentUser                      = $currentUser;
    }

    /**
     * 
     * @param type $input
     * @param array $expected
     * @dataProvider testEmailProvider
     */
    public function testEmail($input, $expected)
    {
        $data     = [$input];
        $expected = [$expected];
        $actual = $this->object->translateImport($data);

        $this->assertEquals($expected, $actual);
    }

    public function testEmailProvider()
    {
        return [ 'normal' =>
            [
                [
                    'gr2o_patient_nr'      => '123456',
                    'gr2o_id_organization' => 70,
                    'grs_email'            => 'abc@def.com'
                ],
                [
                    'gr2o_patient_nr'      => '123456',
                    'gr2o_email'           => 'abc@def.com',
                    'gr2o_id_organization' => 70,
                    'grs_email'            => 'abc@def.com'
                ]
            ], 'empty' =>
            [
                [
                    'gr2o_patient_nr'      => '1234567',
                    'gr2o_id_organization' => 70,
                    'grs_email'            => ''
                ],
                [
                    'gr2o_patient_nr'      => '1234567',
                    'gr2o_email'           => '',
                    'gr2o_id_organization' => 70,
                    'grs_email'            => '',
                    'calc_email'           => 1
                ]
            ], 'null' => 
            [
                [
                    'gr2o_patient_nr'      => '12345678',
                    'gr2o_id_organization' => 70,
                    'grs_email'            => null
                ],
                [
                    'gr2o_patient_nr'      => '12345678',
                    'gr2o_email'           => null,
                    'gr2o_id_organization' => 70,
                    'grs_email'            => null,
                    'calc_email'           => 1
                ]
            ],
        ];
    }

}
