<?php

class CommMessengersAction extends \Gems\Controller\ModelSnippetActionAbstract
{

    protected $createEditSnippets = ['Communication\\MessengersEditSnippet'];

    protected function createModel($detailed, $action)
    {
        return $this->loader->getModels()->getCommMessengersModel($detailed);
    }
}
