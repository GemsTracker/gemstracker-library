<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\CommTemplateModel;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\Vue\CreateEditSnippet;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class CommTemplateHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public array $cacheTags = ['commTemplates'];

    protected array $createEditSnippets = [
        ContentTitleSnippet::class,
        CreateEditSnippet::class,
        CurrentButtonRowSnippet::class,
    ];

    protected array $createParameters = [
        'addCurrentSiblings' => true,
        'contentTitle' => 'getCreateTitle',
        'vueOptions' => 'getVueOptions',
    ];

    protected array $editParameters = [
        'addCurrentSiblings' => true,
        'contentTitle' => 'getEditTitle',
        'vueOptions' => 'getVueOptions',
    ];

    protected array $defaultParameters = [
        'dataResource' => 'commTemplate',
        'dataEndpoint' => 'comm-template',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly ProjectOverloader $overloader,
        protected readonly CurrentUserRepository $currentUserRepository,
    )
    {
        parent::__construct($responder, $translate, $cache);
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return CommTemplateModel
     */
    public function createModel(bool $detailed, string $action): CommTemplateModel
    {
        /**
         * @var CommTemplateModel
         */
        $model = $this->overloader->create(CommTemplateModel::class);
        if (!$detailed) {
            $model->applyBrowseSettings();
        } else {
            $model->applyDetailSettings();
        }

        return $model;
    }

    public function getCreateTitle(): string
    {
        return $this->_('New template');
    }

    public function getEditTitle(): string
    {
        $data = $this->getModel()->loadFirst();

        return sprintf($this->_('Edit template: %s'), $data['gct_name']);
    }

    public function getIndexTitle(): string
    {
        return $this->_('Email templates');
    }

    public function getVueOptions(): array
    {
        $currentUser = $this->currentUserRepository->getCurrentUser();

        return [
            ':test-email' => (int)$currentUser->hasPrivilege('pr.comm.templates.test'),
        ];
    }
}
