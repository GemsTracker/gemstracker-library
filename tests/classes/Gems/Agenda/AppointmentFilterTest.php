<?php

/**
 *
 * @package    Gemstracker
 * @subpackage AppointmentFilterTest
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

use Gems\Agenda\AppointmentFilterInterface;

/**
 *
 * @package    Gemstracker
 * @subpackage AppointmentFilterTest
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 24-Oct-2018 11:03:27
 */
class AppointmentFilterTest extends \Gems_Test_DbTestAbstract
{
    /**
     *
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Model_AppointmentModel
     */
    protected $model;

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        $testCase = $this->getName();
        $testFile =  str_replace('.php', "_$testCase.yml", __FILE__);
        if (file_exists($testFile)) {
            return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($testFile);
        }
        
        //Dataset className.yml has the minimal data we need to perform our tests
        $classFile =  str_replace('.php', '.yml', __FILE__);
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($classFile);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // \Zend_Application: loads the autoloader
        require_once 'Zend/Application.php';

        $iniFile = APPLICATION_PATH . '/configs/application.example.ini';

        // Use a database, can be empty but this speeds up testing a lot
        $config = new Zend_Config_Ini($iniFile, 'testing', true);
        $config->merge(new Zend_Config([
            'resources' => [
                'db' => [
                    'adapter' => 'Pdo_Sqlite',
                    'params'  => [
                        'dbname'   => ':memory:',
                        'username' => 'test'
                    ]
                ]
            ]
        ]));

        // Add our test loader dirs
        $dirs               = $config->loaderDirs->toArray();
        $config->loaderDirs = [GEMS_PROJECT_NAME_UC => GEMS_TEST_DIR . "/classes/" . GEMS_PROJECT_NAME_UC] +
                $dirs;

        // Create application, bootstrap, and run
        $application = new Zend_Application(APPLICATION_ENV, $config);

        $this->bootstrap = $application;

        parent::setUp();

        $this->bootstrap->bootstrap('db');
        $this->bootstrap->getBootstrap()->getContainer()->db = $this->db;

        // Remove
        $this->bootstrap->bootstrap('cache');
        $this->bootstrap->getBootstrap()->getContainer()->cache = \Zend_Cache::factory(
                'Core',
                'Static',
                ['caching' => false],
                ['disable_caching' => true]
                );

        $this->bootstrap->bootstrap();

        \Zend_Registry::set('db', $this->db);
        \Zend_Db_Table::setDefaultAdapter($this->db);

        $this->loader = $this->bootstrap->getBootstrap()->getResource('loader');
        $this->agenda = $this->loader->getAgenda();
        $this->model  = $this->loader->getModels()->createAppointmentModel();

         // Now set some defaults
        $dateFormOptions['dateFormat']   = 'dd-MM-yyyy';
        $datetimeFormOptions['dateFormat']   = 'dd-MM-yyyy HH:mm';
        $timeFormOptions['dateFormat']   = 'HH:mm';

        \MUtil_Model_Bridge_FormBridge::setFixedOptions(array(
            'date'     => $dateFormOptions,
            'datetime' => $datetimeFormOptions,
            'time'     => $timeFormOptions,
            ));
    }

    /**
     * General test database is loaded
     */
    public function testCountStaff()
    {
        $allStaff = $this->db->fetchAll("SELECT * FROM gems__agenda_staff");

        $this->assertEquals(count($allStaff), 1);
    }

    /**
     * Test location filters
     */
    public function testLocationFilters()
    {
        $allAppointments = $this->model->load();

        $expected = [
            1 => [1],
            2 => [2],
            3 => [1, 2],
        ];
        $results     = [];
        $testFilters = [];

        foreach ($allAppointments as $appointmentData) {
            $appointment = $this->agenda->getAppointment($appointmentData);
            $filters = $this->agenda->matchFilters($appointment);
            foreach ($filters as $filter) {
                if ($filter instanceof AppointmentFilterInterface) {
                    $filterId = $filter->getFilterId();
                    $results[$appointment->getId()][] = $filterId;
                    $testFilters[$filterId] = $filter;
                    $expected2[$filterId][] = $appointment->getId();
                }
            }
        }
        error_log(print_r($results, true));
        $this->assertEquals($expected, $results, 'Appointments match not equal to expected result for Locations.');

        $results2 = [];
        foreach ($testFilters as $filter) {
            if ($filter instanceof AppointmentFilterInterface) {
                $sql = "SELECT gap_id_appointment FROM gems__appointments WHERE " . $filter->getSqlWhere();
                $results2[$filter->getFilterId()] = $this->db->fetchCol($sql);
            }
        }
        $this->assertEquals($expected2, $results2, 'Appointment SQL not equal to appointment match for Locations.');
    }
}
