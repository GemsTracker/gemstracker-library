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
 * Outputs an array of arrays (or a Zend_Db_Table_Rowset) as a table
 *
 * The first 'record' is rendered bold, being the header for the table
 *
 * @category    Gems
 * @filesource
 * @package     Gems
 * @subpackage  View_Helper
 * @author      175780
 * @version     $Id$
 */
class Gems_View_Helper_Excel extends Zend_View_Helper_Abstract

{
    public function excel ($rowset = null)
    {
        try {
            //disable de ZFDebug class indien nodig.
            Zend_Controller_Front::getInstance()->unregisterPlugin('ZFDebug_Controller_Plugin_Debug');
        } catch (Exception $e) {}
        $this->view->layout()->setLayout('excel');
        if ($rowset instanceof Gems_FormattedData) {
            $rowset->setFormatted(false);
        }
        $rowcnt = 0;
        foreach ($rowset as $row) {
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
            }
            if (!is_array($row)) {
                $row = (array) $row;
            }
            if ($rowcnt == 0) {
                //Only for the first row: output headers
                $output = "<table>\r\n";
                $output .= "\t<thead>\r\n";
                $output .= "\t\t<tr>\r\n";
                foreach ($row as $name => $value) {
                    $output .= "\t\t\t<th>$value</th>\r\n";
                }
                $output .= "\t\t</tr>\r\n";
                $output .= "\t</thead>\r\n";
                $output .= "\t<tbody>\r\n";
                if ($rowset instanceof Gems_FormattedData) {
                    $rowset->setFormatted(true);
                }
            } else {
                $output .= "\t\t<tr>\r\n";
                foreach ($row as $name => $value) {
                    $output .= "\t\t\t<td>$value</td>\r\n";
                }
                $output .= "\t\t</tr>\r\n";
            }
            $rowcnt++;
        }
        if (isset($output)) {
            $output .= "\t</tbody>\r\n";
            $output .= "</table>\r\n";
            return $output;
        } else {
            return null;
        }
    }
}