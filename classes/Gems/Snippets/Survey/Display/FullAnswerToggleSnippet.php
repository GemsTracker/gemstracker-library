<?php
/**
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Display survey answers with a toggle for full or compact view
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Snippets_Survey_Display_FullAnswerToggleSnippet extends \MUtil_Snippets_SnippetAbstract {

    /**
     *
     * @var \Zend_Controller_Request_Http
     */
    protected $request;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $html = $this->getHtmlSequence();

        $request = $this->request;
        $html->hr(array('class'=>'noprint'));
        $params = $request->getParams();
        $state = $params;                   // Use current state for pdf export

        if (isset($params['fullanswers'])) {
            unset($params['fullanswers']);
        } else {
            $params['fullanswers'] = 1;
        }

        $url = array('controller' => $request->getControllerName(),
            'action' => $request->getActionName(),
            'routereset' => true) + $params;
        $html->actionLink($url, $this->_('Toggle'));

        // Now add the menulist all buttons under answer
        $menuList = $this->menu->getMenuList();
        $menuList->addParameterSources($this->menu->getParameterSource())
                 ->addCurrentChildren();

        $html[] = $menuList;
        $html->hr(array('class'=>'noprint'));

        return $html;
    }

    public function hasHtmlOutput() {
        // Only show toggle for individual answer display
        if ($this->request->getActionName() !== 'answer') {
            return false;
        }

        return true;
    }
}