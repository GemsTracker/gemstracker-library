<?php

namespace Gems\Snippets\Batch;

use MUtil\Batch\BatchAbstract;
use MUtil\Html;
use MUtil\Snippets\SnippetAbstract;
use Zalt\Base\RequestInfo;

class ContinuousBatchRunnerSnippet extends SnippetAbstract
{
    /**
     * @var BatchAbstract
     */
    protected $batch;

    /**
     * @var RequestInfo
     */
    protected $requestInfo;

    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $result = $this->batch->runContinuous();

        $messages = $this->batch->getMessages();
        if (!$result) {
            $messages[] = 'Nothing to do...';
        }

        $container = Html::create('div', ['class' => 'alert alert-info']);

        $ul = $container->ul(['class' => 'list']);
        foreach($messages as $message) {
            $ul->li($message);
        }

        $this->batch->reset();

        return $container;
    }
}