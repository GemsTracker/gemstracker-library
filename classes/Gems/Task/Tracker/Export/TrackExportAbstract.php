<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Export;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Field\FieldInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 14, 2016 2:17:09 PM
 */
abstract class TrackExportAbstract extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var file resource
     */
    private $_file;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Write the array keys to the output
     *
     * @param array $data With headers as the keys
     */
    protected function exportFieldHeaders(array $data)
    {
        fwrite($this->_file, implode("\t", array_keys($data)) . "\r\n");
    }

    /**
     * Write the array to the output to file
     *
     * @param array $data
     */
    protected function exportFieldData(array $data)
    {
        $replacements = array("\n" => '\\n', "\r" => '\\r', "\t" => '\\t');

        foreach ($data as &$item) {
            $item = strtr((string) $item, $replacements);
        }
        fwrite($this->_file, implode("\t", $data) . "\r\n");
    }

    /**
     * Flush the output
     */
    protected function exportFlush()
    {
        fflush($this->_file);
    }

    /**
     * Write the export type to the output
     *
     * @param array $data With headers as the keys
     */
    protected function exportTypeHeader($header, $prependNewline = true)
    {
        if ($prependNewline) {
            fwrite($this->_file, "\r\n");
        }
        fwrite($this->_file, "$header\r\n");
    }

    /**
     * Sets the batch this task belongs to
     *
     * This method will be called from the \Gems\Task\TaskRunnerBatch upon execution of the
     * task. It allows the task to communicate with the batch queue.
     *
     * @param \MUtil\Task\TaskBatch $batch
     * @return \MUtil\Task\TaskInterface (continuation pattern)
     */
    public function setBatch(\MUtil\Task\TaskBatch $batch)
    {
        parent::setBatch($batch);

        $this->_file = $batch->getVariable('file');

        return $this;
    }

    /**
     * Translate a field code to the field order number
     *
     * @param FieldsDefinition $fields
     * @param string $fieldId
     * @return string {order} or original value
     */
    protected function translateFieldCode(FieldsDefinition $fields, $fieldId)
    {
        $field = $fields->getField($fieldId);
        if ($field instanceof FieldInterface) {
            return  '{f' . $field->getOrder() . '}';
        }

        return $fieldId;
    }
}
