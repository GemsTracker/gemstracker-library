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
 * @version    $Id: PagePanel.php 2089 2014-08-21 08:05:58Z mennodekker $
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
class MUtil_Bootstrap_Html_PagePanel extends MUtil_Html_PagePanel
{

    protected $range = false;

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
        $conditionLiClass = 'disabled';
        if ($this->range) {
            $conditionLiClass = 'active';
        }
        $li = MUtil_Html::create()->li(array('class' => MUtil_Lazy::iff($condition, '', $conditionLiClass)));
        $li[] = $element;
        return $li;
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

        $container = MUtil_Html::create()->ul(array('class' => 'pagination pagination-sm pull-left'));

        if ($first) { // Can be null or array()
            $container[] = $this->firstPage((array) $first + $args);
        }
        if ($previous) { // Can be null or array()
            $container[] = $this->previousPage((array) $previous + $args);
        }
        $this->range = true;
        $container[] = $this->rangePages('', $args);
        $this->range = false;
        if ($next) { // Can be null or array()
            $container[] = $this->nextPage((array) $next + $args);
        }
        if ($last) { // Can be null or array()
            $container[] = $this->lastPage((array) $last + $args);
        }

        return MUtil_Lazy::iff(MUtil_Lazy::comp($this->pages->pageCount, '>', 1), $container);
    }
}
