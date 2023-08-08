<?php

namespace Gems\Handlers\Setup;

use Gems\Handlers\BrowseChangeHandler;
use Gems\Model\AppointmentInfoFilterModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class AppointmentInfoFilterHandler extends BrowseChangeHandler
{
    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        protected readonly AppointmentInfoFilterModel $model,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate);
    }

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        return $this->model;
    }
}