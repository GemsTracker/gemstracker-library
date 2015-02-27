<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ModelFormSnippet
 *
 * @author 175780
 */
class Gems_Snippets_Respondent_Relation_ModelFormSnippet extends Gems_Snippets_ModelFormSnippetGeneric {

    protected function setAfterSaveRoute() {
        $this->afterSaveRouteUrl = array(
            'action'                 => 'index',
            'controller'             => 'respondent-relation',
            MUtil_Model::REQUEST_ID1 => $this->request->getParam(MUtil_Model::REQUEST_ID1),
            MUtil_Model::REQUEST_ID2 => $this->request->getParam(MUtil_Model::REQUEST_ID2),
        );
        
        $this->resetRoute = true;
        
        //parent::setAfterSaveRoute();
        
        return $this;        
    }

}