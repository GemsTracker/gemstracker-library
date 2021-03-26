<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\SiteMaintenance
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\SiteMaintenance;

/**
 *
 * @package    Gems
 * @subpackage Snippets\SiteMaintenance
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteMaintenanceInformation extends \MUtil_Snippets_SnippetAbstract
{
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
        $seq = $this->getHtmlSequence();
        $seq->br();
        $div = $seq->div(['class' => 'alert alert-info', 'role' => "alert"]);

        $div->h2($this->_('Explanation of Site maintenance'), ['style' => 'margin-top: 5px;']);

        $div->pInfo($this->_('Site maintenance does multiple things: it link url\'s to organizations for email and login. It also performs a security function.'));

        $div->h3($this->_('How it works'));
        $div->pInfo($this->_('Site maintenance can be maintained manually, but is usually automatically filled every time the login screen is opened or data is committed by post.'));

        $div->h3($this->_('Linking an url to an organization'));

        $p = $div->pInfo($this->_('When the login page is visited on the server, this means that the url used is accepted by the server.'));
        $p->append(' ' . $this->_('If the url is new, then the url is by default made accessible for all organizations.'));

        $div->pInfo($this->_('After login you can use this screen to limit the organizations accessible through this url.'));

        $div->pInfo($this->_('The email url used by an organization is the oldest url with the lowest priority number accessible for that organization.'));
        
        $div->h3($this->_('Defending against cross site posting'));

        $p = $div->pInfo($this->_('When a user tries to login (or post in general) from a site that is not known to the system, this implies the url is not on the server itself.'));
        $p->append(' ' . $this->_('If the url is new, then the url is blocked by default.'));

        $div->pInfo($this->_('If the login is legitimate (e.g. from another site in the same organization) you can unblock the url here.'));
        
        return $seq;
    }

}