<?php


class Gems_Default_SurveyCodeBookAction extends \Gems_Controller_ModelSnippetActionAbstract
{

    protected $surveyId;

    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->getSurveyCodeBookModel($this->surveyId);

        //$test = $model->load();

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->_('Codebook');
    }

    public function getTopicTitle()
    {
        return $this->_('Codebook');
    }

    public function exportAction()
    {
        $this->surveyId = $this->request->getParam(\MUtil_Model::REQUEST_ID);
        if ($this->surveyId == false) {
            throw new \Exception('No Survey ID set');
        }

        parent::exportAction();
    }
    
    public function getExportClasses()
    {
        return $this->loader->getExport()->getExportClasses();
    }

    /**
     * Get the return url
     * 
     * @return \MUtil_Html_HrefArrayAttribute Used as href for the \MUtil_Html_AElement
     */
    protected function getExportReturnLink() {
        // At the moment we can only come from the survey-maintenance action, so we redirect there instead of the the index of this action.

        $urlArray = \MUtil_Html_UrlArrayAttribute::rerouteUrl(
                        $this->getRequest(),
                        [
                            'controller' => 'survey-maintenance',
                            'action'     => 'show',
                            'id'         => $this->surveyId
        ]);
        
        $url = new \MUtil_Html_HrefArrayAttribute($urlArray);
        $url->setRouteReset(true);
        
        return $url;
    }

}