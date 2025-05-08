<?php
/**
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent\Export;

/**
 * Footer for html/pdf export of a respondent
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class ReportFooterSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $html->div($this->_('Report generation finished.'), array('class'=> 'centerAlign'));
        $html->hr();

        return $html;
    }
}