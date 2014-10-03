<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * @package    MUtil
 * @subpackage Bootstrap
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Bootstrap .php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 * Bootstrap (and less) enable an application
 *
 * @package    MUtil
 * @subpackage Bootstrap
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Bootstrap
{
    /**
     * Current default supported Bootstrap library version with MUtil_Bootstrap
     *
     * @const string
     */
    const DEFAULT_BOOTSTRAP_VERSION = "3.2.0";

    /**
     * Link to CDN http://www.bootstrapcdn.com/
     *
     * @const string
     */
    const CDN_BASE = '//maxcdn.bootstrapcdn.com/bootstrap/';

    /**
     * Location of bootstrap CSS
     *
     * @const string
     */
    const CND_CSS = '/css/bootstrap.min.css';

    /**
     * Location of bootstrap JavaScript
     *
     * @const string
     */
    const CND_JS = '/js/bootstrap.js.css';

    /**
     * Bootsrap view helper
     *
     * @var MUtil_Bootstrap_View_Helper_Bootstrap
     */
    private static $_bootstrap;

    /**
     * Returns the Bootstrapper object assigned to the view helper.
     *
     * @staticvar MUtil_Bootstrap_View_Helper_Bootstrapper $bootstrap
     * @return MUtil_Bootstrap_View_Helper_Bootstrapper
     */
    public static function bootstrap()
    {
        if (! self::$_bootstrap) {
            $helper = new MUtil_Bootstrap_View_Helper_Bootstrap();
            self::$_bootstrap = $helper->bootstrap();
        }

        return self::$_bootstrap;
    }

    /**
     * jQuery-enable a form instance
     *
     * @param  Zend_Form $form
     * @return void
     * /
    public static function enableForm(Zend_Form $form)
    {
        $form->addPrefixPath('MUtil_Bootstrap_Form_Decorator', 'MUtil/Bootstrap/Form/Decorator', 'decorator')
             ->addPrefixPath('MUtil_Bootstrap_Form_Element', 'MUtil/Bootstrap/Form/Element', 'element')
             ->addElementPrefixPath('MUtil_Bootstrap_Form_Decorator', 'MUtil/Bootstrap/Form/Decorator', 'decorator')
             ->addDisplayGroupPrefixPath('MUtil_Bootstrap_Form_Decorator', 'MUtil/Bootstrap/Form/Decorator');

        foreach ($form->getSubForms() as $subForm) {
            self::enableForm($subForm);
        }

        if (null !== ($view = $form->getView())) {
            self::enableView($view);
        }
    }

    /**
     * Bootstrap-enable a view instance
     *
     * @param  Zend_View_Interface $view
     * @return void
     */
    public static function enableView(Zend_View_Interface $view)
    {
        if (! MUtil_JQuery::usesJQuery($view)) {
            MUtil_JQuery::enableView($view);
        }

        if (false === $view->getPluginLoader('helper')->getPaths('MUtil_Bootstrap_View_Helper')) {
            $view->addHelperPath('MUtil/Bootstrap/View/Helper', 'MUtil_Bootstrap_View_Helper');
        }
    }

    /**
     * Is bootstrap enabled?
     * 
     * @return boolean
     */
    public static function enabled()
    {
        return self::$_bootstrap instanceof MUtil_Bootstrap_View_Helper_Bootstrapper;
    }

    /**
     * Check if the view or form is using Bootstrap
     *
     * @param mixed $object Zend_View_Abstract or Zend_Form
     * @return boolean
     */
    public static function usesBootstrap($object)
    {
        if ($object instanceof Zend_View_Abstract) {
            return false !== $object->getPluginLoader('helper')->getPaths('MUtil_Bootstrap_View_Helper');
        }

        /*
        if ($object instanceof Zend_Form) {
            return false !== $object->getPluginLoader(Zend_Form::DECORATOR)->getPaths('ZendX_JQuery_Form_Decorator');
        } // */

        if (is_object($object))  {
            throw new MUtil_Bootstrap_BootstrapException(
                    'Checking for Bootstrap on invalid object of class: ' . get_class($object)
                    );
        } else {
            throw new MUtil_Bootstrap_BootstrapException('Checking for Bootstrap on non-object');
        }
    }
}
