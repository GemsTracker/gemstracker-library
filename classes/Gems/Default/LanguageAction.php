<?php

/**
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Allows the user to switch interface language.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_LanguageAction extends \Gems_Controller_Action
{
    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    public function changeUiAction()
    {
        $request = $this->getRequest();

        $lang = strtolower($request->getParam('language'));
        $url  = base64_decode($request->getParam('current_uri'));

        if ((! $url) || ('/' !== $url[0])) {
            throw new \Exception($this->_('Illegal language redirect url.'));
        }

        if (in_array($lang, $this->view->project->locales)) {

            $this->currentUser->setLocale($lang);

            if ($this->currentUser->switchLocale($lang)) {
                if ($url) {
                    $this->getResponse()->setRedirect($url);
                } else {
                    $this->currentUser->gotoStartPage($this->menu, $this->getRequest());
                }
                return;
            }

            throw new \Exception($this->_('Cookies must be enabled for setting the language.'));
        }

        throw new \Exception($this->_('Invalid language setting.'));
    }
}
