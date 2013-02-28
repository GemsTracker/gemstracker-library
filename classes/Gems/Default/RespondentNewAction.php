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
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentNewAction.php$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
abstract class Gems_Default_RespondentNewAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'ModelTabFormSnippetGeneric';

    /**
     *
     * @var Gems_Loader
     */
    public $loader;

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
    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->createRespondentModel();

        if (! $detailed) {
            return $model->applyBrowseSettings();
        }

        switch ($action) {
            case 'create':
            case 'edit':
                return $model->applyEditSettings();

            default:
                return $model->applyDetailSettings();
        }
    }

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns()
    {
        $model = $this->getModel();

        $model->setIfExists('gr2o_opened', 'tableDisplay', 'small');
        $model->setIfExists('grs_email',   'formatFunction', 'MUtil_Html_AElement::ifmail');

        // Newline placeholder
        $br = MUtil_Html::create('br');

        // Display separator and phone sign only if phone exist.
        $phonesep = MUtil_Html::raw('&#9743; '); // $bridge->itemIf($bridge->grs_phone_1, MUtil_Html::raw('&#9743; '));
        $citysep  = MUtil_Html::raw('&nbsp;&nbsp;'); // $bridge->itemIf($bridge->grs_zipcode, MUtil_Html::raw('&nbsp;&nbsp;'));

        if ($this->loader->getCurrentUser()->hasPrivilege('pr.respondent.multiorg')) {
            $columns[] = array('gr2o_patient_nr', $br, 'gr2o_id_organization');
        } else {
            $columns[] = array('gr2o_patient_nr', $br, 'gr2o_opened');
        }
        $columns[] = array('name',            $br, 'grs_email');
        $columns[] = array('grs_address_1',   $br, 'grs_zipcode', $citysep, 'grs_city');
        $columns[] = array('grs_birthday',    $br, $phonesep, 'grs_phone_1');

        return $columns;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Respondents');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('respondent', 'respondents', $count);;
    }

    /**
     * Overrule default index for the case that the current
     * organization cannot have users.
     */
    public function indexAction()
    {
        $user = $this->loader->getCurrentUser();

        if ($user->hasPrivilege('pr.respondent.multiorg') || $user->getCurrentOrganization()->canHaveRespondents()) {
            parent::indexAction();
        } else {
            $this->addSnippet('Organization_ChooseOrganizationSnippet');
        }
    }
}
