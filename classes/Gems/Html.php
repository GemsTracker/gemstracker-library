<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Gems specific Html elements and settings
 *
 * @package    Gems
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Html
{
    public static function actionDisabled($arg_array = null)
    {
        $args = func_get_args();

        $element = MUtil_Html::createArray('span', $args);

        $element->appendAttrib('class', 'actionlink'); // Keeps existing classes
        return $element;
    }

    public static function actionLink($arg_array = null)
    {
        $args = func_get_args();

        $element = MUtil_Html::createArray('a', $args);

        $element->appendAttrib('class', 'actionlink'); // Keeps existing classes
        return $element;
    }

    public static function buttonDiv($arg_array = null)
    {
        $args = func_get_args();

        $element = MUtil_Html::createArray('div', $args);

        $element->appendAttrib('class', 'buttons'); // Keeps existing classes
        return $element;
    }

    public static function init(MUtil_Html_Creator $creator = null)
    {
        if (null === $creator) {
            $creator = MUtil_Html::getCreator();
        }

        // MUtil_Html::$verbose = true;

        // Set the image directories
        MUtil_Html_ImgElement::addImageDir('gems/images');
        MUtil_Html_ImgElement::addImageDir('gems/icons');
        $escort = GemsEscort::getInstance();
        if (isset($escort->project->imagedir)) {
            MUtil_Html_ImgElement::addImageDir($escort->project->imagedir);
        }

        // Gems specific element functions
        $creator->addElementFunction(
            'actionDisabled', array(__CLASS__, 'actionDisabled'),
            'actionLink',     array(__CLASS__, 'actionLink'),
            'buttonDiv',      array(__CLASS__, 'buttonDiv'),
            'pagePanel',      array(__CLASS__, 'pagePanel'),
            'pInfo',          array(__CLASS__, 'pInfo'),
            'smallData',      array(__CLASS__, 'smallData'));


        // Gems_Util::callProjectClass('Html', 'init', $creator);
        // Allow in-project overruling
        $projectFile = APPLICATION_PATH . '/classes/' . GEMS_PROJECT_NAME_UC . '/Html.php';
        if (file_exists($projectFile)) {
            include_once($projectFile);
            call_user_func(array(GEMS_PROJECT_NAME_UC . '_Html', 'init'), $creator);
        } // */

        return $creator;
    }

    public static function pagePanel($paginator = null, $request = null, $translator = null, $args = null)
    {
        $types = array(
            'paginator'  => 'Zend_Paginator',
            'request'    => 'Zend_Controller_Request_Abstract',
            'translator' => 'Zend_Translate',
            'view'       => 'Zend_View',
        );

        $args = MUtil_Ra::args(func_get_args(), $types, null, MUtil_Ra::STRICT);

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
            $translator = Zend_Registry::get('Zend_Translate');
        }
        if (isset($args['class'])) {
            if ($args['class'] instanceof MUtil_Html_AttributeInterface) {
                $args['class']->add('browselink');
            } else {
                $args['class'] = new MUtil_Html_ClassArrayAttribute('browselink', $args['class']);;
            }
        } else {
            $args['class'] = new MUtil_Html_ClassArrayAttribute('browselink');
        }

        // MUtil_Echo::r($args);
        $pager = new MUtil_Html_PagePanel($panel_args);

        $pager[] = $pager->pageLinks(
            array($translator->_('<< First'),   'class' => new MUtil_Html_ClassArrayAttribute('browselink', 'keyHome')),
            array($translator->_('< Previous'), 'class' => new MUtil_Html_ClassArrayAttribute('browselink', 'keyPgUp')),
            array($translator->_('Next >'),     'class' => new MUtil_Html_ClassArrayAttribute('browselink', 'keyPgDn')),
            array($translator->_('Last >>'),    'class' => new MUtil_Html_ClassArrayAttribute('browselink', 'keyEnd')),
            $translator->_(' | '), $args);

        $pager->div(
            $pager->uptoOffDynamic(
                $translator->_('to'),
                $translator->_('of'),
                array('-', 'class' => new MUtil_Html_ClassArrayAttribute('browselink', 'keyCtrlUp')),
                array('+', 'class' => new MUtil_Html_ClassArrayAttribute('browselink', 'keyCtrlDown')),
                null,
                ' ',
                $args),
            array('class' => 'rightFloat'));

        return $pager;
    }

    public static function pInfo($arg_array = null)
    {
        $args = func_get_args();

        $element = MUtil_Html::createArray('p', $args);

        $element->appendAttrib('class', 'info'); // Keeps existing classes
        return $element;
    }

    public static function smallData($value, $args_array = null)
    {
        $args = func_get_args();

        $element = MUtil_Lazy::iff($value, MUtil_Html::createArray('small', array(' [', $args, ']')));

        return $element;
    }
}
