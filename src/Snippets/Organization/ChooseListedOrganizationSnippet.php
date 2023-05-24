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

use Zalt\Snippets\TranslatableSnippetAbstract;

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
    protected $action;

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
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $html->h3($this->_('Choose an organization'));

        $url = [
            $this->requestInfo->getBasePath(),
            'action' => $this->action,
        ];

        if ($this->info) {
            $html->pInfo($this->info);
        }

        foreach ($this->orgs as $orgId => $name) {
            $url['org'] = $orgId;

            $html->pInfo()->actionLink($url, $name)->appendAttrib('class', 'larger');
        }

        return $html;
    }
}
