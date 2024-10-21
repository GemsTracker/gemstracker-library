<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Organization;

use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\RouteHelper;
use Gems\User\User;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ChooseOrganizationSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    protected readonly User $currentUser;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected readonly RouteHelper $routeHelper,
        )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $html->h3($this->_('Choose an organization'));

        if ($orgs = $this->currentUser->getRespondentOrganizations()) {
            $html->pInfo($this->_('This organization cannot have any respondents, please choose one that does:'));

            foreach ($orgs as $orgId => $name) {
                $url = $this->routeHelper->getRouteUrl('organization.switch-ui', [], ['org' => $orgId]);

                // @phpstan-ignore method.notFound
                $html->pInfo()->actionLink($url, $name)->appendAttrib('class', 'larger');
            }
        } else {
            $html->pInfo($this->_('This organization cannot have any respondents.'));
        }

        return $html;
    }
}
