<?php

/**
 * @package    Gems
 * @subpackage Snippets\Export
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

/**
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5
 */
class ExportInformationSpss extends \MUtil\Snippets\SnippetAbstract
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

        $seq->h2($this->_('Export to SPSS'));

        $seq->pre($this->_("Extract all files from the downloaded zip and open the .sps file.\n" .
                "Change line number 8 to include the full path to the .dat file:\n" .
                "    /FILE=\"filename.dat\"  ==>  /FILE=\"c:\\downloads\\filename.dat\"\n" .
                "Choose Run/All and all your data should be visible."
                ));

        return $seq;
        
        
    }
}
