<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Organization;

use Gems\Menu\RouteHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Snippets\TranslatableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 12:27:58
 */
class ChooseListedOrganizationSnippet extends TranslatableSnippetAbstract
{
    /**
     *
     * @var string
     */
    protected $info;

    /**
     *
     * @var array org-id => name
     */
    protected $orgs;

    /**
     *
     * @var string
     */
    protected $route;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly RouteHelper $routeHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }


    /**
     * @inheritDoc
     */
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $html->h3($this->_('Choose an organization'));

        if ($this->info) {
            $html->pInfo($this->info);
        }

        foreach ($this->orgs as $orgId => $name) {
            $html->pInfo()->actionLink($this->routeHelper->getRouteUrl($this->route, ['org' => $orgId]), $name)->appendAttrib('class', 'larger');
        }

        return $html;
    }
}
