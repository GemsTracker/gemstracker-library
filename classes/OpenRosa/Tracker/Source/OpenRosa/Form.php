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
 * Helper for OpenRosa forms
 *
 * It supports a subset of OpenRosa forms and provides a bridge between GemsTracker
 * models and the xml-formdefinition.
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 * Helper for OpenRosa forms
 *
 * It supports a subset of OpenRosa forms and provides a bridge between GemsTracker
 * models and the xml-formdefinition.
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 * @deprecated Class deprecated since version 2.0
 */
class OpenRosa_Tracker_Source_OpenRosa_Form
{
    /**
     * @var Gems_Model_JoinModel 
     */
    private $model;
    private $bind;
    private $instance;
    private $body;
    private $deviceIdField;
    private $formID;
    private $formVersion;
    private $title;

    /**
     * @var SimpleXmlElement
     */
    private $_xml;

    /**
     * Create an OpenRosaForm from an existing filename
     *
     * @param string $file the sanitized filename (absolute path)
     */
    public function __construct($file)
    {
        $this->translate = Zend_Registry::getInstance()->get('Zend_Translate');
        if (!file_exists($file)) {
            throw new Gems_Exception_Coding(sprintf($this->translate->_('File not found: %s'), $file));
        }

        //We read the xml file
        $xml = simplexml_load_file($file);
        if ($xml === false) {
            throw new Gems_Exception_Coding(sprintf($this->translate->_('Could not read form definition for form %s'), $file));
        }
        $this->_xml = $xml;

        //For working with the namespaces:
        //$xml->children('h', true)->head->children()->model->bind
        //use namespace h children for the root element, and find h:head, then use no namespace children
        //and find model->bind so we get h:head/model/bind elements
        $this->bind     = $this->flattenBind($this->_xml->children('h', true)->head->children()->model->bind);
        $this->body     = $this->flattenBody($xml->children('h', true)->body->children(), $context        = '');
        $this->instance = $this->flattenInstance($this->_xml->children('h', true)->head->children()->model->instance->data->children());
    }

    private function createTable()
    {
        $tableName   = $this->getTableName();
        $tablePrefix = 'orf';
        $db          = Zend_Registry::getInstance()->get('db');

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier($tableName) . ' ('
            . $db->quoteIdentifier($tablePrefix . '_id') . " bigint(20) NOT NULL auto_increment,\n";

        foreach ($this->instance as $name => $element) {
            $bindName = str_replace('_', '/', '_data_' . $name);
            if (array_key_exists($bindName, $this->bind)) {
                $bindInfo = $this->bind[$bindName];    
            } else {
                $bindInfo['type'] = 'string';
            }

            $field = array();
            switch ($bindInfo['type']) {
                case 'date':
                case 'dateTime':
                    $field['type'] = 'datetime';
                    $field['size'] = '';
                    break;

                case 'barcode':
                    // The token field
                    $field['size'] = 32;
                    $field['type'] = 'varchar';
                    $field['size'] = '(' . $field['size'] . ')';
                    
                case 'string':
                    // Always make it text
                    $field['type'] = 'text';
                    $field['size'] = '';
                    break;

                case 'select':
                    //A multi select
                    $field['size'] = '(1)';
                    $field['type'] = 'int';
                    $items         = $this->body[$bindName]['item'];
                    foreach ($items as $key => $value) {
                        $multiName = $name . '_' . $key;
                        $sql .= "  " . $db->quoteIdentifier($multiName) . " {$field['type']}{$field['size']} DEFAULT 0 NOT NULL,\n";
                    }
                    //So we don't get an extra field
                    unset($field['type']);
                    break;

                case 'select1':
                    //Select one, size can be as a small as largest answeroption
                    $items         = $this->body[$bindName]['item'];
                    $field['size'] = 1;
                    foreach ($items as $key => $value) {
                        if (strlen($key) > $field['size']) {
                            $field['size'] = strlen($key);
                        }
                    }
                    $field['type'] = 'varchar';
                    $field['size'] = '(' . $field['size'] . ')';
                    break;

                case 'int':
                    $field['type'] = 'bigint';
                    $field['size'] = '(20)';

                case 'decimal':
                    $field['type'] = 'float';
                    $field['size'] = '';

                default:
                    $field['type'] = 'varchar';
                    $field['size'] = 5;
                    $field['size'] = '(' . $field['size'] . ')';
            }

            if (isset($field['type'])) {
                $sql .= "  " . $db->quoteIdentifier($name) . " {$field['type']}{$field['size']} DEFAULT NULL,\n";
            }
        }

        $sql .= $db->quoteIdentifier($tablePrefix . '_changed') . " timestamp NOT NULL,\n"
            . $db->quoteIdentifier($tablePrefix . '_changed_by') . " bigint(20) NOT NULL,\n"
            . $db->quoteIdentifier($tablePrefix . '_created') . " timestamp NOT NULL,\n"
            . $db->quoteIdentifier($tablePrefix . '_created_by') . " bigint(20) NOT NULL,\n"
            . 'PRIMARY KEY  (`' . $tablePrefix . '_id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';

        $db->query($sql);

        return new Gems_Model_JoinModel($this->getFormID(), $tableName, $tablePrefix);
    }

