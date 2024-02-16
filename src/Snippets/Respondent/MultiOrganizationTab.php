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

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\RouteHelper;
use Gems\Tracker\Respondent;
use Gems\User\User;
use MUtil\Model;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
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

    protected Respondent $respondent;

    protected ?string $linkClass = 'nav-link';

    protected ?string $parameterKey = Model::REQUEST_ID2;

    protected string $tabClass = 'tab nav-item';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected ResultFetcher $resultFetcher,
        protected RouteHelper $routeHelper,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    protected function getHref($currentTab): string
    {
        $routeName = $this->requestInfo->getRouteName();

        $params = $this->requestInfo->getRequestMatchedParams();
        $params[$this->parameterKey] = $currentTab;

        return $this->routeHelper->getRouteUrl($routeName, $params, $this->requestInfo->getRequestQueryParams());
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    protected function getTabs(): array
    {
        $this->defaultTab = (string) $this->currentUser->getCurrentOrganizationId();

        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams[Model::REQUEST_ID2])) {
            $this->currentTab = $queryParams[Model::REQUEST_ID2];
        }

        $select = $this->resultFetcher->getSelect('gems__respondent2org');
        $select->columns(['gr2o_id_organization', 'gr2o_patient_nr'])
            ->where(['gr2o_id_user' => $this->respondent->getId()]);

        $allowedOrgs  = $this->currentUser->getRespondentOrganizations();
        $existingOrgs = $this->resultFetcher->fetchPairs($select);
        $tabs         = [];
        
        foreach ($allowedOrgs as $orgId => $name) {
            if (isset($existingOrgs[$orgId])) {
                $tabs[$orgId] = $name;
            }
        }

        return $tabs;
    }
}
