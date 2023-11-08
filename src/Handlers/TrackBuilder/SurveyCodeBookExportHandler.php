<?php


namespace Gems\Handlers\TrackBuilder;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Model\SurveyCodeBookModel;
use MUtil\Model\ModelAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class SurveyCodeBookExportHandler extends ModelSnippetLegacyHandlerAbstract
{
    protected ?int $surveyId = null;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected ProjectOverloader $overLoader
    ) {
        parent::__construct($responder, $translate, $cache);
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

    public function exportAction(): ResponseInterface
    {
        $this->surveyId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID);
        if ($this->surveyId === null) {
            throw new \Exception('No Survey ID set');
        }

        return parent::exportAction();
    }
}
