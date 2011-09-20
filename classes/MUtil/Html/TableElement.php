<?php


/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.4
 * @package MUtil
 * @subpackage Html
 */

/**
 * TableElement is an extension of HtmlElement that add's a lot of table specific extra functionality
 *
 * @author Matijs de Jong
 * @package MUtil
 * @subpackage Html
 */
class MUtil_Html_TableElement extends MUtil_Html_HtmlElement implements MUtil_Html_ColumnInterface, MUtil_Html_FormLayout
{
    /**
     * Content position constants
     */
    const CAPTION = 'caption';
    const COLGROUPS = 'colgroups';
    const TBODY = 'tbody';
    const TFOOT = 'tfoot';
    const THEAD = 'thead';

    /**
     * Rendering constants for displaying special values in static createX and renderX functions
     */

    /**
     * Constant for displaying circular reference in static createX and renderX functions
     */
    const RENDER_CIRCULAR = '&#x21BA;';  // &lArr; &crarr; &alefsym; &infin; &#x219C;&infin;

    /**
     * Constant for end of display of any special type of value in static createX and renderX functions
     */
    const RENDER_CLOSE = '</span>';

    /**
     * Constant for displaying empty value in static createX and renderX functions
     */
    const RENDER_EMPTY = '&empty;';

    /**
     * Constant for displaying empty array value in static createX and renderX functions
     */
    const RENDER_EMPTY_ARRAY = '[&empty;]';

    /**
     * Constant for displaying empty string value in static createX and renderX functions
     */
    const RENDER_EMPTY_STRING = '&rsaquo;&lsaquo;'; // '&lsquo;&rsquo;'; // '&raquo;&laquo;'; '#';

    /**
     * Constant for start of display of any special type of value in static createX and renderX functions
     */
    const RENDER_OPEN = '<span class="tblSpecial">';

    /**
     * All new content is added to the last (tbody) element.
     *
     * @var boolean When true new content not having a $_allowedChildTags is added to $_lastChild.
     */
    protected $_addtoLastChild = true;

    /**
     * Of course strictly speaking a row is allowed as well,
     * but there is no need to support independent rows and
     * allowing them makes for semantic difficulties.
     *
     * @var string|array A string or array of string values of the allowed element tags.
     */
    protected $_allowedChildTags = array(self::CAPTION, self::COLGROUPS, self::TBODY, self::TFOOT, self::THEAD);

    /**
     * Always end with a new line. Makes the html code better readable
     *
     * @var string Content added after the element.
     */
    protected $_appendString = "\n";

    /**
     * All content is added to the tbody element.
     *
     * @var string The tagname of the element that should be created for content not having an $_allowedChildTags.
     */
    protected $_defaultChildTag = 'tbody';

    /**
     * Signals the default row class was set
     *
     * @var boolean
     */
    protected $_defaultRowClassSet = false;

    /**
     * When set the table layout is pivoted to the left when rendering.
     *
     * I.e. the first (header) row becomes the first column, etc..
     *
     * When set this is an array containing array(self::THEAD => number_of_header_rows, self::TFOOT => number_of_footer_rows).
     *
     * @var array False or array containing number of header en footer rows
     */
    protected $_pivot = false;

    /**
     * Always start on a new line. Makes the html code better readable
     *
     * @var string Content added before the element.
     */
    protected $_prependString = "\n";

    protected $_specialTypes = array(
        'Zend_Form' => 'setAsFormLayout',
        );

    /**
     * When empty a table element should not be output at rendering time as
     * a stand-alone <table/> tag makes no sense.
     *
     * @see $_repeater
     *
     * @var boolean The element is rendered even without content when true.
     */
    public $renderWithoutContent = false;

    public function __call($name, array $arguments)
    {
        // Cannot define a function with name throw, so this is an alias
        if (strtolower($name) == 'throw') {
            return call_user_func_array(array($this, 'thhrow'), $arguments);
        }

        return parent::__call($name, $arguments);
    }

