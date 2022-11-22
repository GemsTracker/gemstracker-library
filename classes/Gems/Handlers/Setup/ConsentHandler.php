<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers\Setup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\MenuNew\RouteHelper;
use Gems\Model;
use Gems\Util\ConsentUtil;
use MUtil\Model\ModelAbstract;
use MUtil\Model\TableModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers\Setup
 * @since      Class available since version 1.9.2
 */
class ConsentHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['consent', 'consents'];

    /**
     * The snippets used for the autofilter action.
     *
     * @var array snippets name
     */
    protected array $autofilterParameters = [
        'extraSort' => ['gco_order' => SORT_ASC,],
    ];

    public function __construct(
        RouteHelper $routeHelper,
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected Model $modelLoader,
        protected ConsentUtil $consentUtil,
    ) {
        parent::__construct($routeHelper, $responder, $translate);
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
    public function createModel(bool $detailed, string $action): ModelAbstract
    {
        $model = new TableModel('gems__consents');
        // $model->copyKeys(); // The user can edit the keys.
        $model->addColumn('gco_description', 'origKey');

        $model->set('gco_description', 'label', $this->_('Description'), 'size', '10', 'translate', true);

        $model->set('gco_order',       'label', $this->_('Order'), 'size', '10',
                    'description', $this->_('Determines order of presentation in interface.'),
                    'validator', 'Digits');
        $model->set('gco_code',        'label', $this->_('Consent code'),
                    'multiOptions', $this->consentUtil->getConsentTypes(),
                    'description', $this->_('Internal code, not visible to users, copied with the token information to the source.'));
        if ($detailed) {
            $model->set('gco_description', 'validator', $model->createUniqueValidator('gco_description'));
            $model->set('gco_order',       'validator', $model->createUniqueValidator('gco_order'));
        }

        if ('create' == $action || 'edit' == $action) {
            $this->modelLoader->addDatabaseTranslationEditFields($model);
        } else {
            $this->modelLoader->addDatabaseTranslations($model);
            $model->setKeys(['origKey']);
        }

        \Gems\Model::setChangeFieldsByPrefix($model, 'gco', $this->currentUserId);

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Respondent informed consent codes');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('respondent consent', 'respondent consents', $count);
    }
}