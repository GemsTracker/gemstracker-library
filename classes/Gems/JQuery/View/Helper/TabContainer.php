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
 * Extension to the default TabContainer ViewHelper
 *
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

require_once "ZendX/JQuery/View/Helper/TabContainer.php";

/**
 * Extension to allow passing what tab was selected and highlighting tabs with errors
 *
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_JQuery_View_Helper_TabContainer extends \ZendX_JQuery_View_Helper_TabContainer
{

    /**
     * Render TabsContainer with all the currently registered tabs.
     *
     * Render all tabs to the given $id. If no arguments are given the
     * tabsContainer view helper object is returned and can be used
     * for chaining {@link addPane()} for tab pane adding.
     *
     * Only change to the normal helper is that we filter out which tab is selected
     * and add a class to tabs with errors
     *
     * @link   http://docs.jquery.com/UI/Tabs
     * @param  string $id
     * @param  array  $params
     * @param  array  $attribs
     * @return string|\ZendX_JQuery_View_Helper_TabsContainer
     */
    public function tabContainer($id=null, $params=array(), $attribs=array())
    {
        if(func_num_args() === 0) {
            return $this;
        }

        $useBootstrap = \MUtil_Bootstrap::enabled();

        if(!isset($attribs['id'])) {
            $attribs['id'] = $id;
        }

        //Inserted: take care of the selected tab
        $selected = false;
        $firstSelected = true;
        if(isset($attribs['selected'])) {
            $selected = $attribs['selected'];
            unset($attribs['selected']);
            $firstSelected = false;
        }

        $content = "";
        if(isset($this->_tabs[$id])) {

            if ($useBootstrap) {
                $list = '<ul class="nav nav-tabs">'.PHP_EOL;
            } else {
                $list = '<ul class="ui-tabs-nav">'.PHP_EOL;
            }
            $html = '<div class="tab-content">';
            $fragment_counter = 1;
            $tabIndex = 0;
            foreach($this->_tabs[$id] AS $k => $v) {
                $frag_name = sprintf('%s-frag-%d', $attribs['id'], $fragment_counter++);
                $opts = $v['options'];

                $class = '';
                if (!$useBootstrap) {
                    $class = 'ui-tabs-nav-item ';
                }
                $active = '';
                if (isset($opts['class'])) {
                  $class .= $opts['class'];
                }
                if ($firstSelected || $tabIndex == $selected) {
                  $class .= ' active';
                  $active = ' active';
                  $firstSelected = false;
                }
                $tabIndex++;

                if ($class) {
                  $class = ' class="'.$class.'"';
                }
                if(isset($opts['contentUrl'])) {
                    $list .= '<li'.$class.'><a role="tab" data-toggle="tab" href="'.$opts['contentUrl'].'"><span>'.$v['name'].'</span></a></li>'.PHP_EOL;
                } else {
                    $list .= '<li'.$class.'><a role="tab" data-toggle="tab" href="#'.$frag_name.'"><span>'.$v['name'].'</span></a></li>'.PHP_EOL;
                    $html .= '<h3 class="print-only">' . $v['name'] . '</h3>'; /* For printing of tab forms */
                    $html .= '<div id="'.$frag_name.'" class="tab-pane'.$active.'">'.$v['content'].'</div>'.PHP_EOL;
                }
            }
            $list .= '</ul>'.PHP_EOL;
            $html .= '</div>'.PHP_EOL;
            $content = $list.$html;
            unset($this->_tabs[$id]);
        }

        if(count($params)) {
            $params = \ZendX_JQuery::encodeJson($params);
        } else {
            $params = '{}';
        }

        $html = '<div'
              . $this->_htmlAttribs($attribs)
              . '>'.PHP_EOL
              . $content
              . '</div>'.PHP_EOL;

        //Load the selected tab if jQuery UI tabs
        if (!$useBootstrap) {
            $js = sprintf('%s("#%s").tabs(%s);',
                \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
                $attribs['id'],
                $params
            ); 
            if (isset($selected)) {
                $js .= sprintf('
                    %s("#%s").tabs("option", "active", %d);',
                    \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
                    $attribs['id'],
                    $selected
                );
            }
            $this->jquery->addOnLoad($js);
        }

        return $html;
    }
}