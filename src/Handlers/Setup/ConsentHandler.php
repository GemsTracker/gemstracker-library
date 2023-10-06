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
use Gems\Model\MetaModelLoader;
use Gems\Repository\ConsentRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Sql\SqlTableModel;
use Zalt\SnippetsLoader\SnippetResponderInterface;
use Zalt\Validator\Model\ModelUniqueValidator;

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
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected MetaModelLoader $metaModelLoader,
        protected ConsentRepository $consentRepository,
    ) {
        parent::__construct($responder, $translate);
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
    public function createModel(bool $detailed, string $action): DataReaderInterface
    {
        $model = $this->metaModelLoader->createModel(SqlTableModel::class, 'gems__consents');
        
        $metaModel = $model->getMetaModel();
        $metaModel->setKeys(['gco_description' => 'gco_description']);
        $metaModel->set('gco_description', 'label', $this->_('Description'), 'size', '10', 'translate', true);

        $metaModel->set('gco_order',       'label', $this->_('Order'), 'size', '10',
                    'description', $this->_('Determines order of presentation in interface.'),
                    'validator', 'Digits');
        $metaModel->set('gco_code',        'label', $this->_('Consent code'),
                    'multiOptions', $this->consentRepository->getConsentTypes(),
                    'description', $this->_('Internal code, not visible to users, copied with the token information to the source.'));
        if ($detailed) {
            $metaModel->set('gco_description', 'validator', ModelUniqueValidator::class);
            $metaModel->set('gco_order',       'validator', ModelUniqueValidator::class);
        }

        if ('create' == $action || 'edit' == $action) {
            // $this->metaModelLoader->addDatabaseTranslationEditFields($metaModel);
        } else {
            // $this->metaModelLoader->addDatabaseTranslations($metaModel);
        }

        $this->metaModelLoader->setChangeFields($metaModel, 'gco');

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