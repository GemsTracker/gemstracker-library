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

    /**
     * Convert instance name to bindname
     *
     * Replaces underscores with slashes and adds the data element
     *
     * @param string $name
     * @return string
     */
    protected function _getBindName($name)
    {
        return '/data/' . $name;
    }

    /**
     * Add the changed/created by fields and add primary key
     *
     * @param string $tablePrefix
     * @return string
     */
    protected function _getFinalSql($tablePrefix)
    {
        $db = Zend_Registry::getInstance()->get('db');

        return $db->quoteIdentifier($tablePrefix . '_changed') . " timestamp NOT NULL,\n"
            . $db->quoteIdentifier($tablePrefix . '_changed_by') . " bigint(20) NOT NULL,\n"
            . $db->quoteIdentifier($tablePrefix . '_created') . " timestamp NOT NULL,\n"
            . $db->quoteIdentifier($tablePrefix . '_created_by') . " bigint(20) NOT NULL,\n"
            . 'PRIMARY KEY  (`' . $tablePrefix . '_id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
    }

    /**
     * If needed process the answer
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return array
     */
    protected function _processAnswer($key, $input, $type)
    {
        $output = array();

        if (array_key_exists($key, $input))
        {
            $value = $input[$key];
        } else {
            return $output;
        }

        switch ($type) {
            case 'dateTime':
                // A null value will sometimes be empty, causing errors in Zend_Date
                if (empty($value)) {$value = null;}
                $output[$key] = new Zend_Date($value, Zend_Date::ISO_8601);
                break;

            case 'select':
                $items = explode(' ', $value);
                foreach ($items as $idx => $answer) {
                    $multiName = $key . '_' . $answer;
                    $output[$multiName] = 1;
                }
                break;

            case 'geopoint':
                // Location split in 4 fields  latitude, longitude, altitude and accuracy.
                $items         = explode(' ', $value);
                if (count($items) == 4) {
                    $answers[$key . '_lat'] = $items[0];
                    $answers[$key . '_long'] = $items[1];
                    $answers[$key . '_alt'] = $items[2];
                    $answers[$key . '_acc'] = $items[3];
                }
                break;

            default:
                $output[$key] = $value;
                break;
        }

        return $output;
    }

    private function createTable()
    {
        $db          = Zend_Registry::getInstance()->get('db');
        $nested      = false;

        $mainTableName   = $this->getTableName();
        $mainTablePrefix = 'orf';
        $mainSql = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier($mainTableName) . ' ('
            . $db->quoteIdentifier($mainTablePrefix . '_id') . " bigint(20) NOT NULL auto_increment,\n";

        $relatedTablePrefix = 'orfr';
        $relatedTableName   = $this->getRelatedTableName();
        $relatedSql = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier($relatedTableName) . ' ('
            . $db->quoteIdentifier($relatedTablePrefix . '_id') . " bigint(20) NOT NULL auto_increment,\n"
            . $db->quoteIdentifier($relatedTablePrefix . '_response_id') . " bigint(20) NOT NULL,\n";

        foreach ($this->instance as $name => $element) {
            $sql = '';
            $bindName = $this->_getBindName($name);
            if (array_key_exists($bindName, $this->bind)) {
                $bindInfo = $this->bind[$bindName];
            } else {
                $bindInfo['type'] = 'string';
            }
            
            // Now convert $name including / to _
            $mysqlName = str_replace('/', '_', $name);

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
                        $multiName = $mysqlName . '_' . $key;
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
                    break;

                case 'decimal':
                    $field['type'] = 'float';
                    $field['size'] = '(12,4)';
                    break;

                case 'geopoint':
                    // Location split in 4 fields  latitude, longitude, altitude and accuracy.
                    $field['size'] = '(11,7)';
                    $field['type'] = 'decimal';
                    $items         = $this->body[$bindName]['item'];
                    $answers[$mysqlName . '_lat'] = $items[0];
                    $answers[$mysqlName . '_long'] = $items[1];
                    $answers[$mysqlName . '_alt'] = $items[2];
                    $answers[$mysqlName . '_acc'] = $items[3];
                    $sql .= "  " . $db->quoteIdentifier($mysqlName . '_lat') . " {$field['type']}{$field['size']} DEFAULT 0 NOT NULL,\n";
                    $sql .= "  " . $db->quoteIdentifier($mysqlName . '_long') . " {$field['type']}{$field['size']} DEFAULT 0 NOT NULL,\n";
                    $sql .= "  " . $db->quoteIdentifier($mysqlName . '_alt') . " int DEFAULT 0 NOT NULL,\n";
                    $sql .= "  " . $db->quoteIdentifier($mysqlName . '_acc') . " int DEFAULT 0 NOT NULL,\n";

                    //So we don't get an extra field
                    unset($field['type']);
                    break;

                default:
                    $field['type'] = 'varchar';
                    $field['size'] = 5;
                    $field['size'] = '(' . $field['size'] . ')';
            }

            if (isset($field['type'])) {
                $sql .= "  " . $db->quoteIdentifier($mysqlName) . " {$field['type']}{$field['size']} DEFAULT NULL,\n";
            }

            if (array_key_exists($bindName, $this->body) && array_key_exists('repeat', $this->body[$bindName])) { // CHECK NESTED
                $nested = true;
                $relatedSql .= $sql;
            } else {
                $mainSql .= $sql;
            }
        }

        $mainSql .= $this->_getFinalSql($mainTablePrefix);
        $db->query($mainSql);

        if ($nested) {
            $relatedSql .= $this->_getFinalSql($relatedTablePrefix);
            $db->query($relatedSql);
        }

        return new Gems_Model_JoinModel($this->getFormID(), $mainTableName, $mainTablePrefix);
    }

    private function flattenAnswers($xml, $parent = '')
    {
        $output = array();
        foreach ($xml as $name => $element) {
            if (!empty($parent)) {
                $elementName = $parent . '_' . $name;
            } else {
                $elementName = $name;
            }
            if (count($element->children()) > 0) {
                if ($this->getModel()->get($elementName, 'type') == Mutil_Model::TYPE_CHILD_MODEL) {
                    // Now do something :)
                    $output[$elementName][] = $this->flattenInstance($element, $elementName);
                } else {
                    $output = $output + $this->flattenInstance($element, $elementName);
                }
            } else {
                $output[$elementName] = (string) $element;
            }
        }
        return $output;
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
                if ($name == 'ref' || $name == 'nodeset' ) {
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
                    $rawItem['attribs']          = $element->attributes();
                    $result[$rawItem['context']] = $rawItem;
                    break;

                case 'item':
                    //has only value and label but repeated, add value/label pairs in array
                    $rawItem                           = $this->flattenBody($element->children(), $elementContext);
                    unset($rawItem['context']);
                    unset($rawItem['name']);
                    $result['item'][$rawItem['value']] = $rawItem['label'];
                    break;

                case 'repeat':
                case 'group':
                default:
                    unset($result['context']);
                    unset($result['name']);
                    $subarray = $this->flattenBody($element->children(), $elementContext);
                    unset($subarray['context']);
                    unset($subarray['label']);
                    unset($subarray['hint']);
                    unset($subarray['name']);

                    // If it is a repeat element, we need to do something special when data is coming in
                    if ($elementName == 'repeat') {
                        foreach($subarray as $key => &$info)
                        {
                            $info['repeat'] = substr($elementContext, 6);
                        }
                    }
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
                $elementName = $parent . '/' . $name;
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
                    foreach ($presets as $value) {
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
            foreach ($this->_xml->children('h', true)->head->children()->model->instance->children() as $element) {
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
            foreach ($this->_xml->children('h', true)->head->children()->model->instance->children() as $element) {
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
                //Failed, now create the table as it obviously doesn't exist
                $model = $this->createTable();
            }

            // Initially no repeated groups
            $nested = false;

            // Add submit date, this is the date the form was uploaded
            $model->set('orf_created', 'label', $this->translate->_('Date received'));

            //Now we have the table, let's add some multi options etc.
            $checkBox[1] = $this->translate->_('Checked');
            $checkBox[0] = $this->translate->_('Not checked');
            foreach ($this->instance as $name => $element) {
                $modelToUse = $model;
                $bindName = $this->_getBindName($name);
                if (array_key_exists($bindName, $this->bind)) {
                    $bindInfo = $this->bind[$bindName];
                } else {
                    $bindInfo['type'] = 'string';
                }
                
                // Now convert $name including / to _
                $modelName = str_replace('/', '_', $name);

                if (array_key_exists($bindName, $this->body) && array_key_exists('repeat', $this->body[$bindName])) { // CHECK NESTED
                    if ($nested === false) {
                        $nested = true;
                        $nestedName = $this->body[$bindName]['repeat'];
                        $relatedModel = new Gems_Model_JoinModel($nestedName, $this->getRelatedTableName(), 'orfr');
                        
                        // Add some meta information to make export a little easier
                        $model->setMeta('nested', true);
                        $model->setMeta('nestedName', $nestedName);
                    }
                    $modelToUse = $relatedModel;
                }

                switch ($bindInfo['type']) {
                    case 'date':
                    case 'dateTime':
                        $label = $modelName;

                        // Now check some special fields
                        if (array_key_exists('jr', $bindInfo)) {
                            $keys = array('preload', 'preloadParams');
                            $found = array_intersect_key($bindInfo['jr'], array_flip($keys));

                            if (count($found) == count($keys) && $found['preload'] == 'timestamp') {
                                if ($found['preloadParams'] == 'start') {
                                    $label = $this->translate->_('Start date');
                                    $modelToUse->setMeta('start', $modelName);
                                } elseif ($found['preloadParams'] == 'end') {
                                    $label = $this->translate->_('Completion date');
                                    $modelToUse->setMeta('end', $modelName);
                                }
                            }
                        }

                        if (array_key_exists($bindName, $this->body)) {
                            if (array_key_exists('label', $this->body[$bindName])) {
                                $label = $this->body[$bindName]['label'];
                                if (array_key_exists('hint', $this->body[$bindName])) {
                                    $label = sprintf('%s (%s)', $label, $this->body[$bindName]['hint']);
                                }
                            }
                        }
                        $modelToUse->set($modelName, 'label', $label);
                        break;


                    case 'select':
                        //A multi select
                        $items         = $this->body[$bindName]['item'];
                        foreach ($items as $key => $value) {
                            $multiName = $modelName . '_' . $key;
                            $label     = sprintf('%s [%s]', $this->body[$bindName]['label'], $value);
                            $modelToUse->set($multiName, 'multiOptions', $checkBox, 'label', $label);
                        }
                        break;

                    case 'geopoint':
                        // Location split in 4 fields  latitude, longitude, altitude and accuracy.
                        $label = $this->body[$bindName]['label'];
                        $modelToUse->set($modelName . '_lat', 'label', $label . ' [latitude]');
                        $modelToUse->set($modelName . '_long', 'label', $label . ' [longitude]');
                        $modelToUse->set($modelName . '_alt', 'label', $label . ' [altitude]');
                        $modelToUse->set($modelName . '_acc', 'label', $label . ' [accuracy]');
                        break;

                    case 'select1':
                        $items         = $this->body[$bindName]['item'];
                        $modelToUse->set($modelName, 'multiOptions', $items);

                    case 'string':
                        // Now determine mediatype
                        if (array_key_exists($bindName, $this->body)) {
                            $bodyElement = $this->body[$bindName];
                            if (isset($bodyElement['name']) && $bodyElement['name'] == 'upload') {
                                $mediaType = (string) $bodyElement['attribs']->mediatype;
                                if (substr($mediaType, 0, 5) == 'image') {
                                    $modelToUse->setOnLoad($modelName, array($this,'formatImg'));
                                }
                            }
                        }

                    default:
                        $label = null;
                        if (array_key_exists($bindName, $this->body)) {
                            if (array_key_exists('label', $this->body[$bindName])) {
                                $label = $this->body[$bindName]['label'];
                                if (array_key_exists('hint', $this->body[$bindName])) {
                                    $label = sprintf('%s (%s)', $label, $this->body[$bindName]['hint']);
                                }
                                $modelToUse->set($modelName, 'label', $label);
                            }
                        }
                        break;
                }
            }
            if ($nested) {
                $model->addModel($relatedModel, array('orf_id' => 'orfr_response_id'));
            }
            $this->model = $model;
        }

        return $this->model;
    }

    /**
     * Get the table name for the repeating group in a form
     *
     * @return string
     */
    public function getRelatedTableName()
    {
        return $this->getTableName() . '_related';
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

        $model   = $this->getModel();
        $answers = $this->flattenAnswers($xml);

        //Now we should parse the response, extract the options given for a (multi)select
        $output = array();
        foreach ($this->instance as $name => $element) {
                $bindName = $this->_getBindName($name);
                if (array_key_exists($bindName, $this->bind)) {
                    $bindInfo = $this->bind[$bindName];
                } else {
                    $bindInfo['type'] = 'string';
                }

                if (array_key_exists($bindName, $this->body) && array_key_exists('repeat', $this->body[$bindName])) { // CHECK NESTED
                    // We found a field that should go into the nested record
                    // Now process all answers
                    $group = $this->body[$bindName]['repeat'];
                    foreach($answers[$group] as $idx => $element)
                    {
                        if (!array_key_exists($group, $output)) {
                            $output[$group] = array();
                        }
                        if (!array_key_exists($idx, $output[$group])) {
                            $output[$group][$idx] = array();
                        }
                        $output[$group][$idx] = $output[$group][$idx] + $this->_processAnswer($name, $element, $bindInfo['type']);
                    }
                } else {
                    $output = $output + $this->_processAnswer($name, $answers, $bindInfo['type']);
                }
        }

        $output['orf_id'] = null;
        $answers = $model->save($output);

        if ($model->getChanged() && $remove) {
            $log     = Gems_Log::getLogger();
            $log->log($file .  '-->' .  substr($file, 0, -4) . '.bak', Zend_Log::ERR);
            rename($file, substr($file, 0, -4) . '.bak');
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

    public function formatImg($value, $new, $name, array $context = array())
    {
        // TODO: Find a way to build an url that identifies the form so we can download the attachted image
        //       and still check if one is logged in
        if (!empty($value)) {
            return MUtil_Html_ImgElement::img(array(
                        'src' => array(
                            'controller' => 'openrosa',
                            'action'     => 'image',
                            'id'         => $this->formID,
                            'version'    => $this->formVersion,
                            'resp'       => $context['orf_id'],
                            'field'      => str_replace('.', '_', $value)
                            ),
                        'width' => '350px;'
                ));
        } else {
            return $value;
        }
    }
}