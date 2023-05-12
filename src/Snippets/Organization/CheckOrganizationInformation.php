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
 * @since      Class available since version 1.8.4 12-Mar-2019 17:28:39
 */
class CheckOrganizationInformation extends \MUtil\Snippets\SnippetAbstract
{
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
        $seq = $this->getHtmlSequence();

        $seq->h2($this->_('Checks'));

        $ul = $seq->ul();
        $ul->li($this->_('Executes respondent change event for all active respondents.'));

        $seq->pInfo($this->_(
                'Run this code when the respondent change event was changed or e.g. when a new "default" track was created.'
                ));

        return $seq;
    }
}
