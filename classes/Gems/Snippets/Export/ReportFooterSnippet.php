<?php
/**
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

/**
 * Footer for html/pdf export of a respondent
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class ReportFooterSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     * @var \Gems\Loader
     */
    public $loader;

    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $html = $this->getHtmlSequence();

        $html->div($this->_('Report generation finished.'), array('class'=> 'centerAlign'));
        $html->hr();

        return $html;
    }
}