    public function __construct($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        // Create positions for all (potential) elements
        //
        // parent::offsetSet() instead of $this[].
        // This prevent unsuspected infinite loop
        $this->_content[self::CAPTION] = null;
        $this->_content[self::COLGROUPS] = null;
        $this->_content[self::THEAD] = null;
        $this->_content[self::TFOOT] = null;
        $this->_content[self::TBODY] = MUtil_Html::create('tbody');

        parent::__construct('table', $args);

        if (! ($this->_defaultRowClassSet || $this->getDefaultRowClass())) {
            $this->setAlternateRowClass();
        }
    }

    /**
     * Makes sure the column width of the $name part is equal to that
     * of the 'tbody' part.
     *
     * @param string $name 'thead' or 'tfoot'
     */
    private function _equalizeColumnCounts($name)
    {
        $body = $this->$name();

        $count = $this->_content[self::TBODY]->getColumnCount();
        for ($i = $body->getColumnCount(); $i < $count; $i++) {
            $body->th();
        }
    }

    private function _pivotBody($name)
    {
        $newBody = MUtil_Html::create($name);

        if ($this->_content[$name] instanceof MUtil_Html_TBodyElement) {
            $newBody->_attribs = $this->_content[$name]->_attribs;
            $newBody->defaultRowClass = $this->_content[$name]->getDefaultRowClass();
        }

        return $newBody;
    }

    private function _pivotContent($headerRows, $footerRows)
    {
        $newContent[self::CAPTION]   = $this->_content[self::CAPTION];
        $newContent[self::COLGROUPS] = null;  // Never copied, has lost it's meaning.
        $newContent[self::THEAD] = $headerRows ? $this->_pivotBody(self::THEAD) : null;
        $newContent[self::TFOOT] = $footerRows ? $this->_pivotBody(self::TFOOT) : null;
        $newContent[self::TBODY] = $this->_pivotBody(self::TBODY);

        $order   = array($this->_content[self::THEAD], $this->_content[self::TBODY], $this->_content[self::TFOOT]);
        $newRows = array();

        foreach ($order as $oldBody) {

            if ($oldBody) {
                foreach ($oldBody as $row) {

                    $bodyRepeater = $oldBody->getRepeater();

                    $i = 0;
                    foreach ($row as $cell) {
                        if (! isset($newRows[$i])) {
                            $newRows[$i] = MUtil_Html::create('tr');
                        }
                        $newCell = clone $cell;

                        /* // Cancelled: does not work, wait for test case
                        if (isset($cell->rowspan)) {
                            $newCell->colspan = $cell->rowspan;
                        }
                        if (isset($cell->colspan)) {
                            $newCell->rowspan = $cell->colspan;
                        } // */

                        // Row attributes must go somewhere
                        foreach ($row->_attribs as $name => $value) {
                            if ($name !== 'class') {
                                $newCell->appendAttrib($name, $value);
                            }
                        }

                        if ($bodyRepeater) {
                            $newCell->setRepeater($bodyRepeater);
                            $newCell->setRepeatTags(true);
                        }

                        $newRows[$i][] = $newCell;
                        $i++;
                    }
                }
            }
        }

        $rowCount    = count($newRows);
        $footerStart = $rowCount - $footerRows;

        for ($i = 0; $i < $headerRows; $i++) {
            $newRows[$i]->appendAttrib('class', $newContent[self::THEAD]->getDefaultRowClass());
            $newContent[self::THEAD][] = $newRows[$i];
        }
        for ($i = $headerRows; $i < $footerStart; $i++) {
            $newRows[$i]->appendAttrib('class', $newContent[self::TBODY]->getDefaultRowClass());
            $newContent[self::TBODY][] = $newRows[$i];
        }
        for ($i = $footerStart; $i < $rowCount; $i++) {
            $newRows[$i]->appendAttrib('class', $newContent[self::TFOOT]->getDefaultRowClass());
            $newContent[self::TFOOT][] = $newRows[$i];
        }

        return $newContent;
    }

    public function addColumn($cell = null, $header = null, $footer = null)
    {
        $tds = $this->addColumnArray($cell, $header, $footer);

        if (count($tds) > 1) {
            // Return all objects in a wrapper object
            // that makes sure they are all treated
            // the same way.
            return new MUtil_MultiWrapper($tds);
        }

        // Return first object only
        return reset($tds);
    }

