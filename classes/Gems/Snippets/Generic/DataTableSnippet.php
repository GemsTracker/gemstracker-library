<?php

namespace Gems\Snippets\Generic;

class DataTableSnippet extends \MUtil\Snippets\SnippetAbstract
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
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $table = \MUtil\Html\TableElement::createArray($this->tableData, $this->tableTitle, $this->tableNested);
        $table->class = 'browser table';
        $div = \MUtil\Html::create()->div(array('class' => 'table-container'));
        $div[] = $table;
        return $div;
    }
}