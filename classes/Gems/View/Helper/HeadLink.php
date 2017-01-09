<?php
/**
 *
 * @package    Gems
 * @subpackage View\Helper
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */



/**
 * Make sure each .less css script is compiled to .css
 *
 * @package    Gems
 * @subpackage View\Helper
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5
 */
class Gems_View_Helper_HeadLink extends \MUtil_Less_View_Helper_HeadLink
{
    public function __construct()
    {
        parent::__construct();
        $this->_webroot = GEMS_WEB_DIR;
    }

    /**
     * Create HTML link element from data item
     *
     * @param  \stdClass $item
     * @return string
     */
    public function itemToString(\stdClass $item)
    {

    	$attributes = (array) $item;

        if (isset($attributes['type']) &&
                (($attributes['type'] == 'text/css') || ($attributes['type'] == 'text/less'))) {

            // This is a stylesheet, consider extension and compile .less to .css
            if (($attributes['type'] == 'text/less') || \MUtil_String::endsWith($attributes['href'], '.less', true)) {
                $this->compile($this->view, $attributes['href'], false);

                // Modify object, not the derived array
                $item->type = 'text/css';
                $item->href = substr($attributes['href'], 0, -4) . 'css';
            }
        }

        if (isset($this->view->currentVersion)) {
            $version = $this->view->currentVersion;

            if (property_exists($item, 'href')) {
                $item->href = $item->href . '?' . $version;
            }
        }
        
        return \Zend_View_Helper_HeadLink::itemToString($item);
    }

}