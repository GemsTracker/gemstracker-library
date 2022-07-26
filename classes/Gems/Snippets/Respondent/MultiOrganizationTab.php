<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

/**
 * Displays tabs for multiple organizations.
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MultiOrganizationTab extends \MUtil\Snippets\TabSnippetAbstract
{
    protected $href = [];

    /**
     * Required
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var array id specific hrefs
     */
    protected $hrefs;

    /**
     * Required
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    /**
     * Return the parameters that should be used for this tabId
     *
     * @param string $tabId
     * @return array
     */
    protected function getParameterKeysFor($tabId)
    {
        return $this->hrefs[$tabId];
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    protected function getTabs()
    {
        $user = $this->loader->getCurrentUser();

        $sql  = "SELECT gr2o_id_organization, gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_id_user = ?";

        $this->defaultTab = $user->getCurrentOrganizationId();

        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams[\MUtil\Model::REQUEST_ID2])) {
            $this->currentTab = $queryParams[\MUtil\Model::REQUEST_ID2];
        }

        $allowedOrgs  = $user->getRespondentOrganizations();
        $existingOrgs = $this->db->fetchPairs($sql, $this->respondent->getId());
        $tabs         = [];
        
        foreach ($allowedOrgs as $orgId => $name) {
            if (isset($existingOrgs[$orgId])) {
                $tabs[$orgId] = $name;
                $this->hrefs[$orgId] = [
                    \MUtil\Model::REQUEST_ID1 => $existingOrgs[$orgId],
                    \MUtil\Model::REQUEST_ID2 => $orgId,
                    'RouteReset' => true,
                ];
            }
        }

        return $tabs;
    }
}
