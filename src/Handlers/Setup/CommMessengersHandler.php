<?php

namespace Gems\Handlers\Setup;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Model\CommMessengersModel;
use MUtil\Model\ModelAbstract;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class CommMessengersHandler extends ModelSnippetLegacyHandlerAbstract
{

    protected array $createEditSnippets = ['Communication\\MessengersEditSnippet'];

    protected $modelName = CommMessengersModel::class;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected ProjectOverloader $overLoader
    )
    {
        parent::__construct($responder, $translate, $cache);
    }

    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        /**
         * @var $model CommMessengersModel
         */
        $model = $this->overLoader->create($this->modelName);
        $model->applySetting($detailed);

        return $model;
    }
}
