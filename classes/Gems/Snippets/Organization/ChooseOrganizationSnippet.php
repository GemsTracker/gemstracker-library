<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

namespace Gems\Snippets\Organization;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ChooseOrganizationSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * Required
     *
     * @var \Gems_Loader
     */
    protected $loader;

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
        return (boolean) $this->loader && $this->request && parent::checkRegistryRequestsAnswers();
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

        $user = $this->loader->getCurrentUser();
        $url['controller'] = 'organization';
        $url['action']     = 'change-ui';

        if ($orgs = $user->getRespondentOrganizations()) {
            $html->pInfo($this->_('This organization cannot have any respondents, please choose one that does:'));

            foreach ($orgs as $orgId => $name) {
                $url['org'] = $orgId;

                $html->pInfo()->actionLink($url, $name)->appendAttrib('class', 'larger');
            }
        } else {
            $html->pInfo($this->_('This organization cannot have any respondents.'));
        }

        return $html;
    }
}
