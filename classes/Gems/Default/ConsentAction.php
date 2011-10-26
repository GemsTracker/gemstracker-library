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
class Gems_Default_ConsentAction  extends Gems_Controller_BrowseEditAction
{
    public $menuIndexIncludeLevel = 1;

    public $sortKey = array('gco_order' => SORT_ASC);

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
        $model = new MUtil_Model_TableModel('gems__consents');
        $model->copyKeys(); // The user can edit the keys.

        $model->set('gco_description', 'label', $this->_('Description'), 'size', '10');

        $model->set('gco_order',       'label', $this->_('Order'), 'size', '10',
            'description', $this->_('Determines order of presentation in interface.'),
            'validator', 'Digits');
        $model->set('gco_code',        'label', $this->_('Consent code'),
            'multiOptions', $this->util->getConsentTypes(),
            'description', $this->_('Internal code, not visible to users, copied with the token information to the source.'));
        if ($detailed) {
            $model->set('gco_description', 'validator', $model->createUniqueValidator('gco_description'));
            $model->set('gco_order',       'validator', $model->createUniqueValidator('gco_order'));
        }

        if ($this->project->multiLocale) {
            $model->set('gco_description', 'description', 'ENGLISH please! Use translation file to translate.');
        }

        Gems_Model::setChangeFieldsByPrefix($model, 'gco');

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('respondent consent', 'respondent consents', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Respondent consents');
    }
}