    private function flattenBind($xml)
    {
        foreach ($xml as $name => $element) {
            $attributes = array();
            foreach ($element->attributes() as $name => $value) {
                $attributes[$name] = (string) $value;
                if ($name == 'nodeset') {
                    $ref = (string) $value;
                }
            }
            foreach ($element->attributes('jr', true) as $name => $value) {
                $attributes['jr'] [$name] = (string) $value;
            }
            $output[$ref]             = $attributes;
        }

        return $output;
    }

    /**
     * Return flattend element
     *
     * @param SimpleXMLElement $xml
     * @param type $context
     */
    private function flattenBody($xml, $context = '')
    {
        foreach ($xml as $elementName => $element) {
            //Check ref first
            $elementContext = $context;
            foreach ($element->attributes() as $name => $value) {
                if ($name == 'ref') {
                    if (!empty($elementContext)) {
                        $elementContext .= '/';
                    } else {
                        $elementContext = '/data/';
                    }
                    if (substr($value, 0, 1) == '/') {
                        $elementContext    = '';
                    }
                    $elementContext .= $value;
                    break;
                }
            }
            $result['context'] = $elementContext;
            $result['name']    = $element->getName();

            //elementName can be label, hint, or a sub element (group, input, select, select1
            switch ($elementName) {
                case 'label':
                    $result['label'] = (string) $element;
                    break;

                case 'hint':
                    $result['hint'] = (string) $element;
                    break;

                case 'value':
                    $result['value'] = (string) $element;
                    break;

                case 'trigger':
                case 'upload':
                case 'input':
                case 'select':
                case 'select1':
                    //has only value and label but repeated, add value/label pairs in array
                    $rawItem                     = $this->flattenBody($element, $elementContext);
                    $rawItem['context']          = $elementContext;
                    $rawItem['name']             = $element->getName();
                    $result[$rawItem['context']] = $rawItem;
                    break;

                case 'item':
                    //has only value and label but repeated, add value/label pairs in array
                    $rawItem                           = $this->flattenBody($element->children(), $elementContext);
                    unset($rawItem['context']);
                    unset($rawItem['name']);
                    $result['item'][$rawItem['value']] = $rawItem['label'];
                    break;

                case 'group':
                default:
                    unset($result['context']);
                    unset($result['name']);
                    $subarray = $this->flattenBody($element->children(), $elementContext);
                    unset($subarray['context']);
                    unset($subarray['label']);
                    unset($subarray['hint']);
                    unset($subarray['name']);
                    $result   = $result + $subarray;
                    break;
            }
        }

        return $result;
    }

    private function flattenInstance($xml, $parent = '')
    {
        $output = array();
        foreach ($xml as $name => $element) {
            if (!empty($parent)) {
                $elementName = $parent . '_' . $name;
            } else {
                $elementName = $name;
            }
            if (count($element->children()) > 0) {
                $output = $output + $this->flattenInstance($element, $elementName);
            } else {
                $output[$elementName] = (string) $element;
            }
        }
        return $output;
    }

    /**
     * Returns what field (path) contains the attributes jr:preload="property" jr:preloadParams="deviceid"
     * from the moden -> bind elements
     *
     * @return string
     */
    public function getDeviceIdField()
    {
        if (empty($this->deviceIdField)) {
            foreach ($this->_xml->children('h', true)->head->children()->model->bind as $bind) {
                if ($presets = $bind->attributes('jr', true)) {
                    foreach ($presets as $key => $value) {
                        if ($value == 'deviceid') {
                            $this->deviceIdField = $bind->attributes()->nodeset;
                            break;
                        }
                    }
                }
            }
        }

        return $this->deviceIdField;
    }

    /**
     * Returns the formID from the instance element id attribute
     *
     * @return string
     */
    public function getFormID()
    {
        if (empty($this->formID)) {
            foreach ($this->_xml->children('h', true)->head->children()->model->instance->children() as $name => $element) {
                if (!empty($element->attributes()->id)) {
                    $this->formID = $element->attributes()->id;
                    break;
                }
            }
        }

        return $this->formID;
    }
    
    /**
     * Returns the formVersion from the instance element version attribute
     *
     * @return string
     */
    public function getFormVersion()
    {
        if (empty($this->formVersion)) {
            foreach ($this->_xml->children('h', true)->head->children()->model->instance->children() as $name => $element) {
                if (!empty($element->attributes()->version)) {
                    $this->formVersion = $element->attributes()->version;
                    break;
                }
            }
        }

        return $this->formVersion;

    }

