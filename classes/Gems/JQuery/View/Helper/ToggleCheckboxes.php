<?php
/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Short description for ToggleCheckboxes
 *
 * Long description for class ToggleCheckboxes (if any)...
 *
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_JQuery_View_Helper_ToggleCheckboxes extends \ZendX_JQuery_View_Helper_UiWidget
{
    //put your code here
    public function toggleCheckboxes($id, $value = null, array $params = array())
    {

        $js = sprintf('%1$s("#%2$s").click(function(){
            var checkboxes = %1$s("%3$s");
            checkboxes.prop("checked", !checkboxes.prop("checked"));
});',
                \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
                $id,
                $params['selector']
        );
        unset($params['selector']);

        $this->jquery->addOnLoad($js);

        //Now do something to add the jquery stuff
        return $this->view->formButton($id, $value, $params);
    }
}