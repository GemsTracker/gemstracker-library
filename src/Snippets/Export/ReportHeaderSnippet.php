<?php
/**
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

/**
 * Header for html/pdf export of a respondent
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class ReportHeaderSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var \Gems\User\Organization
     */
    protected $currentOrganization;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $html = $this->getHtmlSequence();
        $html->h2($this->_('Respondent report'));

        $table = $html->table(array('class' => 'browser'));

        $table->th($this->_('Report information'), array('colspan' => 2));
        $tr = $table->tr();
        $tr->th($this->_('Generated by'));
        $tr->td($this->currentUser->getFullName());
        $tr = $table->tr();
        $tr->th($this->_('Generated on'));
        $tr->td(date('Y-m-d H:i:s'));
        $tr = $table->tr();
        $tr->th($this->_('Organization'));
        $tr->td($this->currentOrganization->getName());

        $html->br();

        return $html;
    }
}