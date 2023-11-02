<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers\Setup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\BrowseChangeHandler;
use Gems\Model\Dependency\ActivationDependency;
use Gems\Model\MetaModelLoader;
use Gems\Repository\AccessRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Snippets\Mask\MaskUsageInformation;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Show\ShowAction;
use Gems\SnippetsLoader\GemsSnippetResponder;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\Sql\SqlTableModel;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Model\Type\ConcatenatedType;
use Zalt\Model\Type\YesNoType;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\BrowseTableAction;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 *
 * @package    Gems
 * @subpackage Handlers\Setup
 * @since      Class available since version 1.9.2
 */
class MaskHandler extends BrowseChangeHandler
{
    use \Zalt\SnippetsHandler\CreateModelHandlerTrait;

    public function __construct(
        SnippetResponderInterface $responder, 
        MetaModelLoader $metaModelLoader, 
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected AccessRepository $accessRepository,
        protected MaskRepository $maskRepository,
        protected OrganizationRepository $organizationRepository,
        protected Translated $translatedUtil,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    /**
     * @inheritDoc
     */
    protected function createModel(SnippetActionInterface $action): MetaModellerInterface
    {
        $model = $this->metaModelLoader->createModel(SqlTableModel::class, 'gems__masks');
        
        $metaModel = $model->getMetaModel();
        $metaModel->getKeys();
        $metaModel->resetOrder();
        
        $metaModel->set('gm_description', [
            'label' => $this->_('Description'),
            'required' => true,
            'validators[unique]' => ModelUniqueValidator::class
        ]);
        $metaModel->set('gm_id_order', [
            'label' => $this->_('Order'),
            'description' => $this->_('The first mask (the one with the lowest Order) that covers the current user is used.'),
            'validators[unique]' => ModelUniqueValidator::class
        ]);
        $metaModel->set('gm_groups', [
            'label' => $this->_('Groups'),
            'description' => $this->_('No group is the same as all groups.'),
            'elementClass' => 'MultiCheckbox',
            'multiOptions' => $this->accessRepository->getGroups(false),
            'type' => new ConcatenatedType(':', $this->_(', '), true),
        ]);
        $metaModel->set('gm_organizations', [
            'label' => $this->_('Organization'),
            'description' => $this->_('No organization is the same as all organizations.'),
            'elementClass' => 'MultiCheckbox',
            'multiOptions' => $this->organizationRepository->getOrganizations(),
            'type' => new ConcatenatedType(':', $this->_(', '), true),
        ]);
        $metaModel->set('gm_mask_sticky', [
            'label' => $this->_('Enforce'),
            'description' => $this->_('Enforced masks are compared using both users base organization and group as well as their current organization and group.'),
            'type' => new YesNoType($this->translatedUtil->getYesNo()),
        ]);
        $metaModel->set('gm_mask_active', [
            'label' => $this->_('Active'),
            'description' => $this->_('Only active masks are used.'),
            'type' => new ActivatingYesNoType($this->translatedUtil->getYesNo(), 'row_class'),
        ]);

        if ($action->isDetailed()) {
            $this->maskRepository->addMaskStorageTo($metaModel, 'gm_mask_settings', $action->isDetailed());

            if ($this->responder instanceof GemsSnippetResponder) {
                $menuHelper = $this->responder->getMenuSnippetHelper();
            } else {
                $menuHelper = null;
            }
            $metaModel->addDependency(new ActivationDependency(
                $this->translate,
                $metaModel,
                $menuHelper,
            ));
        }

        $this->metaModelLoader->setChangeFields($metaModel, 'gm');
        
        return $model;   
    }

    public function getTopic(int $count = 1) : string
    {
        return $this->plural('mask', 'masks', $count);
    }

    public function prepareAction(SnippetActionInterface $action) : void
    {
        parent::prepareAction($action);

        if ($action instanceof BrowseFilteredAction) {
            $action->extraSort['gm_id_order'] = SORT_ASC;
            $action->onEmpty = $this->_('No masks found!');
            
            if ($action instanceof BrowseSearchAction) {
                $action->appendStopSnippet(MaskUsageInformation::class);
                $action->contentTitle = $this->_('Masks');
                $action->addCurrentParent = false;
            }
        } elseif ($action instanceof AbstractAction) {
            $action->appendSnippet(MaskUsageInformation::class);
        }
        
        if ($action instanceof ShowAction) {
            $action->contentTitle = sprintf($this->_('Showing %s'), $this->getTopic(1));
        }
    }
}