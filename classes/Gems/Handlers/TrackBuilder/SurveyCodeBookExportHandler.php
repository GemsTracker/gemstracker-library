<?php


namespace Gems\Handlers\TrackBuilder;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\MenuNew\RouteHelper;
use Gems\Model\SurveyCodeBookModel;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class SurveyCodeBookExportHandler extends ModelSnippetLegacyHandlerAbstract
{
    protected ?int $surveyId = null;

    public function __construct(
        RouteHelper $routeHelper,
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected ProjectOverloader $overLoader
    ) {
        parent::__construct($routeHelper, $responder, $translate);
    }

    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        /**
         * @var $model SurveyCodeBookModel
         */
        $model = $this->overLoader->create('Model\\SurveyCodeBookModel', $this->surveyId);

        return $model;
    }

    public function getTopic(int $count = 1): string
    {
        return $this->_('Codebook');
    }

    public function getTopicTitle(): string
    {
        return $this->_('Codebook');
    }

    public function exportAction(): void
    {
        $this->surveyId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID);
        if ($this->surveyId === null) {
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

        $urlArray = \Zalt\Html\UrlArrayAttribute::rerouteUrl(
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
