<?php

declare(strict_types=1);

namespace Gems\Handlers;

use Gems\Model\Ra\FolderModel;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Show\ShowAction;
use Gems\Util\Translated;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsHandler\ConstructorModelHandlerTrait;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class LogFileHandler extends BrowseChangeHandler
{
    public $recursive = true;

    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index' => BrowseSearchAction::class,
        'show' => ShowAction::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly Translated $translatedUtil,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        $model = new FolderModel(
//            GEMS_ROOT_DIR . '/data/logs',
            '/app/data/logs',
            $this->metaModelLoader,
            recursive: $this->recursive,
            followSymlinks: false,
        );

        if ($this->recursive) {
            $model->getMetaModel()->set('relpath', [
                'label' => $this->_('File (local)'),
                'maxlength' => 255,
                'size' => 40,
                'validators' => ['File_Path', 'File_IsRelativePath']
            ]);
            $model->getMetaModel()->set('filename', ['elementClass' => 'Exhibitor']);
        }

        if ($action->isDetailed() || (!$this->recursive)) {
            $model->getMetaModel()->set('filename', [
                'label' => $this->_('Filename'),
                'size' => 30,
                'maxlength' => 255
            ]);
        }
        if ($action->isDetailed()) {
            $model->getMetaModel()->set('path', [
                'label' => $this->_('Path'),
                'elementClass' => 'Exhibitor'
            ]);
            $model->getMetaModel()->set('fullpath', [
                'label' => $this->_('Full name'),
                'elementClass' => 'Exhibitor'
            ]);
            $model->getMetaModel()->set('extension', [
                'label' => $this->_('Type'),
                'elementClass' => 'Exhibitor'
            ]);
            $model->getMetaModel()->set('content', [
                'label' =>$this->_('Content'),
                'formatFunction' => [\MUtil\Html::create(), 'pre'],
                'elementClass' => 'TextArea',
            ]);
        }
        $model->getMetaModel()->set('size', [
            'label' => $this->_('Size'),
            'formatFunction' => ['\\MUtil\\File', 'getByteSized'],
            'elementClass' =>'Exhibitor',
        ]);
        $model->getMetaModel()->set('changed', [
            'label' =>$this->_('Changed on'),
            'dateFormat' => $this->translatedUtil->dateTimeFormatString,
            'elementClass' =>'Exhibitor',
        ]);

        return $model;
    }

    public function getTopic(int $count = 1): string
    {
        return $this->translate->plural('Log file', 'Log files', $count);
    }
}