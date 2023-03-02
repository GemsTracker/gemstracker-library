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
use Gems\Model\MetaModelLoader;
use Gems\Repository\AccessRepository;
use Gems\Repository\OrganizationRepository;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Show\ShowAction;
use Gems\Util\Translated;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\Sql\SqlTableModel;
use Zalt\SnippetsActions\BrowseTableAction;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

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
        protected AccessRepository $accessRepository,
        protected OrganizationRepository $organizationRepository,
        protected Translated $translatedUtil,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate);
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
        ]);
        $metaModel->set('gm_id_order', [
            'label' => $this->_('Order'),
        ]);
        $metaModel->set('gm_group', [
            'label' => $this->_('Group'),
            'multiOptions' => $this->accessRepository->getGroups(),
        ]);
        $metaModel->set('gm_id_organization', [
            'label' => $this->_('Organization'),
            'multiOptions' => $this->translatedUtil->getEmptyDropdownArray() + $this->organizationRepository->getOrganizations(),
        ]);
        // gm_mask_settings
        
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
            $action->onEmpty = $this->_('No masks found!');
            
            if ($action instanceof BrowseSearchAction) {
                $action->contentTitle = $this->_('Masks');
                $action->addCurrentParent = false;
            }
        }
        
        if ($action instanceof ShowAction) {
            $action->contentTitle = sprintf($this->_('Showing %s'), $this->getTopic(1));
        }
    }
}