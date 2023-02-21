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
class SiteSetupCheckSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     * @var \Gems\Loader
     */
    protected $loader;

    protected $outputLevel = true;
    
    /**
     * @var \Gems\Util
     */
    protected $util;
    
    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $html     = $this->getHtmlSequence();
        $siteUtil = $this->util->getSites();

        $html->h2($this->_('Site setup information'));
        
        $theOne = $siteUtil->getOneForAll();
        if ($theOne && $this->outputLevel) {
            $html->pInfo()->raw(sprintf(
                $this->_('The url <b>%s</b> is used as fall-back for all organizations.'),
                $theOne->getUrl()
                ));
        } else {
            $msg = $this->_('No fall-back url exists for all organizations!');
            $html->pInfo()->strong($msg);
            $this->addMessage($msg);
        }
        $unspecified = $siteUtil->getUnspecificOrganizations();
        if ($unspecified) {
            $userLoader = $this->loader->getUserLoader();

            $html->pInfo($this->_('These organizations do not have a specific site url:'));
            $ul = $html->ul();
            
            foreach ($unspecified as $orgId) {
                $organization = $userLoader->getOrganization($orgId);
                $ul->li($organization->getName());
            }
            
            if (! $theOne) {
                $this->addMessage($this->_('Some organizations cannot access the site!'));
            }
        } elseif ($this->outputLevel) {
            $html->pInfo($this->_('All organizations have a specific site url.'));
        }
        
        return $html;
    }


}