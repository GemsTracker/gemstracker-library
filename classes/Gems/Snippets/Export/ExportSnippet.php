<?php

namespace Gems\Snippets\Export;

class ExportSnippet extends \MUtil_Snippets_SnippetAbstract
{
    public $filter;

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
        $url = $view->url(array('action' => 'export'));

        $container = \MUtil_Html::div(array('id' => 'export-container'));

        $button = $container->button(array($this->_('Export'), 'id' => 'modelExport'));

        return $container;
    }
}