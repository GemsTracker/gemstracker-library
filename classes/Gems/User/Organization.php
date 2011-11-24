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
 * @version    $id: Organization.php 203 2011-07-07 12:51:32Z matijs $
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
class Gems_User_Organization extends Gems_Registry_TargetAbstract
{
    /**
     * The default organization data for 'no organization'.
     *
     * @var array
     */
    protected $_noOrganization = array(
        'gor_id_organization' => 1,
        'gor_name'            => 'NO ORGANIZATION',
        'gor_code'            => null,
        'gor_location'        => null,
        'gor_url'             => null,
        'gor_task'            => null,
        'gor_accessible_by'   => null,
        'gor_contact_name'    => null,
        'gor_contact_email'   => null,
        'gor_welcome'         => null,
        'gor_signature'       => null,
        'gor_style'           => null,
        'gor_iso_lang'        => 'en',
        'gor_has_respondents' => 0,
        'gor_add_respondents' => 0,
        'gor_active'          => 0,
        'can_access'          => array(),
        );

    /**
     *
     * @var array
     */
    protected $_organizationData;

    /**
     *
     * @var int
     */
    protected $_organizationId;

    /**
     *
     * @var Zend_Cache_Core
     */
    protected $cache;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Creates the organization object.
     *
     * @param int $organizationId
     */
    public function __construct($organizationId)
    {
        $this->_organizationId = $organizationId;
    }

    /**
     * Returns a callable if a method is called as a variable
     * or the value from the organizationData if it exists
     *
     * @param string $name
     * @return Callable
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return array($this, $name);
        } elseif (isset($this->_organizationData[$name])) {
            return $this->_organizationData[$name];
        }

        throw new Gems_Exception_Coding("Unknown method '$name' requested as callable.");
    }

    /**
     * Get the cacheId for the organization
     *
     * @return string
     */
    private function _getCacheId() {
        return GEMS_PROJECT_NAME . '__' . __CLASS__ . '__' . $this->_organizationId;
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
        return (boolean) $this->_organizationData['gor_add_respondents'];
    }

    /**
     * Returns true when this organization has or can have respondents
     *
     * @return boolean
     */
    public function canHaveRespondents()
    {
        return (boolean) $this->_organizationData['gor_has_respondents'] || $this->_organizationData['gor_add_respondents'];
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->cache) {
            $cacheId = $this->_getCacheId();
            $this->_organizationData = $this->cache->load($cacheId);
        } else {
            $cacheId = false;
        }

        if (! $this->_organizationData) {
            $sql = "SELECT * FROM gems__organizations WHERE gor_id_organization = ? LIMIT 1";
            $this->_organizationData = $this->db->fetchRow($sql, $this->_organizationId);

            if (! $this->_organizationData) {
                $this->_organizationData = $this->_noOrganization;
            } else {
                $dbOrgId = $this->db->quote($this->_organizationId, Zend_Db::INT_TYPE);
                $sql = "SELECT gor_id_organization, gor_name
                    FROM gems__organizations
                    WHERE gor_active = 1 AND
                        (
                          gor_id_organization = $dbOrgId OR
                          gor_accessible_by LIKE '%:$dbOrgId:%'
                        )
                    ORDER BY gor_name";
                $this->_organizationData['can_access'] = $this->db->fetchPairs($sql);

                // MUtil_Echo::track($sql, $this->_organizationData['can_access']);
            }

            if ($cacheId) {
                $this->cache->save($this->_organizationData, $cacheId);
            }
        }
        // MUtil_Echo::track($this->_organizationData);

        return is_array($this->_organizationData) && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Get the organizations this organizations can access.
     *
     * @return array Of type orgId => orgName
     */
    public function getAllowedOrganizations()
    {
        return $this->_organizationData['can_access'];
    }

    /**
     * Get the code attribute.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_organizationData['gor_code'];
    }

    /**
     * Get the organization id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->_organizationData['gor_id_organization'];
    }

    /**
     * Get the name of the organization.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_organizationData['gor_name'];
    }

    /**
     * Get the signature of the organization.
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->_organizationData['gor_signature'];
    }

    /**
     * Get the style attribute.
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->_organizationData['gor_style'];
    }

    /**
     * Get the welcome message for the organization.
     *
     * @return string
     */
    public function getWelcome()
    {
        return $this->_organizationData['gor_welcome'];
    }

    public function invalidateCache() {
        if ($this->cache) {
            $cacheId = $this->_getCacheId();
            $this->cache->remove($cacheId);
        }
    }
}
