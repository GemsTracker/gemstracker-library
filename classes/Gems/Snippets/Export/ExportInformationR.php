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
class ExportInformationR extends \MUtil_Snippets_SnippetAbstract
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

        $seq->h2($this->_('Export to R'));

        $seq->pre($this->_("Open the downloaded zip file when finished and open the .R file using:\n" .
                "    source(\"filename.R\", encoding=\"UTF-8\")\n" .
                "or use File -> Reopen with Encoding... when using RStudio and choose UTF-8 and run all lines.\n" .
                "\n" .
                "Your data is now in a frame called 'data'"
                ));

        return $seq;
    }
}