    public function addColumnArray($cell = null, $header = null, $footer = null)
    {
        // First make sure all existing body elements are at the same number of columns.
        // Do this now, so we do not have to do any colspan checks on $cell.
        if ($header || $this->_content[self::THEAD]) {
            $this->_equalizeColumnCounts(self::THEAD);
        }
        if ($footer || $this->_content[self::TFOOT]) {
            $this->_equalizeColumnCounts(self::TFOOT);
        }

        $tds[0] = $this->_content[self::TBODY]->td($cell);

        if ($header || $this->_content[self::THEAD]) {
            $tds[1] = $this->thead()->th($header);
        }

        if ($footer || $this->_content[self::TFOOT]) {
            $tds[2] = $this->tfoot()->td($footer);
        }

        return $tds;
    }

    public function addRepeater($repeater, $name = null)
    {
        return $this->_content[self::TBODY]->addRepeater($repeater, $name);
    }

    public function addRow($arg_array)
    {
        return $this->tr(func_get_args());
    }

    public function caption($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        if ($this->_content[self::CAPTION]) {
            $this->_content[self::CAPTION]->_processParameters($args);
        } else {
            $this->_content[self::CAPTION] = MUtil_Html::createArray('caption', $args);
        }

        return $this->_content[self::CAPTION];
    }

    public function col($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        if (is_array($this->_content[self::COLGROUPS])) {
            $colgroup = end($this->_content[self::COLGROUPS]);
        } else {
            $colgroup = null;
        }

        if (! $colgroup) {
            $colgroup = $this->colgroup();
        }

        return $colgroup->col($args);
    }

    public function colgroup($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        $colgroup = MUtil_Html::create()->colgroup($args);

        $this->_content[self::COLGROUPS][] = $colgroup;

        return $colgroup;
    }

    public static function createAlternateRowClass($class1 = 'odd', $class2 = 'even')
    {
        $args = func_get_args();

        // GHAAA!!!
        // func_get_args() does not assign default values.
        if (func_num_args() < 2) {
            if (func_num_args() < 1) {
                $args[] = $class1;
            }
            $args[] = $class2;
        }

        return new MUtil_Lazy_Alternate($args);
    }

    /**
     * Support function for renderVar(). Use of renderVar is preferred.
     *
     * print_r but then resulting in html tables.
     *
     * @param array $data An array or an array of arrays
     * @param $caption Optional caption
     * @param true|false|null $nested Optional, looks at first element of $data when null or not specified
     * @param array $objects_not_expanded Objects whose content should not be displayed. Used for preventing resursion.
     * @return self
     */
    public static function createArray($data, $caption = null, $nested = null, $objects_not_expanded = array())
    {
        if (! (is_array($data) || ($data instanceof Traversable))) {
            throw new MUtil_Html_HtmlException('The $data parameter is not an array or a Traversable interface instance ');
        }

        // Add the object to the not expand list if this is the first call.
        // Later additions are all done in renderVar()
        if (! $objects_not_expanded) {
            $objects_not_expanded[] = $data;
        }

        if (count($data) === 0) {
            return new MUtil_Html_Raw(self::RENDER_OPEN . self::RENDER_EMPTY_ARRAY . self::RENDER_CLOSE);
        }

        if (null === $nested) {
            if ((count($data) > 1) && is_array(reset($data))) {
                $nested = true;
                $count = count(reset($data));

                // Check all items
                foreach ($data as $row) {
                    if (count($row) !== $count) {
                        $nested = false;
                        break;
                    }
                }
            } else {
                $nested = false;
            }
        }

        if ($nested) {
            $repeater_data = MUtil_Lazy::repeat($data);
        } else {
            $repeater_data = new MUtil_Lazy_RepeatableByKeyValue($data);
        }

        $table = new self($repeater_data);
        if ($caption) {
            $table->caption($caption);
        }

        if ($nested) {
            $row = reset($data);
            if ($row) {
                foreach ($row as $key => $data) {
                    $table->addColumn(
                        MUtil_Lazy::call(array(__CLASS__, 'createVar'), $repeater_data[$key], null, $objects_not_expanded),
                        $key);
                }
            } else {
                $table->addColumn('-/-', '-/-');
            }
        } else {
            $table->addColumn(
                $repeater_data['key'],
                'Key');
            $table->addColumn(
                MUtil_Lazy::call(array(__CLASS__, 'createVar'), $repeater_data['value'], null, $objects_not_expanded),
                'Value');
        }

        return $table;
    }

