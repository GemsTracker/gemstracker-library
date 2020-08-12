<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Mail;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class CommJobShowSnippet extends \Gems_Snippets_ModelItemTableSnippetGeneric
{
    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        $this->menuList = $this->getMenuList();
    }


    /**
     * overrule to add your own buttons.
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $id   = $this->request->getParam(\MUtil_Model::REQUEST_ID);
        $prev = $this->db->fetchOne(
            "SELECT gcj_id_job FROM gems__comm_jobs 
                WHERE gcj_id_order < (SELECT gcj_id_order FROM gems__comm_jobs WHERE gcj_id_job = ?) 
                ORDER BY gcj_id_order DESC",
            $id) ?: null;
        $next = $this->db->fetchOne(
            "SELECT gcj_id_job FROM gems__comm_jobs 
                WHERE gcj_id_order > (SELECT gcj_id_order FROM gems__comm_jobs WHERE gcj_id_job = ?) 
                ORDER BY gcj_id_order ASC",
            $id) ?: null;

        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->append($this->menu->getCurrent()->toActionLink(true, \MUtil_Html::raw($this->_('&lt; Previous')), [\MUtil_Model::REQUEST_ID => $prev]));
        $links->addCurrentParent($this->_('Cancel'));
        $links->addCurrentChildren();
        $links->addCurrentSiblings();

        $links->append($this->menu->getCurrent()->toActionLink(true, \MUtil_Html::raw($this->_('Next &gt;')), [\MUtil_Model::REQUEST_ID => $next]));

        return $links;
    }
}