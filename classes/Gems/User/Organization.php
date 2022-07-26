<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User;

/**
 * Contains information on the organization of the current User
 *
 * @see \Gems_Useer_User
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Organization extends \Gems\Registry\CachedArrayTargetAbstract
{
    /**
     *
     * @var array of class name => class label
     */
    protected $_allowedProjectUserClasses;

    /**
     * Variable to add tags to the cache for cleanup.
     *
     * @var array
     */
    protected $_cacheTags = array('organization', 'organizations');

    /**
     * The default organization data for 'no organization'.
     *
     * @var array
     */
    protected $_noOrganization = array(
        'gor_id_organization' => 1,
        'gor_name'            => 'NO ORGANIZATION',
        'gor_iso_lang'        => 'en',
        'gor_has_respondents' => 0,
        'gor_add_respondents' => 0,
        'gor_active'          => 0,
        'can_access'          => array(),
        );

    /**
     *
     * @var \Gems\Screens\EditScreenInterface
     */
    protected $_respondentEditScreen;

    /**
     *
     * @var \Gems\Screens\ShowScreenInterface
     */
    protected $_respondentShowScreen;

    /**
     *
     * @var \Gems\Screens\SubscribeScreenInterface
     */
    protected $_subscribeScreen;

    /**
     *
     * @var \Gems\Screens\AskScreenInterface
     */
    protected $_tokenAskScreen;

    /**
     *
     * @var \Gems\Screens\UnsubscribeScreenInterface
     */
    protected $_unsubscribeScreen;

    /**
     * Required
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Set in child classes
     *
     * @var string Name of table used in gtrs_table
     */
    protected $translationTable = 'gems__organizations';

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Creates the object.
     *
     * @param mixed $id Whatever identifies this object.
     * @param array $allowedProjectUserClasses of class name => class label
     */
    public function __construct($id, array $allowedProjectUserClasses)
    {
        parent::__construct($id);

        $this->_allowedProjectUserClasses = $allowedProjectUserClasses;
    }

    /**
     * When true respondents of this organization may login
     *
     * @return boolean
     */
    public function allowsRespondentLogin()
    {
        return (boolean) $this->_get('gor_respondent_group') && $this->canHaveRespondents();
    }

    /**
     * Set menu parameters from the organization
     *
     * @param \Gems\Menu\ParameterSource $source
     * @return \Gems\Tracker\Token (continuation pattern)
     */
    public function applyToMenuSource(\Gems\Menu\ParameterSource $source)
    {
        $source->offsetSet('can_add_respondents', $this->canCreateRespondents());
    }

    /**
     * Returns true when this organization has or can have respondents
     *
     * @return boolean
     */
    public function canCreateRespondents()
    {
        return (boolean) $this->_get('gor_add_respondents');
    }

    /**
     * Returns true when this organization has or can have respondents
     *
     * @return boolean
     */
    public function canHaveRespondents()
    {
        return (boolean) $this->_get('gor_has_respondents') || $this->_get('gor_add_respondents');
    }

    /**
     * Check whether the organization still has at least one respondent attached to it.
     *
     * Does nothing if this is already known.
     *
     * @param int $userId The current user
     * @return \Gems\User\Organization (continuation pattern)
     */
    public function checkHasRespondents($userId, $check = false)
    {
        $sql = "CASE
            WHEN EXISTS (SELECT * FROM gems__respondent2org WHERE gr2o_id_organization = gor_id_organization)
            THEN 1
            ELSE 0
            END";
        $values['gor_has_respondents'] = new \Zend_Db_Expr($sql);
        $values['gor_changed']         = new \MUtil\Db\Expr\CurrentTimestamp();
        $values['gor_changed_by']      = $userId;

        $where = $this->db->quoteInto('gor_id_organization = ?', $this->_id);

        if ($this->db->update('gems__organizations', $values, $where)) {
            $this->loadData($this->_id);
        }

        return $this;
    }

    /**
     * Does the code attribute contain this code (with multiple codes seperated by a space)
     *
     * @param string $checkCode
     * @return boolean
     */
    public function containsCode($checkCode)
    {
        return stripos(' ' . $this->_get('gor_code') . ' ', ' ' . $checkCode . ' ') !== false;
    }

    /**
     * Returns true if this is an existing organization
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->_noOrganization['gor_name'] !== $this->getName();
    }

	/**
     * Returns the $key in organizationData when set otherwise the default value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        } else {
            return $default;
        }
    }

    /**
     * Get the allowed_ip_ranges attribute.
     *
     * @return string
     */
    public function getAllowedIpRanges()
    {
        return $this->_get('gor_allowed_ip_ranges');
    }

    /**
     * Get the organizations this organizations can access.
     *
     * @return array Of type orgId => orgName
     */
    public function getAllowedOrganizations()
    {
        return $this->_get('can_access');
    }

    /**
     * Get the available user class for new users in this organization
     *
     * @return array Of type class name => class label
     */
    public function getAllowedUserClasses()
    {
        $output = $this->_allowedProjectUserClasses;

        if (\Gems\User\UserLoader::USER_RADIUS !== $this->_get('gor_user_class')) {
            unset($output[\Gems\User\UserLoader::USER_RADIUS]);
        }
        return $output;
    }

    /**
     *
     * @return integer Create account template id
     */
    public function getCreateAccountTemplate()
    {
        return $this->_get('gor_create_account_template');
    }

    /**
     * Get the code attribute.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_get('gor_code');
    }

    /**
     * Get the contact name attribute.
     *
     * @return string
     */
    public function getContactName()
    {
        return $this->_get('gor_contact_name');
    }

    /**
     * Get the default user class for new users in this organization
     *
     * @return string The stored user class
     */
    public function getDefaultUserClass()
    {
        return $this->_get('gor_user_class');
    }

    /**
     * Get the email attribute.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->_get('gor_contact_email');
    }

    /**
     * Returns the from address
     *
     * @return string E-Mail address
     */
    public function getFrom()
    {
        return $this->getEmail();
    }

    /**
     * Get the organization id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->_get('gor_id_organization');
    }

    /**
     * Return org dependent login url
     *
     * Synonym for getPreferredSiteUrl(), may devert in the future
     * 
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->getPreferredSiteUrl();
    }

    /**
     * Array of field name => values for sending E-Mail
     *
     * @return array
     */
    public function getMailFields()
    {
        $result['organization']                 = $this->getName();
        $result['organization_location']        = $this->_get('gor_location');
        $result['organization_login_url']       = $this->getLoginUrl();
        $result['organization_reply_name']      = $this->_get('gor_contact_name');
        $result['organization_reply_to']        = $this->_get('gor_contact_email');
        $result['organization_signature']       = $this->getSignature();
        $result['organization_unsubscribe_url'] = $this->getUnsubscribeUrl();
        $result['organization_url']             = $this->_get('gor_url');
        $result['organization_welcome']         = $this->getWelcome();

        if ((APPLICATION_ENV === 'production') &&
                preg_match('#^http(s)?://localhost#', $result['organization_login_url'])) {
            throw new \Gems\Exception("Use of 'localhost' as url not permitted on production system.");
        }

        return $result;
    }

    /**
     * Get the name of the organization.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_get('gor_name');
    }

    /**
     * Class name for organization specific respondent change event
     *
     * @return string
     */
    public function getRespondentChangeEventClass()
    {
        return $this->_get('gor_resp_change_event');
    }

    /**
     *
     * @return \Gems\Screens\EditScreenInterface
     */
    public function getRespondentEditScreen()
    {
        if ($this->_respondentEditScreen || (! $this->_get('gor_respondent_edit'))) {
            return $this->_respondentEditScreen;
        }
        $screenLoader = $this->loader->getScreenLoader();

        $this->_respondentEditScreen = $screenLoader->loadRespondentEditScreen($this->_get('gor_respondent_edit'));

        return $this->_respondentEditScreen;
    }

    /**
     *
     * @return \Gems\Screens\ShowScreenInterface
     */
    public function getRespondentShowScreen()
    {
        if ($this->_respondentShowScreen || (! $this->_get('gor_respondent_show'))) {
            return $this->_respondentShowScreen;
        }
        $screenLoader = $this->loader->getScreenLoader();

        $this->_respondentShowScreen = $screenLoader->loadRespondentShowScreen($this->_get('gor_respondent_show'));

        return $this->_respondentShowScreen;
    }

    /**
     * get the parameters where the survey should return to
     *
     * @return array
     */
    public function getPreferredSiteUrl()
    {
        if (! $this->_has('preferredSite')) {
            $this->_set('preferredSite', $this->util->getSites()->getOrganizationPreferredUrl($this->_id));
        }

        return $this->_get('preferredSite');
    }

    /**
     * Get the template id for the reset password mail
     *
     * @return  integer Template ID
     */
    public function getResetPasswordTemplate()
    {
        return $this->_get('gor_reset_pass_template');
    }

    /**
     * Get the Sms from name
     *
     * @return string SMS from
     */
    public function getSmsFrom()
    {
        return $this->_get('gor_contact_sms_from');
    }

    /**
     * Get the signature of the organization.
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->_get('gor_signature');
    }

    /**
     *
     * @return \Gems\Screens\SubscribeScreenInterface
     */
    public function getSubscribeScreen()
    {
        if ($this->_subscribeScreen || (! $this->_get('gor_respondent_subscribe'))) {
            return $this->_subscribeScreen;
        }
        $screenLoader = $this->loader->getScreenLoader();

        $this->_subscribeScreen = $screenLoader->loadSubscribeScreen($this->_get('gor_respondent_subscribe'));

        return $this->_subscribeScreen;
    }

    /**
     * Get the style attribute.
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->_get('gor_style');
    }

    /**
     *
     * @return \Gems\Screens\AskScreenInterface
     */
    public function getTokenAskScreen()
    {
        if ($this->_tokenAskScreen || (! $this->_get('gor_token_ask'))) {
            return $this->_tokenAskScreen;
        }
        $screenLoader = $this->loader->getScreenLoader();

        $this->_tokenAskScreen = $screenLoader->loadTokenAskScreen($this->_get('gor_token_ask'));

        return $this->_tokenAskScreen;
    }

    /**
     *
     * @return \Gems\Screens\UnsubscribeScreenInterface
     */
    public function getUnsubscribeScreen()
    {
        if ($this->_unsubscribeScreen || (! $this->_get('gor_respondent_unsubscribe'))) {
            return $this->_unsubscribeScreen;
        }
        $screenLoader = $this->loader->getScreenLoader();

        $this->_unsubscribeScreen = $screenLoader->loadUnsubscribeScreen($this->_get('gor_respondent_unsubscribe'));

        return $this->_unsubscribeScreen;
    }

    /**
     *
     * @return url string
     */
    public function getUnsubscribeUrl()
    {
        if (! $this->_get('gor_respondent_unsubscribe')) {
            return null;
        }

        return $this->getPreferredSiteUrl() . '/participate/unsubscribe/org/' . $this->getId();
    }

    /**
     * Get the welcome message for the organization.
     *
     * @return string
     */
    public function getWelcome()
    {
        return $this->_get('gor_welcome');
    }

    /**
     * Has org an email attribute?
     *
     * @return boolean
     */
    public function hasEmail()
    {
        return $this->_has('gor_contact_email');
    }

    /**
     * Does this organization have a user group?
     *
     * @return boolean
     */
    public function hasRespondentGroup()
    {
        return $this->_has('gor_respondent_group');
    }

    /**
     * Load the data when the cache is empty.
     *
     * @param mixed $id
     * @return array The array of data values
     */
    protected function loadData($id)
    {
        if (\Gems\User\UserLoader::SYSTEM_NO_ORG === $id) {
            $data = false;
        } else {
            try {
                $sql = "SELECT * FROM gems__organizations WHERE gor_id_organization = ? LIMIT 1";
                $data = $this->db->fetchRow($sql, intval($id));
            } catch (\Exception $e) {
                $data = false;
            }
        }

        if ($data) {
            $data['can_access'] = $this->util->getDbLookup()->getAllowedOrganizationsFor($id);
        } else {
            $data = $this->_noOrganization;
            $data['gor_id_organization'] = $id;
        }

        return $data;
    }

    /**
     * Set this organization as the one currently active
     *
     * @return \Gems\User\Organization (continuation pattern)
     */
    public function setAsCurrentOrganization()
    {
        $organizationId = $this->getId();

        if ($organizationId && (! \Gems\Cookies::setOrganization($organizationId, $this->basepath->getBasePath()))) {
            throw new \Exception('Cookies must be enabled for this site.');
        }

        $escort = \Gems\Escort::getInstance();
        if ($escort instanceof \Gems\Project\Layout\MultiLayoutInterface) {
            $escort->layoutSwitch($this->getStyle());
        }

        return $this;
    }

    /**
     * Tell the organization there is at least one respondent attached to it.
     *
     * Does nothing if this is already known.
     *
     * @param int $userId The current user
     * @return \Gems\User\Organization (continuation pattern)
     */
    public function setHasRespondents($userId)
    {
        if (! $this->_get('gor_has_respondents')) {
            $values['gor_has_respondents'] = 1;
            $values['gor_changed']         = new \MUtil\Db\Expr\CurrentTimestamp();
            $values['gor_changed_by']      = $userId;

            $where = $this->db->quoteInto('gor_id_organization = ?', $this->_id);

            $this->db->update('gems__organizations', $values, $where);

            // Invalidate cache / change value
            $this->_set('gor_has_respondents', 1);
        }

        return $this;
    }
}