    /**
     * print_r but then resulting in html tables.
     *
     * @param object $data The object whose public properties should be displayed
     * @param mixed $caption Caption to display above the object
     * @param array $objects_not_expanded Objects whose content should not be displayed. Used for preventing resursion.
     * @return self
     */
    public static function createObject($data, $caption = null, $objects_not_expanded = array())
    {
        if ($data instanceof MUtil_Lazy_LazyInterface) {
            return MUtil_Lazy::call(array(__CLASS__, 'createVar'), MUtil_Lazy::method('MUtil_Lazy::rise', $data), $caption, $objects_not_expanded);
        }

        // Add the object to the not expand list if this is the first call.
        // Later additions are all done in renderVar()
        if (! $objects_not_expanded) {
            $objects_not_expanded[] = $data;
        }

        $repeater_data = new MUtil_Lazy_RepeatableObjectProperties($data);

        if (null === $caption) {
            $classcaption = 'Class: ' . get_class($data);
        } else {
            $classcaption = $caption;
        }

        $table = new self();

        $table->caption($classcaption);

        if ($repeater_data->hasProperties()) {
            $table->setRepeater($repeater_data);

            $table->addColumn($repeater_data->name, 'Name');
            $table->addColumn(MUtil_Lazy::call(array(__CLASS__, 'createVar'), MUtil_Lazy::call('MUtil_Lazy::rise', $repeater_data->value), null, $objects_not_expanded), 'Value');
            $table->addColumn($repeater_data->from_code->if('in code', 'in program'), 'Defined');
            // $table->addColumn(MUtil_Lazy::iff($repeater_data->from_code, 'in code', 'in program'), 'Defined');

        } else {
            if ($data instanceof Traversable) {
                return self::createArray(iterator_to_array($data, true), $caption, null, $objects_not_expanded);
            }

            $table->td()->em(
                new MUtil_Html_Raw(self::RENDER_OPEN .
                    'No public properties for class' .
                    (null === $caption ? '' : ' ' . get_class($data)) .
                    self::RENDER_CLOSE));

        }

        return $table;
    }

    /**
     * print_r but then resulting in html tables.
     *
     * @param $data Any data to display
     * @param $caption Optional caption
     * @param array $objects_not_expanded Objects whose content should not be displayed. Used for preventing resursion.
     * @return self
     */
    public static function createVar($data, $caption = null, $objects_not_expanded = array())
    {
        foreach ($objects_not_expanded as $item) {
            if ($item === $data) {
                return new MUtil_Html_Raw(self::RENDER_OPEN . self::RENDER_CIRCULAR . self::RENDER_CLOSE);
            }
        }

        $objects_not_expanded[] = $data;
        // array_push($objects_not_expanded, $data);

        if (is_array($data) || ($data instanceof Traversable)) {
            $result = self::createArray($data, $caption, null, $objects_not_expanded);

        } elseif (is_object($data)) {
            $result = self::createObject($data, $caption, $objects_not_expanded);

        } elseif (null === $data) {
            return new MUtil_Html_Raw(self::RENDER_OPEN . self::RENDER_EMPTY . self::RENDER_CLOSE);

        } elseif ('' === $data) {
            return new MUtil_Html_Raw(self::RENDER_OPEN . self::RENDER_EMPTY_STRING . self::RENDER_CLOSE);

        } elseif (is_string($data)) {
            $result = $data; // Removed htmlentities(): was double since introduction of MUtil_Html_Raw()

        } else {
            $result = $data;
        }

        array_pop($objects_not_expanded);

        return $result;
    }

    /**
     * Returns the cell or a MUtil_MultiWrapper containing cells that occupy the column position, taking colspan and other functions into account.
     *
     * @param int $col The numeric column position, starting at 0;
     * @return MUtil_Html_HtmlElement Probably an element of this type, but can also be something else, posing as an element.
     */
    public function getColumn($col)
    {
        $count = -1;
        foreach ($this->_content as $cell) {
            $count += self::getCellWidth($cell);

            if ($count >= $col) {
                return $cell;
            }
        }
    }

