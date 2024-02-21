<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Staff;

use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\StaffModel;
use Gems\Snippets\ModelTableSnippetAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 24-sep-2015 16:23:26
 */
class StaffTableSnippet extends ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = ['name' => SORT_ASC];

    /**
     * Menu routes or routeparts to show in Edit box.
     */
    public array $menuEditRoutes = ['reset', 'edit'];

    /**
     *
     * @var MetaModelInterface
     */
    protected $model;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected Model $modelLoader,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        if (! $this->columns) {
            $br = Html::create('br');
            
            $this->columns = array(
                10 => array('gsf_login', $br, 'gsf_id_primary_group'),
                20 => array('name', $br, 'gsf_email'),
                30 => array('gsf_id_organization', $br, 'gsf_gender'),
                40 => array('gsf_active', $br, 'has_authenticator_tfa'),
            );
        }

        parent::addBrowseTableColumns($bridge, $dataModel);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if ($this->model instanceof StaffModel) {
            $model = $this->model;
        } else {
            /**
             * @var StaffModel $model
             */
            $model = $this->modelLoader->getStaffModel();
        }

        return $model;
    }
}