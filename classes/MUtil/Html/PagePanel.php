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
 *
 *
 * @package    MUtil
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Html Element used to display paginator page links and links to increase or decrease
 * the number of items shown.
 *
 * Includes functions for specirfying your own text and separators.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html_PagePanel extends MUtil_Html_Sequence implements MUtil_Lazy_Procrastinator
{
    /**
     * Fixed addition to url's required for links, i.e. htte
     * /pulse part in http://localhost/pulse/respondent/index
     *
     * @var array
     */
    protected $_baseUrl = array();

    /**
     * The current page number
     *
     * @var int
     */
    protected $_currentPage;

    /**
     * Default current page number
     * @var int
     */
    protected $_currentPageDefault = 1;

    /**
     * Parameter name to specify new current page
     *
     * @var string
     */
    protected $_currentPageParam   = 'page';

    /**
     * Array containing default content / attributes for links
     *
     * @var array
     */
    protected $_defaultContent         = array();

    /**
     * Array containing default content / attributes for disabled links
     *
     * @var array
     */
    protected $_defaultDisabledContent = array('class' => 'disabled');

    /**
     * Array containing default content / attributes for enabled links
     *
     * @var array
     */
    protected $_defaultEnabledContent  = array();

    /**
     * The number of items per page
     *
     * @var int
     */
    protected $_itemCount;

    /**
     * The default number of items per page
     *
     * @var int
     */
    protected $_itemCountDefault = 10;

    /**
     * The request parameter / cookie name containing number of items per page
     *
     * @var string
     */
    protected $_itemCountParam   = 'items';

    /**
     * The default decrease / increase steps in the number of items per page
     *
     * @var int
     */
    protected $_itemCountValues  = array(5, 10, 15, 20, 50, 100, 200, 500, 1000, 2000);

    /**
     * Lazy instance of this object
     *
     * @var MUtil_Lazy_ObjectWrap
     */
    protected $_lazy;

    /**
     * Returns the current page collection.
     *
     * @return array
     */
    protected $_pages;

    /**
     * The core paginator.
     *
     * @var Zend_Paginator
     */
    protected $_paginator;

    /**
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request;

    /**
     * One of 'all', 'elastic', 'jumping', 'sliding' or whatever the current
     * possible scolling styles are for the Zend Framework version in use.
     *
     * @var string
     */
    protected $_scrollingStyle = 'sliding';

    /**
     * Extra array with special types for subclasses.
     *
     * When an object of one of the key types is used, then use
     * the class method defined as the value.
     *
     * @var array
     */
    protected $_specialTypes = array(
        'Zend_Controller_Request_Abstract' => 'setRequest',
        'Zend_Paginator'                   => 'setPaginator',
        'Zend_View'                        => 'setView',
        );

    /**
     * Lazy call to the _pages parameter
     *
     * @var MUtil_Lazy_ObjectWrap
     */
    public $pages;

    /**
     * Lazy call to the _paginator parameter.
     *
     * @var MUtil_Lazy_ObjectWrap
     */
    public $paginator;

    protected function _applyDefaults($condition, array $args)
    {
        // Apply default arguments
        $args = $args + $this->_defaultContent;

        foreach ($this->_defaultEnabledContent as $key => $content) {
            $other = isset($args[$key]) ? $args[$key] : null;
            if ($other instanceof MUtil_Html_AttributeInterface) {
                $other->add(MUtil_Lazy::iff($condition, $content));
            } else {
                $args[$key] = MUtil_Lazy::iff($condition, $content, $other);
            }
        }
        foreach ($this->_defaultDisabledContent as $key => $content) {
            $other = isset($args[$key]) ? $args[$key] : null;
            if ($other instanceof MUtil_Html_AttributeInterface) {
                $other->add(MUtil_Lazy::iff($condition, null, $content));
            } else {
                $args[$key] = MUtil_Lazy::iff($condition, $other, $content);
            }
        }

        return $args;
    }

    protected function _checkVariables($force = false)
    {
        if ($this->_paginator) {
            if ($force || $this->_currentPage || $this->_request) {
                $this->_paginator->setCurrentPageNumber($this->getCurrentPage());
            }
            if ($force || $this->_itemCount || $this->_request) {
                if (! $this->_itemCount) {
                    $this->getItemCount();
                }
                // Recently found trick, can save a complicated database query
                $adapter = $this->_paginator->getAdapter();
                if ($adapter instanceof MUtil_Paginator_Adapter_PrefetchInterface) {
                    $adapter->getItems($this->_currentPage, $this->_itemCount);
                }
                $this->_paginator->setItemCountPerPage($this->_itemCount);
            }
        }
    }

    protected function _createHref($param, $page)
    {
        return new MUtil_Html_HrefArrayAttribute(array($param => $page) + $this->_baseUrl);
    }

    public function createCountLink($condition, $count, array $args)
    {
        // Use the condition for the $href
        $element = MUtil_Html::create()->a(
            MUtil_Lazy::iff($condition, $this->_createHref($this->_itemCountParam, $count)),
            $this->_applyDefaults($condition, $args));

        // and make the tagName an if
        $element->tagName = MUtil_Lazy::iff($condition, 'a', 'span');

        return $element;
    }

    /**
     * Returns an element with a conditional tagName: it will become either an A or a SPAN
     * element.
     *
     * @param MUtil_Lazy $condition Condition for link display
     * @param int $page    Page number of this link
     * @param array $args  Content of the page
     * @return \MUtil_Html_HtmlElement
     */
    public function createPageLink($condition, $page, array $args)
    {
        $element = new MUtil_Html_HtmlElement(
                MUtil_Lazy::iff($condition, 'a', 'span'),
                array('href' => MUtil_Lazy::iff($condition, $this->_createHref($this->_currentPageParam, $page))),
                $this->_applyDefaults($condition, $args)
                );

        return $element;
    }

    public function firstPage($label = '<<', $args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        // Apply default
        if (! isset($args[0])) {
            $args[] = '<<';
        }

        return $this->createPageLink($this->pages->previous, $this->pages->first, $args);
    }

    /**
     * Return the location where to store the cookies for the panel
     *
     * @return string
     */
    protected function getCookieLocation()
    {
        $request = $this->getRequest();

        $front  = Zend_Controller_Front::getInstance();
        $result = $request->getBasePath();

        $bname = $request->getModuleKey();
        if (isset($this->_baseUrl[$bname])) {
            $result .= '/' . $this->_baseUrl[$bname];
        } elseif (($val = $request->getModuleName()) && ($val != $front->getDefaultModule())) {
            $result .= '/' . $val;
        }

        $bname = $request->getControllerKey();
        if (isset($this->_baseUrl[$bname])) {
            $result .= '/' . $this->_baseUrl[$bname];
        } elseif (($val = $request->getControllerName())  && ($val != $front->getDefaultControllerName())) {
            $result .= '/' . $val;
        }

        $bname = $request->getActionKey();
        if (isset($this->_baseUrl[$bname])) {
            $result .= '/' . $this->_baseUrl[$bname];
        } elseif (($val = $request->getActionName())  && ($val != $front->getDefaultAction())) {
            $result .= '/' . $val;
        }

        return $result;
    }


    /**
     * Return the current page number (calculated if necessary)
     *
     * @return int
     */
    public function getCurrentPage()
    {
        if (null === $this->_currentPage) {
            $this->_currentPage = $this->getCurrentPageDefault();

            if ($param_name = $this->getCurrentPageParam()) {
                $request = $this->getRequest();

                if (isset($this->_baseUrl[$param_name])) {
                    $this->_currentPage = $this->_baseUrl[$param_name];
                    // Set cookie
                } elseif ($currentPage = $request->getParam($param_name)) {
                    $this->_currentPage = $currentPage;
                    // Set cookie
                } elseif ($request instanceof Zend_Controller_Request_Http) {
                    $this->_currentPage = $request->getCookie($param_name, $this->_currentPage);
                }
            }
        }

        return $this->_currentPage;
    }

    public function getCurrentPageDefault()
    {
        return $this->_currentPageDefault;
    }

    public function getCurrentPageParam()
    {
        return $this->_currentPageParam;
    }

    /**
     * Get the current number of items per page from:
     *  - the baseUrl or
     *  - the request or
     *  - a cookie or
     *  - use the default
     *
     * @return int
     */
    public function getItemCount()
    {
        if (null === $this->_itemCount) {
            if ($param_name = $this->getItemCountParam()) {
                $request = $this->getRequest();

                if (isset($this->_baseUrl[$param_name])) {
                    $this->_itemCount = $this->_baseUrl[$param_name];
                } else {
                    $this->_itemCount = $request->getParam($param_name);
                }

                if ($this->_itemCount) {
                    // Store the current value
                    setcookie($param_name, $this->_itemCount, time() + (30 * 86400), $this->getCookieLocation());

                } elseif ($request instanceof Zend_Controller_Request_Http) {
                    $this->_itemCount = $request->getCookie($param_name, $this->getItemCountDefault());
                }
            }
        }

        return $this->_itemCount;
    }

    public function getItemCountDefault()
    {
        return $this->_itemCountDefault;
    }

    public function getItemCountLess()
    {
        $pos = array_search($this->getItemCount(), $this->_itemCountValues);
        if ($pos || ($pos === 0)) {
            $pos--;

            if (isset($this->_itemCountValues[$pos])) {
                return $this->_itemCountValues[$pos];
            }
        }
    }

    public function getItemCountMax()
    {
        return max($this->_itemCountValues);
    }

    public function getItemCountMore()
    {
        $pos = array_search($this->getItemCount(), $this->_itemCountValues);
        if ($pos || ($pos === 0)) {
            $pos++;

            if (isset($this->_itemCountValues[$pos])) {
                return $this->_itemCountValues[$pos];
            }
        }
    }

    public function getItemCountNotMax()
    {
        return $this->getItemCount() != $this->getItemCountMax();
    }

    public function getItemCountParam()
    {
        return $this->_itemCountParam;
    }

    /**
     * Returns the page collection.
     *
     * @param  string $scrollingStyle Scrolling style
     * @return array
     */
    public function getPages($scrollingStyle = null)
    {
        if (null === $scrollingStyle) {
            $scrollingStyle = $this->_scrollingStyle;
        }

        if ((! $this->_pages) || ($scrollingStyle != $this->_scrollingStyle)) {
            $this->_pages = $this->_paginator->getPages($scrollingStyle);
            $this->_scrollingStyle = $scrollingStyle;
        }

        return $this->_pages;
    }

    public function getPaginator()
    {
        return $this->_paginator;
    }

    /**
     * Return the Request object
     *
     * @return Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        if (! $this->_request) {
            $front = Zend_Controller_Front::getInstance();
            $this->setRequest($front->getRequest());
        }

        return $this->_request;
    }

    public function getScrollingStyle()
    {
        return $this->_scrollingStyle;
    }

    protected function init()
    {
        parent::init();

        $this->paginator = $this->toLazy()->getPaginator();
        $this->pages     = $this->toLazy()->getPages();
    }

    public function lastPage($label = '>>', $args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        // Apply default
        if (! isset($args[0])) {
            $args[] = '>>';
        }

        return $this->createPageLink($this->pages->next, $this->pages->last, $args);
    }

    public function nextPage($label = '>', $args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        // Apply default
        if (! isset($args[0])) {
            $args[] = '>';
        }

        return $this->createPageLink($this->pages->next, $this->pages->next, $args);
    }

    /**
     * Returns a sequence of frist, previous, range, next and last conditional links.
     *
     * The condition is them being valid links, otherwise they are returned as span
     * elements.
     *
     * Note: This sequence is not added automatically to this object, you will have to
     * position it manually.
     *
     * @param string $first Label for goto first page link
     * @param string $previous Label for goto previous page link
     * @param string $next Label for goto next page link
     * @param string $last Label for goto last page link
     * @param string $glue In between links glue
     * @param mixed $args MUtil_Ra::args extra arguments applied to all links
     * @return MUtil_Html_Sequence
     */
    public function pageLinks($first = '<<', $previous = '<', $next = '>', $last = '>>', $glue = ' ', $args = null)
    {
        $argDefaults = array('first' => '<<', 'previous' => '<', 'next' => '>', 'last' => '>>', 'glue' => ' ');
        $argNames    = array_keys($argDefaults);

        $args = MUtil_Ra::args(func_get_args(), $argNames, $argDefaults);

        foreach ($argNames as $name) {
            $$name = $args[$name];
            unset($args[$name]);
        }

        $div = MUtil_Html::create()->sequence(array('glue' => $glue));

        if ($first) { // Can be null or array()
            $div[] = $this->firstPage((array) $first + $args);
        }
        if ($previous) { // Can be null or array()
            $div[] = $this->previousPage((array) $previous + $args);
        }
        $div[] = $this->rangePages($glue, $args);
        if ($next) { // Can be null or array()
            $div[] = $this->nextPage((array) $next + $args);
        }
        if ($last) { // Can be null or array()
            $div[] = $this->lastPage((array) $last + $args);
        }

        return MUtil_Lazy::iff(MUtil_Lazy::comp($this->pages->pageCount, '>', 1), $div);
    }

    /**
     * Create a page panel
     *
     * @param mixed $paginator MUtil_Ra::args() for an MUtil_Html_Sequence
     * @param mixed $request
     * @param mixed $args
     * @return self
     */
    public static function pagePanel($paginator = null, $request = null, $args = null)
    {
        $args = func_get_args();

        $pager = new self($args);

        $pager[] = $pager->pageLinks();
        $pager->div($pager->uptoOffDynamic(), array('style' => 'float: right;'));

        return $pager;
    }

    public function previousPage($label = '<', $args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        // Apply default
        if (! isset($args[0])) {
            $args[] = '<';
        }

        return $this->createPageLink($this->pages->previous, $this->pages->previous, $args);
    }

    public function rangePages($glue = ' ', $args_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), array('glue'), array('glue' => ' '));

        return new MUtil_Html_PageRangeRenderer($this, $args);
    }

    public function setBaseUrl(array $baseUrl = null)
    {
        $this->_baseUrl = (array) $baseUrl;
        return $this;
    }

    public function setCurrentPage($currentPage)
    {
        $this->_currentPage = $currentPage;
        $this->_checkVariables();

        return $this;
    }

    public function setCurrentPageDefault($currentPageDefault)
    {
        $this->_currentPageDefault = $currentPageDefault;
        return $this;
    }

    public function setCurrentPageParam($currentPageParam)
    {
        $this->_currentPageParam = $currentPageParam;
        return $this;
    }

    public function setItemCount($itemCount)
    {
        $this->_itemCount = $itemCount;
        $this->_checkVariables();

        return $this;
    }

    public function setItemCountDefault($itemCountDefault)
    {
        $this->_itemCountDefault = $itemCountDefault;
        return $this;
    }

    public function setItemCountParam($itemCountParam)
    {
        $this->_itemCountParam = $itemCountParam;
        return $this;
    }

    /**
     *
     * @param Zend_Paginator $paginator
     * @return \MUtil_Html_PagePanel (continuation pattern)
     */
    public function setPaginator(Zend_Paginator $paginator)
    {
        $this->_paginator = $paginator;

        if ($this->view) {
            $this->_paginator->setView($this->view);
        }
        $this->_checkVariables();

        return $this;
    }

    /**
     * Set the Request object
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return \MUtil_Html_PagePanel (continuation pattern)
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->_request = $request;
        $this->_checkVariables();

        return $this;
    }

    public function setScrollingStyle($scrollingStyle)
    {
        $this->_scrollingStyle = $scrollingStyle;
        return $this;
    }

    /**
     * Set the View object
     *
     * @param  Zend_View_Interface $view
     * @return \MUtil_Html_PagePanel (continuation pattern)
     */
    public function setView(Zend_View_Interface $view)
    {
        if ($this->_paginator) {
            $this->_paginator->setView($view);
        }

        return parent::setView($view);
    }

    /**
     * Returns a lazy instance of item. Do NOT use MUtil_Lazy::L() in this function!!!
     *
     * @return MUtil_Lazy_LazyInterface
     */
    public function toLazy()
    {
        if (! $this->_lazy) {
            $this->_lazy = new MUtil_Lazy_ObjectWrap($this);
        }

        return $this->_lazy;
    }

    public function uptoOff($upto = '-', $off = '/', $glue = ' ')
    {
        $seq = new MUtil_Html_Sequence();
        $seq->setGlue($glue);
        $seq->if($this->pages->totalItemCount, $this->pages->firstItemNumber, 0);
        $seq[] = $upto;
        $seq[] = $this->pages->lastItemNumber;
        $seq[] = $off;
        $seq[] = $this->pages->totalItemCount;

        return $seq;
    }

    public function uptoOffDynamic($upto = '~', $off = '/', $less = '-', $more = '+', $all = null, $glue = ' ', $args = null)
    {
        $argDefaults = array('upto' => '~', 'off' => '/', 'less' => '-', 'more' => '+', 'all' => null, 'glue' => ' ');
        $argNames    = array_keys($argDefaults);

        $args = MUtil_Ra::args(func_get_args(), $argNames, $argDefaults);

        foreach ($argNames as $name) {
            $$name = $args[$name];
            unset($args[$name]);
        }

        $seq = new MUtil_Html_Sequence();
        $seq->setGlue($glue);
        if (null !== $upto) {
            $seq->if($this->pages->totalItemCount, $this->pages->firstItemNumber, 0);
            $seq[] = $upto;
        }
        if (null !== $less) {
            $cless = $this->toLazy()->getItemCountLess();
            $seq[] = $this->createCountLink($cless, $cless, (array) $less + $args);
        }
        if (null !== $upto) {
            $seq[] = $this->pages->lastItemNumber;
        }
        if (null !== $more) {
            $cmore = $this->toLazy()->getItemCountMore();
            $seq[] = $this->createCountLink($cmore, $cmore, (array) $more + $args);
        }
        if (null !== $all) {
            $seq[] = $this->createCountLink($this->toLazy()->getItemCountNotMax(), $this->toLazy()->getItemCountMax(), (array) $all + $args);
        }
        if (null !== $off) {
            if (null !== $upto) {
                $seq[] = $off;
            }
            $seq[] = $this->pages->totalItemCount;
        }

        return $seq;
    }
}
