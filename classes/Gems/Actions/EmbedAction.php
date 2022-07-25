<?php

/**
 * Electronic Patient Dossier Embed Controller
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Actions;

use Gems\User\Embed\EmbeddedAuthAbstract;
use Gems\User\Embed\EmbeddedAuthInterface;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\Embed\RedirectAbstract;
use Gems\User\Embed\RedirectInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 14-Aug-2019 16:03:20
 */
class EmbedAction extends \Gems\Controller\Action
{
    /**
     * Embed specific log
     *
     * @var \Gems\Log
     */
    protected $activityLog;

    /**
     *
     * @var \Gems\User\Organization
     */
    public $currentOrganization;

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $embeddedLoginLog;

    /**
     *
     * @var \Gems\Menu
     */
    public $menu;

    /**
     *
     * @param \Gems\User\User $embeddedUser
     * @param string $secretKey
     * @param string $deferredLogin The actual user
     * @param string $patientId The patient to show
     * @param mixed $organisations (Array of) organization id's or objects
     * @return boolean
     */
    protected function authenticateEmbedded(\Gems\User\User $embeddedUser, $secretKey, $deferredLogin, $patientId, $organizations)
    {
        $embeddedUserData = $embeddedUser->getEmbedderData();
        if (! ($embeddedUser->isActive() && $embeddedUserData instanceof EmbeddedUserData)) {
            return false;
        }

        $authClass = $embeddedUserData->getAuthenticator();
        if ($authClass instanceof EmbeddedAuthAbstract) {
            $authClass->setDeferredLogin($deferredLogin);
            $authClass->setPatientNumber($patientId);
            $authClass->setOrganizations($organizations);
        }

        if ($authClass instanceof EmbeddedAuthInterface) {
            return $authClass->authenticate($embeddedUser, $secretKey);
        }

        return false;
    }

    /**
     * Try to find / load an active user with this data
     *
     * @param string $userLogin
     * @param mixed $organisations (Array of) organization id's or objects
     * @return \Gems\User\User
     */
    public function getUser($userLogin, $organisations = null)
    {
        // \MUtil\EchoOut\EchoOut::track($userLogin, $organisations );

        $userLoader = $this->loader->getUserLoader();

        // Set to current organization if not passed and no organization is allowed
        if ((null === $organisations) && (! $userLoader->allowLoginOnWithoutOrganization)) {
            $organisations = [$this->currentOrganization];
        }
        foreach ((array) $organisations as $currentOrg) {
            if ($currentOrg instanceof \Gems\User\Organization) {
                $user = $userLoader->getUser($userLogin, $currentOrg->getId());
            } else {
                $user = $userLoader->getUser($userLogin, $currentOrg);
            }
            if ($user->isActive()) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Example Hix Login example
     * /
    public function hixLogin()
    {
        $request = $this->getRequest();

        $this->loginEmbedded(
                'HiX',
                $request->getParam('key'),
                $request->getParam('usr'),
                $request->getParam('pid'),
                array_keys($this->util->getDbLookup()->getOrganizationsByCode('hix'))
                );
    }

    /**
     * @param  string   $message   Message to log
     * @param  integer  $priority  Priority of message
     */
    public function logActivity($message, $priority)
    {
        try {
            $this->embeddedLoginLog->log($message, $priority);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            error_log($message);
        }
    }

    /**
     * Generic EPD login with all information passed in url
     */
    public function loginAction()
    {
        $request = $this->getRequest();

        $this->loginEmbedded(
                $request->getParam('epd'),
                $request->getParam('key'),
                $request->getParam('usr'),
                $request->getParam('pid'),
                $request->getParam('org')
                );
    }

    /**
     *
     * @param string $epdUserLogin Embedded user for EPD
     * @param string $secretKey Pass code
     * @param string $deferredLogin The actual user
     * @param string $patientId The patient to show
     * @param mixed $organizations  (Array of) organization id's or objects
     */
    protected function loginEmbedded($epdUserLogin, $secretKey, $deferredLogin, $patientId, $organizations = null)
    {
        $this->logActivity(
            "Login user: $epdUserLogin, end user: $deferredLogin, patient: $patientId, key: $secretKey",
            \Psr\Log\LogLevel::NOTICE
        );
        $embeddedUser = $this->getUser($epdUserLogin, $organizations);

        if ($embeddedUser && $this->authenticateEmbedded($embeddedUser, $secretKey, $deferredLogin, $patientId, $organizations)) {
            $embeddedUserData = $embeddedUser->getEmbedderData();
            $deferredUser     = $embeddedUserData->getDeferredUser($embeddedUser, $deferredLogin);

            if (($deferredUser instanceof \Gems\User\User) && $deferredUser->isActive()) {
                $deferredUser->setAsCurrentUser();
                $this->redirectUser($embeddedUser, $deferredUser, $patientId, $organizations);

                return;
            }
        }
        $this->logActivity(
            "Failed EPD authentication: login user: $epdUserLogin, end user: $deferredLogin, patient: $patientId, key: $secretKey",
            \Psr\Log\LogLevel::WARNING
        );

        throw new \Gems\Exception($this->_("Unable to authenticate"));
    }

    /**
     * Redirect the user to a specific page
     *
     * @param \Gems\User\User $embeddedUser
     * @param \Gems\User\User $deferredUser
     * @param $patientId string $patientId The patient to show
     * @param $organizations $organisations (Array of) organization id's or objects
     */
    protected function redirectUser(\Gems\User\User $embeddedUser, \Gems\User\User $deferredUser, $patientId, $organizations)
    {
        $embeddedUserData = $embeddedUser->getEmbedderData();
        $redirector       = $embeddedUserData->getRedirector();

        if ($redirector instanceof RedirectAbstract) {
            $redirector->answerRegistryRequest('request', $this->getRequest());
        }

        if ($redirector instanceof RedirectInterface) {
            $url = $redirector->getRedirectRoute($embeddedUser, $deferredUser, $patientId, $organizations);
        } else {
            $url = null;
        }

        if (null === $url) {
            // Back to start screen
            $url = [
                'controller' => 'index',
                'action'     => 'index',
            ];
        }

        $this->logActivity(
            sprintf("Rerouting EPD Login to %s", implode('/', $url)),
            \Psr\Log\LogLevel::DEBUG
        );
        $this->_helper->redirector->gotoRoute($url, null, true);
    }
}
