<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RespondentRelationAction
 *
 * @author 175780
 */
class Gems_Default_RespondentRelationAction extends \Gems_Controller_ModelSnippetActionAbstract {
    
    public $_respondent = null;
    
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Respondent_Relation_TableSnippet';
    
    protected $createEditSnippets = 'Respondent_Relation_ModelFormSnippet';
    
    protected $deleteSnippets = 'Respondent_Relation_YesNoDeleteSnippet';
    
    protected $indexStopSnippets = 'Generic_CurrentButtonRowSnippet';
       
    protected function createModel($detailed, $action) {
        $respondent = $this->getRespondent();
        
        $relationModel = $this->loader->getModels()->getRespondentRelationModel();
        /* @var $relationModel Gems_Model_RespondentRelationModel */

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
       
    public function deleteAction() {
        $this->deleteParameters['resetRoute'] = true;
        $this->deleteParameters['deleteAction'] = 'delete'; // Trick to not get aftersaveroute
        $this->deleteParameters['abortAction'] = 'index';
        $this->deleteParameters['afterSaveRouteUrl'] = array(
            'action' => 'index', 
            'controller' => 'respondent-relation', 
            MUtil_Model::REQUEST_ID1 => $this->_getParam(MUtil_Model::REQUEST_ID1),
            MUtil_Model::REQUEST_ID2 => $this->_getParam(MUtil_Model::REQUEST_ID2),
            );
                
        parent::deleteAction();
    }

}