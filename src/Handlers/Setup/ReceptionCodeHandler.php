<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Actions\ProjectSettings;
use Gems\Handlers\BrowseChangeHandler;
use Gems\Model\Setup\ReceptionCodeModel;
use Gems\Model\Setup\ReceptionCodeUsageCounter;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsHandler\ConstructorModelHandlerTrait;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Controller for maintaining reception codes.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class ReceptionCodeHandler extends BrowseChangeHandler
{
    use ConstructorModelHandlerTrait;

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public array $cacheTags = ['receptionCode', 'receptionCodes'];

    public function __construct(
        SnippetResponderInterface                    $responder,
        MetaModelLoader                              $metaModelLoader,
        TranslatorInterface                          $translate,
        CacheItemPoolInterface                       $cache,
        ReceptionCodeModel                           $receptionCodeModel,
        protected readonly ReceptionCodeUsageCounter $usageCounter,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);

        $this->model = $receptionCodeModel;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Reception codes');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('reception code', 'reception codes', $count);
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);

        if ($action instanceof BrowseFilteredAction) {
            $action->extraSort = ['grc_id_reception_code' => SORT_ASC,];
        }
    }
}
