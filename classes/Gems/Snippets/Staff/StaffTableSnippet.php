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

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 24-sep-2015 16:23:26
 */
class StaffTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('name' => SORT_ASC);

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'staff';

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if (! $this->columns) {
            $br = \MUtil_Html::create('br');
            
            $this->columns = array(
                10 => array('gsf_login', $br, 'gsf_id_primary_group'),
                20 => array('name', $br, 'gsf_email'),
                30 => array('gsf_id_organization', $br, 'gsf_gender'),
                40 => array('gsf_active', $br, 'has_2factor'),
            );
        }

        parent::addBrowseTableColumns($bridge, $model);
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if ($this->model instanceof \Gems_Model_StaffModel) {
            $model = $this->model;
        } else {
            $model = $this->loader->getModels()->getStaffModel();
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return \Gems_Menu_SubMenuItem
     */
    protected function getEditMenuItems()
    {
        $resets = $this->findMenuItems($this->menuActionController, 'reset');
        foreach ($resets as $resetPw) {
            if ($resetPw instanceof \Gems_Menu_SubMenuItem) {
                $resetPw->set('label', $this->_('password'));
            }
        }
        return array_merge(
                parent::getEditMenuItems(),
                $resets,
                $this->findMenuItems($this->menuActionController, 'mail')
                );
    }
}
