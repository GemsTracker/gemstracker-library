<?php

/**
 *
 * @package    Gems
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

use MUtil\Html\Creator;
use Zalt\Html\AElement;
use Zalt\Html\ElementInterface;
use Zalt\Html\ImgElement;
use Zalt\Late\Late;

/**
 * \Gems specific Html elements and settings
 *
 * @package    Gems
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Html extends \Zalt\Html\Html
{
    public static function actionDisabled(...$args): ElementInterface
    {
        $element = parent::createArray('span', $args);
        $element->appendAttrib('class', 'actionlink btn disabled'); // Keeps existing classes
        return $element;
    }

    public static function actionLink(...$args): AElement
    {
        $element = parent::createArray('a', $args);
        $element->appendAttrib('class', 'actionlink btn'); // Keeps existing classes
        return $element;
    }

    public static function buttonDiv(...$args): ElementInterface
    {
        $element = parent::createArray('div', $args);
        $element->appendAttrib('class', 'buttons'); // Keeps existing classes
        return $element;
    }

    public static function init(\MUtil\Html\Creator $creator = null): ?Creator
    {
        if (null === $creator) {
            $mutilCreator = \MUtil\Html::getCreator();
            $zaltCreator = parent::getCreator();
        }

        // \MUtil\Html::$verbose = true;

        // Set the image directories
        \MUtil\Html\ImgElement::addImageDir('gems-responsive/images/icons');

        $addFunctions = [
            'actionDisabled', [__CLASS__, 'actionDisabled'],
            'actionLink',     [__CLASS__, 'actionLink'],
            'buttonDiv',      [__CLASS__, 'buttonDiv'],
            'pagePanel',      [__CLASS__, 'pagePanel'],
            'pInfo',          [__CLASS__, 'pInfo'],
            'smallData',      [__CLASS__, 'smallData'],
            ]; 
        
        // \Gems specific element functions
        $mutilCreator->addElementFunction(...$addFunctions);
        $zaltCreator->addElementFunction(...$addFunctions);

        return $creator;
    }

    /**
     * Create a page panel
     *
     * @param mixed $paginator \MUtil\Ra::args() arguements
     * @param mixed $request
     * @param mixed $translator
     * @param mixed $args
     * @return \MUtil\Html\PagePanel
     */
    public static function pagePanel($paginator = null, $request = null, $translator = null, $args = null)
    {
        $types = array(
            'paginator'  => 'Zend_Paginator',
            'request'    => 'Zend_Controller_Request_Abstract',
            'translator' => 'Zend_Translate',
            'view'       => 'Zend_View',
        );

        $args = \MUtil\Ra::args(func_get_args(), $types, null, \MUtil\Ra::STRICT);

        $panel_args = array();
        foreach (array('baseUrl', 'paginator', 'request', 'scrollingStyle', 'view', 'itemCount') as $var) {
            if (isset($args[$var])) {
                $panel_args[$var] = $args[$var];
                unset($args[$var]);
            }
        }
        if (isset($args['translator'])) {
            $translator = $args['translator'];
            unset($args['translator']);
        } else {
            $translator = \Zend_Registry::get('Zend_Translate');
        }
        if (isset($args['class'])) {
            if ($args['class'] instanceof \MUtil\Html\AttributeInterface) {
                $args['class']->add('browselink');
            } else {
                $args['class'] = new \MUtil\Html\ClassArrayAttribute('browselink', $args['class']);;
            }
        } else {
            $args['class'] = new \MUtil\Html\ClassArrayAttribute('browselink');
        }

        // \MUtil\EchoOut\EchoOut::track($args);
        // \MUtil\EchoOut\EchoOut::track($panel_args['baseUrl']);
        $pager = new \MUtil\Bootstrap\Html\PagePanel($panel_args);

        $pager[] = $pager->pageLinks(
            array($translator->_('<< First'),   'class' => new \MUtil\Html\ClassArrayAttribute('browselink', 'keyHome')),
            array($translator->_('< Previous'), 'class' => new \MUtil\Html\ClassArrayAttribute('browselink', 'keyPgUp')),
            array($translator->_('Next >'),     'class' => new \MUtil\Html\ClassArrayAttribute('browselink', 'keyPgDn')),
            array($translator->_('Last >>'),    'class' => new \MUtil\Html\ClassArrayAttribute('browselink', 'keyEnd')),
            $translator->_(' | '), $args);

        $pager->div(
            $pager->uptoOffDynamic(
                $translator->_('to'),
                $translator->_('of'),
                array('-', 'class' => new \MUtil\Html\ClassArrayAttribute('browselink btn btn-xs', 'keyCtrlUp')),
                array('+', 'class' => new \MUtil\Html\ClassArrayAttribute('browselink btn btn-xs', 'keyCtrlDown')),
                null,
                ' ',
                $args),
            array('class' => 'pagination-index rightFloat pull-right'));

        return $pager;
    }

    public static function pInfo($arg_array = null)
    {
        $args = func_get_args();

        $element = parent::createArray('p', $args);

        $element->appendAttrib('class', 'info'); // Keeps existing classes
        return $element;
    }

    public static function smallData($value, $args_array = null)
    {
        $args = func_get_args();
        $element = Late::iff($value, parent::createArray('small', array(' [', $args, ']')));
        return $element;
    }
}
