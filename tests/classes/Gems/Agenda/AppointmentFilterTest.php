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
     * General test case for appointment filters
     *
     * @param array $expectedAppFilters Expected result of matchAppointment(), Nested array appointment id's = [triggered filter id's]
     * @param string $test Name of test
     */
    public function performFilterTests(array $expectedAppFilters, $test)
    {
        $allAppointments    = $this->model->load();
        $allEpisodeIds      = $this->db->fetchPairs(
                "SELECT gec_episode_of_care_id, gec_episode_of_care_id
                    FROM gems__episodes_of_care
                    ORDER BY gec_episode_of_care_id"
                );
        $testFilters        = $this->db->fetchPairs(
                "SELECT gaf_id, gaf_active
                    FROM gems__appointment_filters INNER JOIN gems__track_appointments ON gaf_id = gtap_filter_id
                    ORDER BY gaf_id"
                );

        $expectedEpiFilters = array_fill_keys($allEpisodeIds, []);
        $expectedFilterApps = array_fill_keys(array_keys($testFilters), []);
        $expectedFilterEpis = $expectedFilterApps;

        // TEST matchAppointment()
        $resultsAppFilters  = [];
        foreach ($allAppointments as $appointmentData) {
            $appointment   = $this->agenda->getAppointment($appointmentData);
            $appointmentId = $appointment->getId();
            $resultsAppFilters[$appointmentId] = [];

            $filters = $this->agenda->matchFilters($appointment);
            foreach ($filters as $filter) {
                if ($filter instanceof AppointmentFilterInterface) {
                    $filterId = $filter->getFilterId();
                    $resultsAppFilters[$appointmentId][] = $filterId;

                    if (! $testFilters[$filterId] instanceof AppointmentFilterInterface) {
                        if (0 == $testFilters[$filterId]) {
                            $this->fail(sprintf(
                                    "Filter %s (id %s) was triggered while inactive in $test.",
                                    $filter->getName(),
                                    $filterId
                                    ));
                        }
                        $testFilters[$filterId] = $filter;
                    }
                    // Prepare for getSqlAppointmentsWhere() test
                    $expectedFilterApps[$filterId][] = $appointment->getId();

                    // Prepare for matchEpisode() test
                    if ($appointment->hasEpisode()) {
                        $expectedEpiFilters[$appointment->getEpisodeId()][] = $filterId;
                    }
                } else {
                    $this->fail("Unexepected non-filter return for appointment in $test.");
                }
            }
        }
        // error_log(print_r($results, true));
        $this->assertEquals(
                $expectedAppFilters,
                $resultsAppFilters,
                "Appointments match not equal to expected result for $test."
                );

        // TEST getSqlAppointmentsWhere()
        $resultsFilterApps = [];
        foreach ($testFilters as $filterId => $filter) {
            if (! $filter instanceof AppointmentFilterInterface) {
                if (0 == $filter) {
                    // Was not used so triggered no results to compare to
                    $resultsFilterApps[$filterId] = [];
                    continue;
                }
                $filter = $this->agenda->getFilter($filterId);
            }
            if ($filter instanceof AppointmentFilterInterface) {
                $sql = "SELECT gap_id_appointment FROM gems__appointments WHERE " . $filter->getSqlAppointmentsWhere();
                $resultsFilterApps[$filter->getFilterId()] = $this->db->fetchCol($sql);
            } else {
                $this->fail(sprintf("Filter %d could not be loaded in $test.", $filterId));
            }
        }
        $this->assertEquals(
                $expectedFilterApps,
                $resultsFilterApps,
                "Appointment SQL not equal to appointment match for $test."
                );

        // TEST matchEpsiode()
        $resultsEpiFilters  = [];
        foreach ($allEpisodeIds as $epiId) {
            $episode = $this->agenda->getEpisodeOfCare($epiId);

            $resultsEpiFilters[$epiId] = [];

            $filters = $this->agenda->matchFilters($episode);
            foreach ($filters as $filter) {
                if ($filter instanceof AppointmentFilterInterface) {
                    $filterId = $filter->getFilterId();
                    $resultsEpiFilters[$epiId][] = $filterId;

                    if (! $testFilters[$filterId] instanceof AppointmentFilterInterface) {
                        if (0 == $testFilters[$filterId]) {
                            $this->fail(sprintf(
                                    "Filter %s (id %s) was triggered while inactive in $test.",
                                    $filter->getName(),
                                    $filterId
                                    ));
                        }
                        $testFilters[$filterId] = $filter;
                    }
                    // Prepare for getSqlEpisodeWhere() test
                    $expectedFilterEpis[$filterId][] = $episode->getId();
                } else {
                    $this->fail("Unexepected non-filter return for epsiode in $test.");
                }
            }
        }
        $this->assertEquals(
                $expectedEpiFilters,
                $resultsEpiFilters,
                "Episode match not equal to expected result for $test."
                );

        // TEST getSqlEpisodeWhere()
        $resultsFilterEpis = [];
        foreach ($testFilters as $filterId => $filter) {
            if (! $filter instanceof AppointmentFilterInterface) {
                if (0 == $filter) {
                    // Was not used so triggered no results to compare to
                    $resultsFilterEpis[$filterId] = [];
                    continue;
                }
                $filter = $this->agenda->getFilter($filterId);
            }
            if ($filter instanceof AppointmentFilterInterface) {
                $sql = "SELECT gec_episode_of_care_id FROM gems__episodes_of_care WHERE " . $filter->getSqlEpisodeWhere();
                $resultsFilterEpis[$filter->getFilterId()] = $this->db->fetchCol($sql);
            } else {
                $this->fail(sprintf("Filter %d could not be loaded in $test.", $filterId));
            }
        }
        $this->assertEquals(
                $expectedFilterEpis,
                $resultsFilterEpis,
                "Episode SQL not equal to episode match for $test."
                );

    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
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
        $config->loaderDirs = [GEMS_PROJECT_NAME_UC => GEMS_TEST_DIR . "/classes/" . GEMS_PROJECT_NAME_UC] +
                $config->loaderDirs->toArray();

        // Create application, bootstrap, and run
        $application = new Zend_Application(APPLICATION_ENV, $config);

        $this->bootstrap = $application;

        parent::setUp();

        $this->bootstrap->bootstrap('db');
        $this->bootstrap->getBootstrap()->getContainer()->db = $this->db;

        // Removing caching as this screws up the tests
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
        $this->agenda = $this->loader->getAgenda()->reset();
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
     * Test subject filters
     */
    public function testAppSubjectFilters()
    {
        $this->performFilterTests(
                [
                    1 => [1],
                    2 => [2],
                    3 => [1, 2],
                    4 => [],
                    5 => [4],
                ], 'Appointment Subject');
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
     * Test Field Like filters
     */
    public function testFieldLikeFilters()
    {
        $this->performFilterTests(
                [
                    1 => [1, 2, 3, 7, 8],
                    2 => [1],
                    3 => [1, 2, 7],
                    4 => [1],
                    5 => [4, 7, 8],
                    6 => [4, 5, 6],
                    7 => [4, 5, 7],
                    8 => [4],
                    9 => [1, 2, 3, 4, 7, 8],
                    10 => [1, 4, 5, 6],
                    11 => [1, 2, 4, 5, 7],
                    12 => [1, 4],
                    13 => [7, 8],
                    14 => [],
                    15 => [7],
                    16 => [],
                    17 => [7, 8],
                    18 => [],
                    19 => [7],
                    20 => [],
                    21 => [1],
                    22 => [4],
                    23 => [1, 4],
                    24 => [],
                    25 => [],
                ], 'SQL Like');
    }

    /**
     * Test location filters
     */
    public function testLocationFilters()
    {
        $this->performFilterTests([
            1 => [1],
            2 => [2],
            3 => [1, 2],
            4 => [],
            ], 'Locations');
    }

    /**
     * Test SQL Like filters
     */
    public function testSqlLikeFilters()
    {
        $this->performFilterTests(
                [
                    1 => [1, 2, 3, 7, 8],
                    2 => [1],
                    3 => [1, 2, 7],
                    4 => [1],
                    5 => [4, 7, 8],
                    6 => [4, 5, 6],
                    7 => [4, 5, 7],
                    8 => [4],
                    9 => [1, 2, 3, 4, 7, 8],
                    10 => [1, 4, 5, 6],
                    11 => [1, 2, 4, 5, 7],
                    12 => [1, 4],
                    13 => [7, 8],
                    14 => [],
                    15 => [7],
                    16 => [],
                    17 => [7, 8],
                    18 => [],
                    19 => [7],
                    20 => [],
                    21 => [1],
                    22 => [4],
                    23 => [1, 4],
                    24 => [],
                    25 => [],
                ], 'SQL Like');
    }
}