    /**
     * @return Gems_Model_JoinModel
     */
    public function getModel()
    {
        if (empty($this->model)) {
            try {
                $model = new Gems_Model_JoinModel($this->getFormID(), $this->getTableName(), 'orf');
            } catch (Exception $exc) {
                //Failed, now create the table as it obviously doesn't exists
                $model = $this->createTable();
            }

            //Now we have the table, let's add some multi options etc.
            $checkBox[1] = $this->translate->_('Checked');
            $checkbox[0]  = $this->translate->_('Not checked');
            foreach ($this->instance as $name => $element) {
                $bindName = str_replace('_', '/', '_data_' . $name);
                if (array_key_exists($bindName, $this->bind)) {
                    $bindInfo = $this->bind[$bindName];    
                } else {
                    $bindInfo['type'] = 'string';
                }
                

                switch ($bindInfo['type']) {
                    case 'select':
                        //A multi select
                        $items         = $this->body[$bindName]['item'];
                        foreach ($items as $key => $value) {
                            $multiName = $name . '_' . $key;
                            $label     = sprintf('%s [%s]', $this->body[$bindName]['label'], $value);
                            $model->set($multiName, 'multiOptions', $checkBox, 'label', $label);
                        }
                        break;

                    case 'select1':
                        $items         = $this->body[$bindName]['item'];
                        $model->set($name, 'multiOptions', $items);
                    default:
                        $label = null;
                        if (array_key_exists($bindName, $this->body)) {
                            if (array_key_exists('label', $this->body[$bindName])) {
                                $label = $this->body[$bindName]['label'];
                                if (array_key_exists('hint', $this->body[$bindName])) {
                                    $label = sprintf('%s (%s)', $label, $this->body[$bindName]['hint']);
                                }
                                $model->set($name, 'label', $label);
                            }
                        }
                        break;
                }
            }
            $this->model = $model;
        }
        
        return $this->model;
    }

    public function getTableName()
    {
        $tableName = str_replace('.', '_', 'gems__orf__' . $this->getFormID() . '_' . $this->getFormVersion());

        return $tableName;
    }

    /**
     * Returns the form title from the h:title element
     *
     * @return string
     */
    public function getTitle()
    {
        if (empty($this->title)) {
            $this->title = $this->_xml->children('h', true)->head->children('h', true)->title;
        }

        return $this->title;
    }

    public function saveAnswer($file, $remove = true)
    {
        if (!file_exists($file)) {
            throw new Gems_Exception_Coding(sprintf($this->translate->_('File not found: %s'), $file));
        }

        //We read the xml file
        $xml = simplexml_load_file($file);
        if ($xml === false) {
            throw new Gems_Exception_Coding(sprintf($this->translate->_('Could not read form definition for form %s'), $file));
        }

        $formId = (string) $xml->attributes()->id;
        if ($formId != $this->getFormID()) {
            //Can not save to this object as it is a different form!
            throw new Gems_Exception_Coding(sprintf($this->translate->_('Response is for a different formId: %s <-> %s'), $formId, $this->getFormID()));
        }
        
        $answers = $this->flattenInstance($xml);
        //Now we should parse the response, extract the options given for a (multi)select
        foreach ($this->instance as $name => $element) {
                $bindName = str_replace('_', '/', '_data_' . $name);
                if (array_key_exists($bindName, $this->bind)) {
                    $bindInfo = $this->bind[$bindName];    
                } else {
                    $bindInfo['type'] = 'string';
                }

                if ($bindInfo['type'] == 'dateTime') {
                    $answers[$name] = new Zend_Date($answers[$name], Zend_Date::ISO_8601);
                }
                if ($bindInfo['type'] == 'select') {
                        //A multi select
                        $items         = explode(' ', $answers[$name]);
                        foreach ($items as $idx => $key) {
                            $multiName = $name . '_' . $key;
                            $answers[$multiName] = 1;
                        }
                        unset($answers[$name]);
                }
        }

        $answers['orf_id'] = null;
        $model = $this->getModel();
        $answers = $model->save($answers);
        if ($model->getChanged() && $remove) {
            $log     = Gems_Log::getLogger();
            $log->log($file .  '-->' .  substr($file, 0, -3) . 'bak', Zend_Log::ERR);
            rename($file, substr($file, 0, -3) . 'bak');
        }
        // @@TODO: make hook for respondentID lookup too
        if (isset($answers['token'])) {
            // We receveid a form linked to a token, signal the 'inSource' for this token.
            $loader = GemsEscort::getInstance()->getLoader();
            $token = $loader->getTracker()->getToken($answers['token']);
            $token->getUrl($loader->getCurrentUser()->getLocale(), $loader->getCurrentUser()->getUserId());
        }

        return $answers;
    }
}