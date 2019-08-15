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
     * @return boolean
     */
    protected function authenticateEmbedded(\Gems_User_User $embeddedUser, $secretKey)
    {
        if (! ($embeddedUser->isActive() && $embeddedUser->isEmbedded())) {
            return false;
        }

        return in_array($secretKey, $this->getValidKeys($embeddedUser));
    }

    /**
     *
     * @param string $key The input type
     * @return string The encrypted result that should be retrieved
     */
    protected function encryptKey($key)
    {
        if ($this->encryptionAlgorithm) {
            $input = hash($this->encryptionAlgorithm, $key, $this->encryptionRaw);
        } else {
            $input = $key;
        }

        if ($this->encryptionBase64) {
            return base64_encode($input);
        }

        return $input;
    }

    public function getDefferedUser(\Gems_User_User $embeddedUser, $deferredLogin)
    {
        $user = $this->getUser($deferredLogin, [
            $embeddedUser->getBaseOrganizationId(),
            $embeddedUser->getCurrentOrganizationId()
            ]);

        if ($user->isActive()) {
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
     * Return the authentication string for the user
     *
     * @param \Gems_User_User $embeddedUser
     * @return string Preferably containing %s
     */
    public function getKeysStart(\Gems_User_User $embeddedUser)
    {
        $key = $embeddedUser->hasTwoFactor() ? $embeddedUser->getTwoFactorKey() : $this->defaultKey;

        if (! \MUtil_String::contains($key, '%s')) {
            $key .= '%s';
        }

        return $key;
    }

    /**
     * Generate the \DateInterval constructor
     *
     * @param int $i The "start" interval
     * @return string
     * @throws \Gems_Exception_Coding
     */
    public function getTimePeriodString($i = 1)
    {
        $timeChar = substr($this->keyTimeFormat, -1);

        switch ($timeChar) {
            case 'o':
            case 'y':
            case 'Y':
                return "P{$i}Y";

            case 'm':
            case 'n':
                return "P{$i}M";

            case 'd':
            case 'j':
                return "P{$i}D";

            case 'H':
            case 'h':
                return "PT{$i}H";

            case 'i':
                return "PT{$i}M";

        }

        throw new \Gems_Exception_Coding("Invalid last keyTimeFormat character '$timeChar' set.");
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

        $user       = $this->currentUser;
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

        return $user;
    }

    /**
     * Return an array of valid key values for this user
     *
     * @param \Gems_User_User $embeddedUser
     * @return array
     */
    public function getValidKeys(\Gems_User_User $embeddedUser)
    {
        $keyStart = $this->getKeysStart($embeddedUser);
        // \MUtil_Echo::track($keyStart);

        if (! \MUtil_String::contains($keyStart, '%s')) {
            return [$keyStart];
        }

        $current = new \DateTime();
        $current->sub(new \DateInterval($this->getTimePeriodString($this->keyTimeValidRange)));
        $addDate = new \DateInterval($this->getTimePeriodString(1));
        $keys    = [];

        for ($i = -$this->keyTimeValidRange; $i <= $this->keyTimeValidRange; $i++) {
            $keys[] = $this->encryptKey(sprintf($keyStart, $current->format($this->keyTimeFormat)));
            $current->add($addDate);
        }
        \MUtil_Echo::track($keys);
        // \MUtil_Echo::track(hash_algos());

        return $keys;
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
     * @param mixed $organisations (Array of) organization id's or objects
     */
    protected function loginEmbedded($epdUserLogin, $secretKey, $deferredLogin, $patientId, $organisations = null)
    {
        $embeddedUser = $this->getUser($epdUserLogin, $organisations);

        if ($this->authenticateEmbedded($embeddedUser, $secretKey)) {
            $deferredUser = $this->getDefferedUser($embeddedUser, $deferredLogin);

            if ($deferredUser->isActive()) {
                if ($deferredUser->getCurrentOrganizationId() !== $embeddedUser->getCurrentOrganizationId()) {
                    $deferredUser->setCurrentOrganization($embeddedUser->getCurrentOrganizationId());
                }
            }

            $menuItem = $this->menu->findController('respondent', 'show');
            if ($patientId && $menuItem) {
                $deferredUser->setAsCurrentUser();
                $url = [
                    'controller'              => 'respondent',
                    'action'                  => 'show',
                    \MUtil_Model::REQUEST_ID1 => $patientId,
                    \MUtil_Model::REQUEST_ID2 => $deferredUser->getCurrentOrganizationId(),
                    ];
                $this->_helper->redirector->gotoRoute($url, null, true);
            }
        }

        throw new \Gems_Exception("Unable to authenticate");
    }
}
