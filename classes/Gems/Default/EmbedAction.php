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

use Gems\User\Embed\EmbeddedAuthAbstract;
use Gems\User\Embed\EmbeddedAuthInterface;
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
class Gems_Default_EmbedAction extends \Gems_Controller_Action
{
    /**
     *
     * @var \Gems_User_Organization
     */
    public $currentOrganization;

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * Default key to use when no two factor key was set
     *
     * @var string
     */
    protected $defaultKey = 'test';

    /**
     *
     * @var string Algorithm for the PHP hash(0 function
     */
    protected $encryptionAlgorithm = 'sha256';

    /**
     *
     * @var boolean When true, apply base64 to encryption output
     */
    protected $encryptionBase64 = true;

    /**
     *
     * @var boolean True when hash() encryption should return raw output
     */
    protected $encryptionRaw = false;

    /**
     * Format for date part of key function
     *
     * @var string
     */
    public $keyTimeFormat = 'YmdH';

    /**
     * The number of time periods on either side of the current that is allowed
     *
     * @var int
     */
    public $keyTimeValidRange = 1;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    /**
     *
     * @param \Gems_User_User $embeddedUser
     * @param string $secretKey
     * @param string $deferredLogin The actual user
     * @param string $patientId The patient to show
     * @param mixed $organisations (Array of) organization id's or objects
     * @return boolean
     */
    protected function authenticateEmbedded(\Gems_User_User $embeddedUser, $secretKey, $deferredLogin, $patientId, $organizations)
    {
        if (! ($embeddedUser->isActive() && $embeddedUser->isEmbedded())) {
            return false;
        }

        $authClass = $embeddedUser->getSystemDeferredAuthenticator();
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
     *
     * @param \Gems_User_User $embeddedUser
     * @param string $deferredLogin
     * @return \Gems_User_User
     */
    public function getDeferredUser(\Gems_User_User $embeddedUser, $deferredLogin)
    {
        $user = $this->getUser($deferredLogin, [
            $embeddedUser->getBaseOrganizationId(),
            $embeddedUser->getCurrentOrganizationId()
            ]);

        if ($user && $user->isActive()) {
            return $user;
        }

        $model = $this->loader->getModels()->getStaffModel();
        $data  = $model->loadNew();

        $data['gsf_login']            = $deferredLogin;
        $data['gsf_id_organization']  = $embeddedUser->getBaseOrganizationId();
        $data['gsf_id_primary_group'] = $embeddedUser->getGroupId();
        $data['gsf_last_name']        = ucfirst($deferredLogin);
        $data['gsf_iso_lang']         = $embeddedUser->getLocale();
        $data['gul_user_class']       = $embeddedUser->getUserDefinitionClass();
        $data['gul_can_login']        = 1;
        // \MUtil_Echo::track($data);

        $model->save($data);

        return $this->loader->getUser($deferredLogin, $embeddedUser->getBaseOrganizationId());
    }

    /**
     * Try to find / load an active user with this data
     *
     * @param string $userLogin
     * @param mixed $organisations (Array of) organization id's or objects
     * @return \Gems_User_User
     */
    public function getUser($userLogin, $organisations = null)
    {
        // \MUtil_Echo::track($userLogin, $organisations );

        $userLoader = $this->loader->getUserLoader();

        // Set to current organization if not passed and no organization is allowed
        if ((null === $organisations) && (! $userLoader->allowLoginOnWithoutOrganization)) {
            $organisations = [$this->currentOrganization];
        }
        foreach ((array) $organisations as $currentOrg) {
            if ($currentOrg instanceof \Gems_User_Organization) {
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
     * Example Hix Login
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
        $embeddedUser = $this->getUser($epdUserLogin, $organizations);

        if ($embeddedUser &&
                $this->authenticateEmbedded($embeddedUser, $secretKey, $deferredLogin, $patientId, $organizations)) {
            $deferredUser = $this->getDeferredUser($embeddedUser, $deferredLogin);

            if (($deferredUser instanceof \Gems_User_User) && $deferredUser->isActive()) {
                $deferredUser->setAsCurrentUser();

                $group = $embeddedUser->getSystemDeferredUserGroupId();
                if ($group) {
//                    $allowedGroups = $deferredUser->getAllowedGroups();
//                    if (array_key_exists($group, $allowedGroups)) {
                        $deferredUser->setGroupSession($group);
//                    }
                }
                $this->redirectUser($embeddedUser, $deferredUser, $patientId, $organizations);
            }
        }

        throw new \Gems_Exception($this->_("Unable to authenticate"));
    }

    /**
     * Redirect the user to a specific page
     *
     * @param Gems_User_User $embeddedUser
     * @param Gems_User_User $deferredUser
     * @param $patientId string $patientId The patient to show
     * @param $organizations $organisations (Array of) organization id's or objects
     */
    protected function redirectUser(\Gems_User_User $embeddedUser, \Gems_User_User $deferredUser, $patientId, $organizations)
    {
        $redirector = $embeddedUser->getSystemDeferredRedirector();
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

        $deferredUserLayout = $embeddedUser->getSystemDeferredUserLayout();
        if ($deferredUserLayout) {
            $this->session->currentLayout = $deferredUserLayout;
        }

        $this->_helper->redirector->gotoRoute($url, null, true);
    }
}
