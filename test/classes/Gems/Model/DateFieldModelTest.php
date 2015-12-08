<?php

/*
 * Copyright (c) Error: on line 4, column 33 in Templates/Licenses/license-bsd_1.txt
  The string doesn't match the expected date/time format. The string to parse was: "Sep 30, 2014". The expected format was: "d-MMM-yyyy"., Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * * Neither the name of Erasmus MC nor the
 *   names of its contributors may be used to endorse or promote products
 *   derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Description of RespondentModelTest
 *
 * @package    Gems
 * @subpackage Gems
 * @author     175780
 * @copyright  Copyright (c) 2014
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_Model_DateFieldModelTest  extends MUtil_Model_AbstractModelTest
{
    /**
     *
     * @var MUtil_Model_TableModel
     */
    private $_model;

    protected function setUp()
    {
        parent::setup();

        // Now set some defaults
        $dateFormOptions['dateFormat']   = 'dd-MM-yyyy';
        $datetimeFormOptions['dateFormat']   = 'dd-MM-yyyy HH:mm';
        $timeFormOptions['dateFormat']   = 'HH:mm';

        MUtil_Model_Bridge_FormBridge::setFixedOptions(array(
            'date'     => $dateFormOptions,
            'datetime' => $datetimeFormOptions,
            'time'     => $timeFormOptions,
            ));
    }

    /**
     * Create the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function getModel()
    {
        if (! $this->_model) {
            $this->_model = new MUtil_Model_TableModel('gems__respondents');
            $this->_model->set('grs_birthday', 'storageFormat', 'yyyy-MM-dd');
            $this->_model->set('grs_birthday', 'dateFormat', Zend_Date::DATE_MEDIUM);
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
        $locale = new Zend_Locale($locale);
        Zend_Registry::set('Zend_Locale', $locale);
    }

    /**
     * Make sure there is some test data to work on
     */
    public function testReadFromDb()
    {
        $model = $this->getModel();
        $row = $model->loadFirst();

        $this->assertEquals($row['grs_birthday'], new MUtil_Date("2014-04-02", 'yyyy-MM-dd'));
    }

    /**
     * Make sure there is some test data to work on
     */
    public function testSaveToDb()
    {
        $model = $this->getModel();
        $row = $model->loadFirst();

        $rowsaved = $model->save($row);
        $this->assertEquals($row['grs_birthday'], $rowsaved['grs_birthday']);
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
