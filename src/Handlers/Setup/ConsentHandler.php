<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers\Setup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\BrowseChangeUsageHandler;
use Gems\Model\Setup\ConsentModel;
use Gems\Model\Setup\ConsentUsageCounter;
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
 * @subpackage Handlers\Setup
 * @since      Class available since version 1.9.2
 */
class ConsentHandler extends BrowseChangeUsageHandler
{
    use ConstructorModelHandlerTrait;

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['consent', 'consents'];

    public static array $parameters = ['gco_description' => '[A-Z0-9-_][a-zA-Z0-9-_]+',];

    public function __construct(
        SnippetResponderInterface              $responder,
        MetaModelLoader                        $metaModelLoader,
        TranslatorInterface                    $translate,
        CacheItemPoolInterface                 $cache,
        ConsentModel                           $consentModel,
        protected readonly ConsentUsageCounter $usageCounter,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);

        $this->model = $consentModel;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Respondent informed consent codes');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('respondent consent', 'respondent consents', $count);
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);

        if ($action instanceof BrowseFilteredAction) {
            $action->extraSort = ['gco_order' => SORT_ASC,];
        }
    }
}