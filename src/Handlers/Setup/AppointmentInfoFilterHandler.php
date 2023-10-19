<?php

namespace Gems\Handlers\Setup;

use Gems\Handlers\BrowseChangeHandler;
use Gems\Model\AppointmentInfoFilterModel;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsHandler\ConstructorModelHandlerTrait;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class AppointmentInfoFilterHandler extends BrowseChangeHandler
{
    use ConstructorModelHandlerTrait;

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        AppointmentInfoFilterModel $appointmentInfoFilterModel,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);

        $this->model = $appointmentInfoFilterModel;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('appointment info filter', 'appointment info filters', $count);
    }
}
