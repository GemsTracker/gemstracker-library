<?php


/**
 * Outputs an array of arrays (or a \Zend_Db_Table_Rowset) as a table
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
class Gems_View_Helper_Excel extends \Zend_View_Helper_Abstract

{
    public function excel ($rowset = null)
    {
        try {
            //disable de ZFDebug class indien nodig.
            \Zend_Controller_Front::getInstance()->unregisterPlugin('ZFDebug_Controller_Plugin_Debug');
        } catch (\Exception $e) {
        }

        $this->view->layout()->setLayout('excel');

        if ($rowset instanceof \Gems_FormattedData) {
            $formatted = $rowset->getFormatted();
            $rowset->setFormatted(false);
        }
        $rowcnt = 0;
        foreach ($rowset as $row) {
            if ($row instanceof \Zend_Db_Table_Row) {
                $row = $row->toArray();
            }
            if (!is_array($row)) {
                $row = (array) $row;
            }
            if ($rowcnt == 0) {
                $headerRow = $row;
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
                if ($rowset instanceof \Gems_FormattedData) {
                    $rowset->setFormatted($formatted);
                }
            } else {
                $output .= "\t\t<tr>\r\n";
                // Make sure we repeat all header rows, even when no data present
                foreach ($headerRow as $key => $value) {
                    $value = isset($row[$key]) ? $row[$key] : null;
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