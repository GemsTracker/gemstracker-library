<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Login;

use Gems\Snippets\FormSnippetAbstract;
use Gems\User\TwoFactor\SendTwoFactorCodeInterface;
use Gems\User\Validate\TwoFactorAuthenticateValidator;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Login
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 28-Jun-2018 18:46:31
 */
class TwoFactorCheckSnippet extends FormSnippetAbstract
{
    /**
     *
     * @var boolean
     */
    private $_result = false;

    /**
     *
     * @var \Gems\User\LoginStatusTracker
     */
    protected $loginStatusTracker;

    protected $redirectRoute;

    /**
     * A parameter that if true resets the queue
     *
     * @var string
     */
    protected $resetParam;

    /**
     *
     * @var \Gems_User_User
     */
    protected $user;

    /**
     * @var \Zend_View
     */
    protected $view;

    /**
     *
     * @return string
     */
    protected function _getIp()
    {
        //In unit test REMOTE_ADDR is not available and will return null
        // E.g. command line user
        if (! $this->request instanceof \Zend_Controller_Request_Http) {
            return false;
        }

        return $this->request->getClientIp();
    }

    /**
     * Simple default function for making sure there is a $this->_saveButton.
     *
     * As the save button is not part of the model - but of the interface - it
     * does deserve it's own function.
     */
    protected function addCancelButton()
    {
        $cancelUrl  = [
            $this->request->getControllerKey() => $this->request->getControllerName(),
            $this->request->getActionKey()     => $this->request->getActionName(),
            $this->resetParam                  => 1,
        ];

        $element = $this->_form->createElement('html', 'reset');
        $element->setLabel(html_entity_decode('&nbsp;'));
        $element->setValue(\Gems_Html::actionLink($cancelUrl, $this->_('Cancel login')));
        $this->_form->addElement($element);
    }

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        if (! $this->user->hasTwoFactor()) {
            return;
        }

        $authenticator = $this->user->getTwoFactorAuthenticator();
        if ($authenticator instanceof SendTwoFactorCodeInterface && (!isset($this->formData['TwoFactor']) || empty($this->formData['TwoFactor']))) {
            try {
                $authenticator->sendCode($this->user);
                $this->addMessage($authenticator->getSentMessage($this->user));
            } catch (\Gems_Exception $e) {
                $this->addMessage($e->getMessage());
            }
            if ($authenticator instanceof SendTwoFactorCodeInterface) {
                try {
                     if ($authenticator->canRetry($this->user)) {
                        $this->addMessage($this->getRetryMessage());
                    }
                } catch(\Gems_Exception $e) {
                }
            }
        }

        $this->saveLabel = $this->_('Check code');

        $description = $authenticator->getCodeInputDescription();

        $options = [
            'label'       => $this->_('Enter authenticator code'),
            'description' => $description,
            'maxlength'   => 6,
            'minlength'   => 6,
            'required'    => true,
            'size'        => 8,
            ];

        $element = $form->createElement('Text', 'TwoFactor', $options);
        $element->addValidator(new TwoFactorAuthenticateValidator(
                $authenticator,
                $this->user->getTwoFactorKey(),
                $this->translate
                ));

        $form->addElement($element);
    }

    /**
     * Simple default function for making sure there is a $this->_saveButton.
     *
     * As the save button is not part of the model - but of the interface - it
     * does deserve it's own function.
     */
    protected function addSaveButton()
    {
        parent::addSaveButton();

        $this->addCancelButton();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->routeController = $this->request->getControllerName();
        $this->routeAction     = $this->request->getActionName();

        if ($this->loginStatusTracker->hasUser()) {
            $this->user = $this->loginStatusTracker->getUser();
        }
    }

    public function getRedirectRoute()
    {
        return $this->redirectRoute;
    }

    protected function getRetryMessage()
    {
        $href = [
            $this->request->getControllerKey() => $this->request->getControllerName(),
            $this->request->getActionKey() => $this->request->getActionName(),
            'resend' => 1,
        ];
        if ($href) {
            $link = \MUtil_Html::create('a', $href, 'Click here');


            $clickLink = $link->setView($this->view);
            return new \MUtil_Html_Raw(sprintf(
                $this->_('%s to retry sending the message'),
                $clickLink
            ));
        }
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->user->hasTwoFactor()) {
            return $this->_('Two factor authentication');
        } else {
            return $this->_('Two factor authentication required but not set!');
        }
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('two factor authentication', 'two factor authentication', $count);
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->user && $this->user->isTwoFactorRequired($this->_getIp())) {
            if (! $this->user->hasTwoFactor()) {
                $this->addMessage($this->_('Two factor authentication is required to login from this location!'));

                $this->loadForm();
                $this->addCancelButton();
                return true;
            }

            parent::hasHtmlOutput();

            return ! $this->_result;
        }

        return false;
    }

    /**
     * Hook that allows actions when the input is invalid
     *
     * When not rerouted, the form will be populated afterwards
     */
    protected function onInValid()
    {
        // We disable this standard error message as we are not changing anything here
        //$this->addMessage(sprintf($this->_('Input error! Changes to %s not saved!'), $this->getTopic()));

        if ($this->_csrf) {
            if ($this->_csrf->getMessages()) {
                $this->addMessage($this->_('The form was open for too long or was opened in multiple windows.'));
            }
        }
    }

    /**
     * Step by step form processing
     *
     * Returns false when $this->afterSaveRouteUrl is set during the
     * processing, which happens by default when the data is saved.
     *
     * @return boolean True when the form should be displayed
     */
    protected function processForm()
    {
        if ($this->request->getParam('resend') == '1') {
            $authenticator = $this->user->getTwoFactorAuthenticator();
            if ($authenticator instanceof SendTwoFactorCodeInterface) {
                try {
                    $authenticator->enableRetrySendCode($this->user);
                } catch (\Gems_Exception $e) {

                }
                /*$this->redirectRoute = [
                    $this->request->getControllerKey()  => $this->request->getControllerName(),
                    $this->request->getActionKey()      => $this->request->getActionName(),
                ];
                $this->redirectRoute();*/
            }
        }
        parent::processForm();
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData()
    {
        $this->_result = true;
    }
}
