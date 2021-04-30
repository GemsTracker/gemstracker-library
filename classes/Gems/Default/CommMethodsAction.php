<?php

class Gems_Default_CommMethodsAction extends \Gems_Controller_ModelSnippetActionAbstract
{

    protected function createModel($detailed, $action)
    {
        return $this->loader->getModels()->getCommMethodsModel();
    }
}
