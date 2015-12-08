<?php

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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class MUtil_Model_NestedModelTest extends MUtil_Model_AbstractModelTest
{
    /**
     *
     * @var MUtil_Model_TableModel
     */
    private $_nestedModel;

    /**
     * Create the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function getNestedModel()
    {
        if (! $this->_nestedModel) {
            $this->_nestedModel = new MUtil_Model_TableModel('n1');

            $sub = new MUtil_Model_TableModel('n2');

            $this->_nestedModel->addModel($sub, array('id' => 'pid'));
        }

        return $this->_nestedModel;
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

    public function testHasTwoTables()
    {
        $model = $this->getNestedModel();
        $rows  = $model->load();
        // error_log(print_r($rows, true));

        $this->assertCount(3, $rows);
        $this->assertCount(2, $rows[0]['n2']);
        $this->assertCount(0, $rows[1]['n2']);
        $this->assertCount(3, $rows[2]['n2']);

        $model = new MUtil_Model_TableModel('n2');
        $rows  = $model->load();
        $this->assertCount(5, $rows);
    }

    public function testInsertARow()
    {
        $model  = $this->getNestedModel();
        $result = $model->save(array(
            'id' => null,
            'c1' => "col1-4",
            'c2' => "col2-4",
            'n2' => array(array('c1' => 'p4col1-6', 'c2' => 'p4col2-6')),
            ));
        // error_log(print_r($result, true));
        
        $this->assertEquals(4, $result['id']);
        $this->assertEquals(6, $result['n2'][0]['cid']);

        $rows = $model->load();
        $this->assertCount(4, $rows);
    }
}
