<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CurrentButtonRowSnippet
 *
 * @author 175780
 */
class EMC_Snippets_Respondent_Relation_CurrentButtonRowSnippet extends MUtil_Snippets_SnippetAbstract {
    /**
     * Required
     *
     * @var Gems_Menu
     */
    protected $menu;

    /**
     * Required
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $menuList = $this->menu->getMenuList();
        
        $children = $this->menu->getCurrentParent()->getChildren();
        foreach($children as $child) {
            if ($child->get('action') == 'show') {
                break;
            }
        }

        $menuList->addParameterSources($this->request)
                 ->addMenuItem($child, $this->_('Cancel'))
//                ->addCurrentParent($this->_('Cancel'))
                ->addCurrentChildren();

        return $menuList;
    }
}