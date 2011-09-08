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
 * @package    MUtil
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TableSnippetAbstract.php 345 2011-07-28 08:39:24Z 175780 $
 */

/**
 * Outputs the data supplied through the $data or $repeater parameter 
 * in a simple standard Html table.
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class MUtil_Snippets_TableSnippetAbstract extends MUtil_Snippets_SnippetAbstract
{
    /**
     * Optional, instead of repeater array containing the data to show
     *
     * @var array Nested array
     */
    protected $data;

    /**
     * REQUIRED, but can be derived from $this->data
     *
     * @var MUtil_Lazy_RepeatableInterface
     */
    protected $repeater;

    /**
     * Add the columns to the table
     *
     * This is a default implementation, overrule at will
     *
     * @param MUtil_Html_TableElement $table
     */
    protected function addColumns(MUtil_Html_TableElement $table)
    {
        if ($this->data) {
            $row = reset($this->data);
        } else {
            $this->repeater->__start();
            $row = $this->repeater->__current();
        }

        foreach ($row as $name => $value) {
            $table->addColumn($this->repeater->$name, $name);
        }
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $table = new MUtil_Html_TableElement($this->repeater);

        $this->addColumns($table);
        
        return $table;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->data) {
            if (! $this->repeater) {
                $this->repeater = MUtil_Lazy::repeat($this->data);
            } else {
                // We do not know whether there is any link between
                // the data and the repeater, so do not use the data
                $this->data = null;
            }
        }

        return (boolean) $this->repeater;
    }
}
