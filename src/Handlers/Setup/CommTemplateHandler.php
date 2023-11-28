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
use Gems\Model\CommTemplateModel;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\Vue\CreateEditSnippet;
use MUtil\Model\ModelAbstract;
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
        CreateEditSnippet::class,
        CurrentButtonRowSnippet::class,
    ];

    protected array $defaultParameters = [
        'dataResource' => 'commTemplate',
        'dataEndpoint' => 'comm-template',
    ];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    //protected array $createEditSnippets = ['Mail\\MailModelFormSnippet'];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    //protected array $showSnippets = ['Mail\\CommTemplateShowSnippet'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly ProjectOverloader $overloader,
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
     * @return \MUtil\Model\ModelAbstract
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
}
