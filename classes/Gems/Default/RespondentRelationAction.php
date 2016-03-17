<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentRelationAction.php  2534 2015-05-05 18:07:37Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class Gems_Default_RespondentRelationAction extends \Gems_Controller_ModelSnippetActionAbstract
{

    public $_respondent = null;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Respondent_Relation_TableSnippet';

    protected $createEditSnippets = 'Respondent_Relation_ModelFormSnippet';

    protected $deleteSnippets = 'Respondent_Relation_YesNoDeleteSnippet';

    protected $indexStopSnippets = 'Generic\\CurrentSiblingsButtonRowSnippet';

    protected function createModel($detailed, $action)
    {
        $respondent = $this->getRespondent();

        $relationModel = $this->loader->getModels()->getRespondentRelationModel();
        /* @var $relationModel \Gems_Model_RespondentRelationModel */

        $respondentId = $respondent->getId();
        $relationModel->set('grr_id_respondent', 'default', $respondentId);
        $relationModel->set('gr2o_patient_nr', 'default', $respondent->getPatientId());
        $relationModel->set('gr2o_id_organization', 'default', $respondent->getOrganizationId());

        if ($detailed) {
            $relationModel->applyDetailSettings();
        } else {
            $relationModel->applyBrowseSettings();
        }

        return $relationModel;
    }

    public function getRespondent()
    {
        if (is_null($this->_respondent)) {
            $model = $this->loader->getModels()->getRespondentModel(true);
            $model->applyRequest($this->getRequest(), true);
            $respondent = $model->loadFirst();
            $respondent = $this->loader->getRespondent($respondent['gr2o_patient_nr'], $respondent['gr2o_id_organization']);

            $this->_respondent = $respondent;
        }
        return $this->_respondent;
    }

    public function getTopic($count = 1)
    {
        $respondentName = $this->getRespondent()->getName();

        return sprintf($this->plural('relation for %s', 'relations for %s', $count), $respondentName);
    }

    public function deleteAction()
    {
        $this->deleteParameters['resetRoute'] = true;
        $this->deleteParameters['deleteAction'] = 'delete'; // Trick to not get aftersaveroute
        $this->deleteParameters['abortAction'] = 'index';
        $this->deleteParameters['afterSaveRouteUrl'] = array(
            'action' => 'index',
            'controller' => 'respondent-relation',
            \MUtil_Model::REQUEST_ID1 => $this->_getParam(\MUtil_Model::REQUEST_ID1),
            \MUtil_Model::REQUEST_ID2 => $this->_getParam(\MUtil_Model::REQUEST_ID2),
            );

        parent::deleteAction();
    }

}