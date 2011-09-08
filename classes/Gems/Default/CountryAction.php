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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * 
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package Gems
 * @subpackage Default
 */

/**
 * 
 * @author Matijs de Jong
 * @package Gems
 * @subpackage Default
 */
class Gems_Default_CountryAction  extends Gems_Controller_BrowseEditAction
{
    public $sortKey = array('gct_code' => SORT_ASC);

    /** 
     * Creates a model for getModel(). Called only for each new $action. 
     *  
     * The parameters allow you to easily adapt the model to the current action. The $detailed 
     * parameter was added, because the most common use of action is a split between detailed 
     * and summarized actions.
     *  
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new MUtil_Model_TableModel('gems__countries');
        $model->copyKeys(); // The user can edit the keys.

        $model->set('gct_code',        'label', $this->_('Code'));
        $model->set('gct_description', 'label', $this->_('Description'));
        $model->set('gct_in_eu',       'label', $this->_('In EU'), 
            'multiOptions', $this->util->getTranslated()->getYesNo(),
            'elementClass', 'CheckBox',
            'required', false);
        $model->set('gct_extra',       'label', $this->_('Notes'), 'elementClass', 'TextArea', 'rows', 5);

        if ($detailed) {
            $model->set('gct_code', 'validator', $model->createUniqueValidator('gct_code'));
            $model->set('gct_description', 'validator', $model->createUniqueValidator('gct_description'));
        }

        Gems_Model::setChangeFieldsByPrefix($model, 'gct');

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('country', 'countries', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Countries');
    }
}
