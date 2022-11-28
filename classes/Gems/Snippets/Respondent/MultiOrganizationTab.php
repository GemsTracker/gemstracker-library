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

use Gems\Legacy\CurrentUserRepository;
use Gems\User\User;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Snippets\TabSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Displays tabs for multiple organizations.
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MultiOrganizationTab extends TabSnippetAbstract
{
    protected User $currentUser;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

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
        $this->defaultTab = $this->currentUser->getCurrentOrganizationId();

        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams[Model::REQUEST_ID2])) {
            $this->currentTab = $queryParams[Model::REQUEST_ID2];
        }

        $allowedOrgs  = $this->currentUser->getRespondentOrganizations();
        $existingOrgs = []; // $this->db->fetchPairs($sql, $this->respondent->getId());
        $tabs         = [];
        
        foreach ($allowedOrgs as $orgId => $name) {
            if (isset($existingOrgs[$orgId])) {
                $tabs[$orgId] = $name;
                $this->hrefs[$orgId] = [
                    Model::REQUEST_ID1 => $existingOrgs[$orgId],
                    Model::REQUEST_ID2 => $orgId,
                    'RouteReset' => true,
                ];
            }
        }

        return $tabs;
    }
}