    /**
     * Returns the cells that occupies the column position, taking colspan and other functions into account, in an array.
     *
     * @param int $col The numeric column position, starting at 0;
     * @return array Of probably one MUtil_Html_HtmlElement
     */
    public function getColumnArray($col)
    {
        $results = array();

        foreach ($this->_content as $body) {
            if ($body instanceof MUtil_Html_ColumnInterface) {
                $results = array_merge($results, $body->getColumnArray($col));
            }
        }

        return $results;
    }

    /**
     * Return the number of columns, taking such niceties as colspan into account
     *
     * @return int
     */
    public function getColumnCount()
    {
        $counts[] = 1;

        foreach ($this->_content as $body) {
            if ($body instanceof MUtil_Html_ColumnInterface) {
                $counts[] = $body->getColumnCount();
            }
        }

        return max($counts);
    }

    public function getDefaultRowClass()
    {
        return $this->_content[self::TBODY]->getDefaultRowClass();
    }

    public function getOnEmpty()
    {
        return $this->_content[self::TBODY]->getOnEmpty($this);
    }

    public function getRepeater()
    {
        if ($this->_repeater) {
            // Happens only during rendering when $this->_pivot is set
            // Is set because Lazy execution expects it.
            return $this->_repeater;
        }
        return $this->_content[self::TBODY]->getRepeater();
    }

    /**
     * Renders the element tag with it's content into a html string
     *
     * The $view is used to correctly encode and escape the output
     *
     * @param Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    public function render(Zend_View_Abstract $view)
    {
        if ($this->_pivot) {
            $oldContent = $this->_content;

            $this->_repeater = $this->getRepeater(); // Cache for bridge
            $this->_content  = $this->_pivotContent($this->_pivot[self::THEAD], $this->_pivot[self::TFOOT]);

            $html = parent::render($view);

            $this->_repeater = null;
            $this->_content  = $oldContent;

            return $html;

        } else {
            return parent::render($view);
        }
    }

    /**
     * Support function for renderVar(). Use of renderVar is preferred.
     *
     * print_r but then resulting in html tables.
     *
     * @param Zend_View_Abstract $view
     * @param array $data An array or an array of arrays
     * @param $caption Optional caption
     * @param true|false|null $nested Optional, looks at first element of $data when null or not specified
     * @return string
     */
    public static function renderArray(Zend_View_Abstract $view, array $data, $caption = null, $nested = null)
    {
        return self::createArray($data, $caption, $nested)->render($view);
    }

    /**
     * print_r but then resulting in html tables.
     *
     * @param Zend_View_Abstract $view
     * @param object $data The object whose public properties should be displayed
     * @param mixed $caption Caption to display above the object
     * @return string
     */
    public static function renderObject(Zend_View_Abstract $view, $data, $caption = null)
    {
        return self::createObject($data, $caption)->render($view);
    }

    /**
     * print_r but then resulting in html tables.
     *
     * @param Zend_View_Abstract $view
     * @param $data Any data to display
     * @param $caption Optional caption
     * @return string
     */
    public static function renderVar(Zend_View_Abstract $view, $data, $caption = null)
    {
        return self::createVar($data, $caption)->render($view);
    }

    public function setAlternateRowClass($class1 = 'odd', $class2 = 'even')
    {
        $args = func_get_args();

        // GHAAA!!!
        // func_get_args() does not assign default values.
        if (func_num_args() < 2) {
            if (func_num_args() < 1) {
                $args[] = $class1;
            }
            $args[] = $class2;
        }

        $this->_content[self::TBODY]->setDefaultRowClass(new MUtil_Lazy_Alternate($args));
        return $this;
    }

