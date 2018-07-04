<?php

namespace Gems\Snippets\Generic;

class DataTableSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * 
     * @var boolean Does the table data array contain direct keys, or are there subitems under it?
     */
    public $tableNested = false;

    /**
     * Title that should be displayed before the table. Will not be shown if null
     * @var string
     */
    public $tableTitle;

    /**
     * Array with data that should be shown in the table. Array Keys will be the columns
     * @var array
     */
    public $tableData = [];

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
        $table = \MUtil_Html_TableElement::createArray($this->tableData, $this->tableTitle, $this->tableNested);
        $table->class = 'browser table';
        $div = \MUtil_Html::create()->div(array('class' => 'table-container'));
        $div[] = $table;
        return $div;
    }
}