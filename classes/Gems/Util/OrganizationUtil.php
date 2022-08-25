<?php

namespace Gems\Util;

class OrganizationUtil extends CachedUtilAbstract
{
    /**
     * The org ID for no organization
     */
    const SYSTEM_NO_ORG  = -1;

    /**
     * @return string[] default array for when no organizations have been created
     */
    public static function getNotOrganizationArray()
    {
        return [static::SYSTEM_NO_ORG => 'create db first'];
    }

    /**
     * Get all active organizations
     *
     * @return array List of the active organizations
     */
    public function getOrganizations(): array
    {
        return $this->_getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            'organizations',
            'gor_active = 1',
            'natsort'
        );
    }

    /**
     * Get all organizations that share a given code
     *
     * On empty this will return all organizations
     *
     * @param string $code
     * @return array key = gor_id_organization, value = gor_name
     */
    public function getOrganizationsByCode(string $code = null): array
    {
        if (is_null($code)) {
            return $this->getOrganizations();
        }

        return $this->_getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            'organizations',
            $this->db->quoteInto('gor_active = 1 AND gor_has_respondents = 1 AND gor_code = ?', $code),
            'natsort'
        );
    }

    /**
     * Returns a list of the organizations where users can login.
     *
     * @return array List of the active organizations
     */
    public function getOrganizationsForLogin(): array
    {
        $output = $this->_getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            'organizations',
            'gor_active = 1 AND gor_has_login = 1',
            'natsort'
        );
        if ($output) {
            return $output;
        }
        return static::getNotOrganizationArray();
    }

    /**
     * Returns a list of the organizations that (can) have respondents.
     *
     * @return array List of the active organizations
     */
    public function getOrganizationsWithRespondents()
    {
        return $this->_getTranslatedPairsCached(
            'gems__organizations',
            'gor_id_organization',
            'gor_name',
            'organizations',
            'gor_active = 1 AND (gor_has_respondents = 1 OR gor_add_respondents = 1)',
            'natsort'
        );
    }
}