    public function setAsFormLayout(Zend_Form $form, $add_description = false, $include_description = false)
    {
        // Make a Lazy repeater for the form elements and set it as the element repeater
        $formrep = new MUtil_Lazy_RepeatableFormElements($form);
        $formrep->setSplitHidden(true); // These are treated separately
        $this->setRepeater($formrep);

        // Add the columns
        $this->addColumn($formrep->label); // For label
        // $this->tdh()->label('[', $formrep->element, ']');

        $elements[] = $formrep->element;
        $elements[] = ' ';
        $elements[] = $formrep->errors;
        if ($add_description && $include_description) {
            $elements[] = ' ';
            $elements[] = $formrep->description;
        }
        $this->addColumn($elements); // Element, Error & optional description
        if ($add_description && (! $include_description)) {
            $this->addColumn($formrep->description); // Description in separate column
        }

        // Set this element as the form decorator
        $decorator = new MUtil_Html_ElementDecorator();
        $decorator->setHtmlElement($this);
        $decorator->setPrologue($formrep); // Renders hidden elements before this element
        $form->setDecorators(array($decorator, 'AutoFocus', 'Form'));

        return $this;
    }

    /**
     * Set the default row class of the tbody item (as the table has rows)
     *
     * When a new row is added to the body it is autmatically given the
     * class attribute specified here.
     *
     * @param string $tag Tagname
     * @return MUtil_Html_TableElement (continuation pattern)
     */
    public function setDefaultRowClass($class)
    {
        // The class can be null
        $this->_defaultRowClassSet = true;
        $this->_content[self::TBODY]->setDefaultRowClass($class);
        return $this;
    }

    /**
     * Setting a table to pviot left-rotates the table at rendering time.
     *
     * The header rows become the first columns, the body rows form the next
     * set of columns and lastly the footer rows become the rightmost columns.
     *
     * In other words: this humble setting switches a table with repeating rows
     * in a table with repeating columns.
     *
     * @param boolean $pivot True to switch to left rotated pivot when rendering
     * @param int $headerRows The number of pivoted rows going to in the header
     * @param int $footerRows The number of pivoted rows going to in the footer
     * @return MUtil_Html_TableElement (continuation pattern)
     */
    public function setPivot($pivot, $headerRows = 0, $footerRows = 0)
    {
        if ($pivot) {
            $this->_pivot = array(self::THEAD => $headerRows, self::TFOOT => $footerRows);
        } else {
            $this->_pivot = false;
        }
        return $this;
    }

    /**
     * Set the content displayed by the tbody item when it is empty during rendering.
     *
     * Overruled as the onEmpty is not set on this element itself.
     *
     * @see $_onEmptyContent;
     *
     * @param mixed $content Content that can be rendered.
     * @return MUtil_Html_TableElement (continuation pattern)
     */
    public function setOnEmpty($content)
    {
        $this->_content[self::TBODY]->setOnEmpty($content, $this);
        return $this;
    }

    /**
     * Overruled as setting a table repeater means repeating the content of the tbody element.
     * Not repeating thead, tfoot and tbody elements but just repeating the rows in the tbody element.
     *
     * Repeat the element when rendering.
     *
     * When repeatTags is false (the default) only the content is repeated but
     * not the element tags. When repeatTags is true the both the tags and the
     * content are repeated.
     *
     * @param mixed $repeater MUtil_Lazy_RepeatableInterface or something that can be made into one.
     * @param mixed $onEmptyContent Optional. When not null the content to display when the repeater does not result in data is set.
     * @param boolean $repeatTags Optional when not null the repeatTags switch is set.
     * @return MUtil_Html_TableElement (continuation pattern)
     */
    public function setRepeater($repeater, $onEmptyContent = null, $repeatTags = null)
    {
        $this->_content[self::TBODY]->setRepeater($repeater, $onEmptyContent, $repeatTags, $this);
        return $this;
    }

    /**
     * Static helper function for creation, used by @see MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TableElement
     */
    public static function table($arg_array = null)
    {
        $args = func_get_args();
        return new self($args);
    }

    /**
     * Returns the tbody element.
     *
     * Addition of multiple bodies is not possible.
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TBodyElement With 'tbody' tagName
     */
    public function tbody($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        if ($args) {
            $this->_content[self::TBODY]->_processParameters($args);
        }

        return $this->_content[self::TBODY];
    }

    /**
     * Returns a 'td' cell in the current row in the tbody
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'td' tagName
     */
    public function td($arg_array = null)
    {
        $args = func_get_args();
        return $this->_content[self::TBODY]->td($args);
    }

    /**
     * Returns a 'th' cell in the current row in the tbody
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'th' tagName
     */
    public function tdh($arg_array = null)
    {
        $args = func_get_args();
        return $this->_content[self::TBODY]->th($args);
    }

