<?php

class Gems_Model_EncryptedFieldModelTest extends MUtil_Model_AbstractModelTest
{
    /**
     *
     * @var MUtil_Model_TableModel
     */
    private $_model;

    /**
     * Create the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function getModel()
    {
        if (! $this->_model) {
            $this->_model = new MUtil_Model_TableModel('t1');

            $settings = new Zend_Config_Ini(GEMS_ROOT_DIR . '/application/configs/project.ini', APPLICATION_ENV);
            $settings = $settings->toArray();
            $settings['salt'] = 'vadf2646fakjndkjn24656452vqk';
            $project = new Gems_Project_ProjectSettings($settings);
            $encryptedField = new Gems_Model_Type_EncryptedField($project, false);
            $encryptedField->apply($this->_model, 'c1', 'c3');
        }

        return $this->_model;
    }

    /**
     * The template file name to create the sql create and xml load names from.
     *
     * Just reutrn __FILE__
     *
     * @return string
     */
    protected function getTemplateFileName()
    {
        return __FILE__;
    }

    /**
     * Make sure there is some test data to work on
     */
    public function testHasFirstRow()
    {
        $model = $this->getModel();
        $rows = $model->load();
        $this->assertCount(1, $rows);
    }

    /**
     * Does reading work with transparent decryption?
     */
    public function testPasswordsMatch()
    {
        $model = $this->getModel();
        $row = $model->loadFirst();
        $this->assertEquals($row['c1'], $row['c2']);

    }

    /**
     * Can we insert and is the field different from the unencrypted field?
     */
    public function testInsertARow()
    {
        $model  = $this->getModel();
        $result = $model->save(array('id' => null, 'c1' => "myvisiblepassword", 'c2' => "myvisiblepassword", 'c3' => 'default'));
        $this->assertEquals(2, $result['id']);
        
        $model->remove('c1', MUtil_Model_ModelAbstract::LOAD_TRANSFORMER);
        $row = $model->loadFirst(array('id'=>2));
        $this->assertNotEquals($row['c1'], $row['c2']);
    }

    /**
     * Can the inserted field be decrypted?
     */
    public function testRetrieveInsertedRow()
    {
        $model  = $this->getModel();
        $result = $model->save(array('id' => null, 'c1' => "myvisiblepassword", 'c2' => "myvisiblepassword", 'c3' => 'default'));

        $row = $model->loadFirst(array('id'=>$result['id']));
        $this->assertEquals($row['c1'], $row['c2']);
    }
}