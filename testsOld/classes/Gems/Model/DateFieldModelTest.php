<?php


/**
 * Description of RespondentModelTest
 *
 * @package    Gems
 * @subpackage \Gems
 * @author     175780
 * @copyright  Copyright (c) 2014
 * @license    New BSD License
 */
namespace Gems\Model;

class DateFieldModelTest extends MUtil\Model_AbstractModelTest
{
    /**
     *
     * @var \MUtil\Model\TableModel
     */
    private $_model;

    protected function setUp()
    {
        parent::setup();

        // Now set some defaults
        $dateFormOptions['dateFormat']   = 'dd-MM-yyyy';
        $datetimeFormOptions['dateFormat']   = 'dd-MM-yyyy HH:mm';
        $timeFormOptions['dateFormat']   = 'HH:mm';

        \MUtil\Model\Bridge\FormBridge::setFixedOptions(array(
            'date'     => $dateFormOptions,
            'datetime' => $datetimeFormOptions,
            'time'     => $timeFormOptions,
            ));
    }

    /**
     * Create the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function getModel()
    {
        if (! $this->_model) {
            $this->_model = new MUtil\Model\TableModel('dfmt');
            $this->_model->set('grs_birthday', 'storageFormat', 'yyyy-MM-dd');
            $this->_model->set('grs_birthday', 'dateFormat', \Zend_Date::DATE_MEDIUM);
            $this->_model->setOnSave('grs_birthday', array($this->_model, 'formatSaveDate'));
            $this->_model->setOnLoad('grs_birthday', array($this->_model, 'formatLoadDate'));
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

    protected function setLocaleTo($locale)
    {
        $locale = new \Zend_Locale($locale);
        \Zend_Registry::set('Zend_Locale', $locale);
    }

    /**
     * Make sure there is some test data to work on
     */
    public function testReadFromDb()
    {
        $model = $this->getModel();
        $row = $model->loadFirst();

        $this->assertEquals($row['grs_birthday'], new MUtil\Date("2014-04-02", 'yyyy-MM-dd'));
    }

    /**
     * Make sure there is some test data to work on
     */
    public function testSaveToDb()
    {
        $model = $this->getModel();
        $row = $model->loadFirst();

        $rowsaved = $model->save($row);
        $this->assertEquals($row['grs_birthday']->get('yyyy-MM-dd'), $rowsaved['grs_birthday']->get('yyyy-MM-dd'));
    }

    /**
     * This testcase should work as we work with dutch date format
     */
    public function testPostDataToDbNl()
    {
        $this->setLocaleTo('nl');
        $model = $this->getModel();
        $row = $model->loadFirst();

        $date = $row['grs_birthday'];
        $postData = $date->get($this->_model->get('grs_birthday', 'dateFormat'));
        $postRow = array('grs_birthday'=>$postData) + $row;
        $postRow = $this->_model->processAfterLoad(array($postRow), false, true);
        $rowsaved = $model->save($postRow);
        $this->assertEquals($row['grs_birthday']->get('yyyy-MM-dd'), $rowsaved[0]['grs_birthday']->get('yyyy-MM-dd'));
    }

    /**
     * This could fail when date format is interpreted incorrect (bug #703)
     */
    public function testPostDataToDbEn()
    {
        $this->setLocaleTo('en');
        $model = $this->getModel();
        $row = $model->loadFirst();

        $date = $row['grs_birthday'];
        $postData = $date->get($this->_model->get('grs_birthday', 'dateFormat'));
        $postRow = array('grs_birthday'=>$postData) + $row;
        $postRow = $this->_model->processAfterLoad(array($postRow), false, true);
        $rowsaved = $model->save($postRow);
        $this->assertEquals($row['grs_birthday']->get('yyyy-MM-dd'), $rowsaved[0]['grs_birthday']->get('yyyy-MM-dd'));
    }

}