    /**
     * Returns a 'td' cell in a new row in the body with a colspan equal to the number of columns in the table.
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'td' tagName
     */
    public function tdrow($arg_array = null)
    {
        $args = func_get_args();
        $cell = $this->tr()->td($args, array('colspan' => $this->toLazy()->getColumnCount()));

        // Make sure the next item is not added to this row.
        $this->_content[self::TBODY]->_lastChild = null;

        return $cell;
    }

    /**
     * Returns a 'td' cell in the current row in the footer
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'td' tagName
     */
    public function tf($arg_array = null)
    {
        $args = func_get_args();
        return $this->tfoot()->td($args);
    }

    /**
     * Returns a 'th' cell in the current row in the footer
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'th' tagName
     */
    public function tfh($arg_array = null)
    {
        $args = func_get_args();
        return $this->tfoot()->th($args);
    }

    /**
     * Returns the tfoot element. Creates when needed.
     *
     * Addition of multiple bodies is not possible.
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TBodyElement With 'tfoot' tagName
     */
    public function tfoot($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        if (! $this->_content[self::TFOOT]) {
            $this->_content[self::TFOOT] = MUtil_Html::create('tfoot');
        }
        if ($args) {
            $this->_content[self::TFOOT]->_processParameters($args);
        }

        return $this->_content[self::TFOOT];
    }

    /**
     * Returns a 'td' cell in a new row in the footer with a colspan equal to the number of columns in the table.
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'td' tagName
     */
    public function tfrow($arg_array = null)
    {
        $args = func_get_args();
        $cell = $this->tfoot()->tr()->td($args, array('colspan' => $this->toLazy()->getColumnCount()));

        // Make sure the next item is not added to this row.
        $this->_content[self::TBODY]->_lastChild = null;

        return $cell;
    }

    /**
     * Returns a 'th' cell in the current row in the header
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'th' tagName
     */
    public function th($arg_array = null)
    {
        $args = func_get_args();
        return $this->thead()->th($args);
    }

    /**
     * Returns the thead element. Creates when needed.
     *
     * Addition of multiple bodies is not possible.
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TBodyElement With 'thead' tagName
     */
    public function thead($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        if (! $this->_content[self::THEAD]) {
            $this->_content[self::THEAD] = MUtil_Html::create('thead');
        }
        if ($args) {
            $this->_content[self::THEAD]->_processParameters($args);
        }

        return $this->_content[self::THEAD];
    }

    /**
     * Returns a 'td' cell in the current row in the header
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'td' tagName
     */
    public function thd($arg_array = null)
    {
        $args = func_get_args();
        return $this->thead()->td($args);
    }

    /**
     * Returns a 'td' cell in a new row in the header with a colspan equal to the number of columns in the table.
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'td' tagName
     */
    public function thdrow($arg_array = null)
    {
        $args = func_get_args();
        return $this->thead()->td($args, array('colspan' => $this->toLazy()->getColumnCount()));
    }

    /**
     * Returns a 'th' cell in a new row in the header with a colspan equal to the number of columns in the table.
     *
     * The name should be 'throw', but that is not an allowed function, so we redefined it as an alias in __call
     *
     * @see __call
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_HtmlElement With 'th' tagName
     */
    public function thhrow($arg_array = null)
    {
        // throw is not an allowed function name. Implemented in __call

        $args = func_get_args();
        $cell = $this->thead()->tr()->th($args, array('colspan' => $this->toLazy()->getColumnCount()));

        // Make sure the next item is not added to this row.
        $this->_content[self::TBODY]->_lastChild = null;

        return $cell;
    }

    /**
     * Returns a new row in the tbody.
     *
     * Also signals thead and tfoot that we are on a new row for addColumn() addition.
     *
     * @see addColumn
     * @see addColumnArray
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TrElement
     */
    public function tr($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        if ($this->_content[self::THEAD]) {
            $this->_content[self::THEAD]->_lastChild = null;
        }

        if ($this->_content[self::TFOOT]) {
            $this->_content[self::TFOOT]->_lastChild = null;
        }

        return $this->_content[self::TBODY]->tr($args);
    }
}
