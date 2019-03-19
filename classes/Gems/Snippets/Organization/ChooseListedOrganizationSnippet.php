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

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 12:27:58
 */
class ChooseListedOrganizationSnippet extends \MUtil_Snippets_SnippetAbstract
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
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return (boolean) $this->orgs && $this->request;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $html = $this->getHtmlSequence();

        $html->h3($this->_('Choose an organization'));

        $url[$this->request->getControllerKey()] = $this->request->getControllerName();
        $url[$this->request->getActionKey()]     = $this->action;

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
