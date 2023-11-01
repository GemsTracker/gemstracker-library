<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\BrowseChangeHandler;
use Gems\Model\Setup\MailCodeModel;
use Gems\Model\Setup\MailCodeUsageCounter;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsHandler\ConstructorModelHandlerTrait;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class MailCodeHandler extends BrowseChangeHandler
{
    use ConstructorModelHandlerTrait;

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['mailcodes'];

    public function __construct(
        SnippetResponderInterface                    $responder,
        MetaModelLoader                              $metaModelLoader,
        TranslatorInterface                          $translate,
        CacheItemPoolInterface                       $cache,
        MailCodeModel                                $mailCodeModel,
        protected readonly MailCodeUsageCounter      $usageCounter,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);

        $this->model = $mailCodeModel;
    }
    
    public function getIndexTitle(): string
    {
        return $this->_('Mail codes');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('mail code', 'mail codes', $count);
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);

        if ($action instanceof BrowseFilteredAction) {
            $action->extraSort = ['gmc_id' => SORT_ASC,];
        }
    }
}