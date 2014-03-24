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
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Contains information on the organization of the current User
 *
 * @see Gems_Useer_User
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_Organization extends Gems_Registry_CachedArrayTargetAbstract
{
    /**
     * Variable to add tags to the cache for cleanup.
     *
     * @var array
     */
    protected $_cacheTags = array('organization');

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
     * Required
     *
     * @var Gems_Util_BasePath
     */
    protected $basepath;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

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
     * @param Gems_Menu_ParameterSource $source
     * @return Gems_Tracker_Token (continuation pattern)
     */
    public function applyToMenuSource(Gems_Menu_ParameterSource $source)
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
     * @return string
     */
    public function getLoginUrl()
    {
        if ($base = $this->_get('base_url')) {
            return $base;
        } else {
            return $this->util->getCurrentURI();
        }
    }

    /**
     * Array of field name => values for sending E-Mail
     *
     * @return array
     */
    public function getMailFields()
    {
        $result['organization']            = $this->getName();
        $result['organization_location']   = $this->_get('gor_location');
        $result['organization_login_url']  = $this->getLoginUrl();
        $result['organization_reply_name'] = $this->_get('gor_contact_name');
        $result['organization_reply_to']   = $this->_get('gor_contact_email');
        $result['organization_signature']  = $this->getSignature();
        $result['organization_url']        = $this->_get('gor_url');
        $result['organization_welcome']    = $this->getWelcome();

        if ((APPLICATION_ENV === 'production') &&
                preg_match('#^http(s)?://localhost#', $result['organization_login_url'])) {
            throw new Gems_Exception("Use of 'localhost' as url not permitted on production system.");
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
     *  Get the template id for the reset password mail
     * @return  integer Template ID
     */
    public function getResetPasswordTemplate()
    {
        return $this->_get('gor_reset_pass_template');
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
     * Get the style attribute.
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->_get('gor_style');
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
        return (boolean) $this->_has('gor_respondent_group');
    }

    /**
     * Load the data when the cache is empty.
     *
     * @param mixed $id
     * @return array The array of data values
     */
    protected function loadData($id)
    {
        try {
            $sql = "SELECT * FROM gems__organizations WHERE gor_id_organization = ? LIMIT 1";
            $data = $this->db->fetchRow($sql, intval($id));
        } catch (Exception $e) {
            $data = false;
        }

        if ($data) {
            try {
                $dbOrgId = $this->db->quote($id, Zend_Db::INT_TYPE);
                $sql = "SELECT gor_id_organization, gor_name
                    FROM gems__organizations
                    WHERE gor_active = 1 AND
                        (
                          gor_id_organization = $dbOrgId OR
                          gor_accessible_by LIKE '%:$dbOrgId:%'
                        )
                    ORDER BY gor_name";
                $data['can_access'] = $this->db->fetchPairs($sql);
                natsort($data['can_access']);
            } catch (Exception $e) {
                $data['can_access'] = array();
            }

            // MUtil_Echo::track($sql, $data['can_access']);

            if (array_key_exists('gor_url_base', $data) && $baseUrls = explode(' ', $data['gor_url_base'])) {
                $data['base_url'] = reset($baseUrls);
            }
        } else {
            $data = $this->_noOrganization;
            $data['gor_id_organization'] = $id;
        }

        return $data;
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
     * Set this organization as teh one currently active
     *
     * @return Gems_User_Organization (continutation pattern)
     */
    public function setAsCurrentOrganization()
    {
        $organizationId = $this->getId();

        if ($organizationId && (! Gems_Cookies::setOrganization($organizationId, $this->basepath->getBasePath()))) {
            throw new Exception('Cookies must be enabled for this site.');
        }

        $escort = GemsEscort::getInstance();
        if ($escort instanceof Gems_Project_Layout_MultiLayoutInterface) {
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
     * @return Gems_User_Organization (continutation pattern)
     */
    public function setHasRespondents($userId)
    {
        if (! $this->_get('gor_has_respondents')) {
            $values['gor_has_respondents'] = 1;
            $values['gor_changed']         = new MUtil_Db_Expr_CurrentTimestamp();
            $values['gor_changed_by']      = $userId;

            $where = $this->db->quoteInto('gor_id_organization = ?', $this->_id);

            $this->db->update('gems__organizations', $values, $where);

            // Invalidate cache / change value
            $this->_set('gor_has_respondents', 1);
        }

        return $this;
    }
}
