<?php


class SurveyCodeBookExportAction extends \Gems\Controller\ModelSnippetActionAbstract
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
        $this->surveyId = $this->request->getParam(\MUtil\Model::REQUEST_ID);
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
     * @return \MUtil\Html\HrefArrayAttribute Used as href for the \MUtil\Html\AElement
     */
    protected function getExportReturnLink() {
        // At the moment we can only come from the survey-maintenance action, so we redirect there instead of the the index of this action.

        $urlArray = \MUtil\Html\UrlArrayAttribute::rerouteUrl(
            $this->getRequest(),
            [
                'controller' => 'survey-maintenance',
                'action'     => 'show',
                'id'         => $this->surveyId
            ]);

        $url = new \MUtil\Html\HrefArrayAttribute($urlArray);
        $url->setRouteReset(true);

        return $url;
    }
}
