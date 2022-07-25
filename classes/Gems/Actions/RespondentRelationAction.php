<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class RespondentRelationAction extends \Gems\Controller\ModelSnippetActionAbstract
{

    public $_respondent = null;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Respondent\\Relation\\TableSnippet';

    protected $createEditSnippets = 'Respondent\\Relation\\ModelFormSnippet';

    protected $deleteSnippets = 'Respondent\\Relation\\YesNoDeleteSnippet';

    protected $indexStopSnippets = 'Generic\\CurrentSiblingsButtonRowSnippet';

    protected function createModel($detailed, $action)
    {
        $respondent = $this->getRespondent();

        $relationModel = $this->loader->getModels()->getRespondentRelationModel();
        /* @var $relationModel \Gems\Model\RespondentRelationModel */

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
            \MUtil\Model::REQUEST_ID1 => $this->_getParam(\MUtil\Model::REQUEST_ID1),
            \MUtil\Model::REQUEST_ID2 => $this->_getParam(\MUtil\Model::REQUEST_ID2),
            );

        parent::deleteAction();
    }

}