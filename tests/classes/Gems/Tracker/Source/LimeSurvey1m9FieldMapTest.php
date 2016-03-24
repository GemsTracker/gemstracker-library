<?php

class Gems_Tracker_Source_LimeSurvey1m9FieldMapTest extends \Gems_Test_DbTestAbstract
{

    protected function setUp()
    {
        parent::setUp();

        $sourceSurveyId = 1;
		$language = 'en';
		$lsDb = $this->db;
		$translate = $this->getTranslate();
		$tablePrefix = '';

		$this->fieldmap = new \Gems_Tracker_Source_LimeSurvey1m9FieldMap($sourceSurveyId, $language, $lsDb, $translate, $tablePrefix);
    }

    /**
     * Returns the test dataset xml of the same name as the test
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        $classFile =  str_replace('.php', '.yml', __FILE__);
        return new PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            $classFile
        );
    }

    protected function getInitSql()
    {
        $path = GEMS_TEST_DIR . '/data/';

        // For successful testing of the complete tokens class, we need more tables
        return array($path . 'sqllite/create-lite-ls.sql');
    }

	protected function getTranslate()
	{
		$english = array(
        	'test' => 'test',
        );

		$translate = new \Zend_Translate(
			array(
				'adapter' => 'array',
				'content' => $english,
				'locale' => 'en'
			)
		);

		return $translate;
	}

	public function providerTestSurveyModelDateStorageFormat()
	{
		return array(
			array('1X1X1', 'yyyy-MM-dd HH:mm:ss'), 	// Limesurvey 2.0 default date
			array('1X1X2', 'yyyy-MM-dd'),			// Limesurvey legacy date
			array('1X1X3', 'yyyy-MM-dd HH:mm:ss'),	// Limesurvey 2.0 date with no time in Date Format
			array('1X1X4', 'yyyy-MM-dd HH:mm:ss'),	// Limesurvey 2.0 date with only time in Date Format
		);
	}

	/**
     * @param string $fieldName the name of the field in the model
     * @param string $expectedStorageFormat the Expected storageFormat value
     *
     * @dataProvider providerTestSurveyModelDateStorageFormat
     */
	public function testSurveyModelDateStorageFormat($fieldName, $expectedStorageFormat)
	{
		// Create a simple array model to apply to fieldmap to
		$array = array('test' => 123);
		$model = new \Gems_Model_PlaceholderModel($array); 
		$this->fieldmap->applyToModel($model);
		
		$this->assertEquals($model->get($fieldName, 'storageFormat'), $expectedStorageFormat);
	}
}