<?php

class Gems_Default_CommMessengersAction extends \Gems_Controller_ModelSnippetActionAbstract
{

    protected $createEditSnippets = ['Communication\\MessengersEditSnippet'];

    protected function createModel($detailed, $action)
    {
        return $this->loader->getModels()->getCommMessengersModel($detailed);
    }
}
