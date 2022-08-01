<?php
/**
 *
 * @package    Gems
 * @subpackage View\Helper
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */
namespace Gems\View\Helper;

class ColorPicker extends \ZendX_JQuery_View_Helper_ColorPicker
{
    public function colorPicker($id, $value='', array $params=array(), array $attribs=array())
    {
	    $attribs = $this->_prepareAttributes($id, $value, $attribs);

	    if(strlen($value) >= 6) {
	        $params['color'] = $value;
	    }

            $params['showInput'] = true;
            $params['preferredFormat'] = "hex";

	    if(count($params) > 0) {
            $params = \ZendX_JQuery::encodeJson($params);
	    } else {
	        $params = "{}";
	    }

        $js = sprintf('%s("#%s").spectrum(%s);',
            \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
            $attribs['id'],
            $params
        );

        $this->jquery->addOnLoad($js);

        $baseUrl = \Gems\Escort::getInstance()->basepath->getBasePath();
        $this->view->headScript()->appendFile($baseUrl . '/gems/spectrum/spectrum.js');
        $this->view->headLink()->appendStylesheet($baseUrl . '/gems/spectrum/spectrum.css');
        //$z = new \Zend_View_Helper_HeadStyle()->append($baseUrl)

	    return $this->view->formText($id, $value, $attribs);
    }
}
