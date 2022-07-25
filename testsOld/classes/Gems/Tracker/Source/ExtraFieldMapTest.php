<?php

class ExtraMapTest extends \Gems\Test\DbTestAbstract {
    /**
     *
     * @var \Gems\Tracker\Source\LimeSurvey1m9FieldMap
     */
    protected $fieldmap;

    protected function setUp() {
        // \Zend_Application: loads the autoloader
        require_once 'Zend/Application.php';

        // Create application, bootstrap, and run
        $application = new \Zend_Application(
                APPLICATION_ENV, GEMS_ROOT_DIR . '/configs/application.example.ini'
        );

        $this->bootstrap = $application;

        parent::setUp();

        $this->bootstrap->bootstrap('db');
        $this->bootstrap->getBootstrap()->getContainer()->db = $this->db;

        $this->bootstrap->bootstrap();

        \Zend_Registry::set('db', $this->db);
        \Zend_Db_Table::setDefaultAdapter($this->db);

        $sourceSurveyId = 2;
        $language       = 'en';
        $lsDb           = $this->db;
        $translate      = $this->getTranslate();
        $tablePrefix    = '';
        $cache          = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        $this->cache    = $cache;

        $this->fieldmap = new \Gems\Tracker\Source\LimeSurvey1m9FieldMap($sourceSurveyId, $language, $lsDb, $translate, $tablePrefix, $cache, 1);

    }

    /**
     * Returns the test dataset xml of the same name as the test
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet() {
        $classFile = str_replace('.php', '.yml', __FILE__);
        return new PHPUnit_Extensions_Database_DataSet_YamlDataSet(
                $classFile
        );
    }

    protected function getInitSql() {
        $path = GEMS_TEST_DIR . '/data/';

        // For successful testing of the complete tokens class, we need more tables
        return array($path . 'sqllite/create-lite-ls.sql');
    }

    protected function getTranslate() {
        $english = array(
            'test' => 'test',
        );

        $translate = new \Zend_Translate(
                array(
            'adapter' => 'array',
            'content' => $english,
            'locale'  => 'en'
                )
        );

        return $translate;
    }

    public function testFieldMapFull() {
        // Create a simple array model to apply to fieldmap to
        $array = [];
        $model = new \Gems\Model\PlaceholderModel('test', $array);
        $this->fieldmap->applyToModel($model);

        foreach($model->getItemNames() as $name) {
            $result[$name] = $model->get($name);
            unset($result[$name]['formatFunction'], $result[$name]['groupName'], $result[$name]['sourceId']);
        }

        // To update the stored fieldmap, uncomment the following if you know what you are doing
        // $export = serialize($result);
        // file_put_contents(GEMS_TEST_DIR . '/data/fieldmap.txt', $export);
        $expected = unserialize(file_get_contents(GEMS_TEST_DIR . '/data/fieldmap.txt'));
        $this->assertEquals($expected, $result, 'Fieldmap has changed!!');
    }

    public function testGetQuestionInformation() {
        // Create a simple array model to apply to fieldmap to
        $array = [];
        $model = new \Gems\Model\PlaceholderModel('test', $array);
        $questionInfo = $this->fieldmap->getQuestionInformation();

        // To update the stored fieldmap, uncomment the following if you know what you are doing
        //$export = serialize($questionInfo);
        //file_put_contents(GEMS_TEST_DIR . '/data/questioninfo.txt', $export);
        $expected = unserialize(file_get_contents(GEMS_TEST_DIR . '/data/questioninfo.txt'));
        $this->assertEquals($expected, $questionInfo, 'QuestionInfo has changed!!');
    }

}
