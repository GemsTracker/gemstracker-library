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
 * @author     Michiel Rooks <info@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace MUtil\Snippets\Standard;

/**
 * Extends the TableSnippet so we can select which fields from the data we want to show
 * and what labels to use instead of using the column name as a label
 *
 * Usage:
 * $this->setDisplayColumns(array('id_reception_code'=>$this->_('Reception code'),
 *                                'lastname'         =>$this->_('Lastname'));
 *
 * Or set the $columns snippet parameter.
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.3
 */
class SelectiveTableSnippet extends \MUtil_Snippets_TableSnippetAbstract
{
    /**
     *
     * @var array of name => label columns
     */
    protected $columns = array();

    /**
     * Add the columns to the table
     *
     * @param \MUtil_Html_TableElement $table
     */
    protected function addColumns(\MUtil_Html_TableElement $table)
    {
        foreach ($this->columns as $name => $label) {
            $table->addColumn($this->repeater->$name, $label);
        }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        return parent::hasHtmlOutput() && $this->columns;
    }

    /**
     * Set the columns to display with their labels
     *
     * @param array $columns name => label columns
     */
    public function setDisplayColumns(array $columns) {
        $this->columns = $columns;
    }
}