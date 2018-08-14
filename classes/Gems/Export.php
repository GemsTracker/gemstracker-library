<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Export extends \Gems_Loader_TargetLoaderAbstract
{
    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Export';

    /**
     * Is set to the calling controller to allow rendering the view
     *
     * @var \Gems_Controller_Action
     */
    public $controller = null;

    /**
     * This variable holds all registered export classes, may be changed in derived classes
     *
     * @var array Of classname => description
     */
    protected $_exportClasses = array(
        'StreamingExcelExport' => 'Excel (xlsx)',
        'SpssExport' => 'SPSS',
        'CsvExport' => 'CSV',
        'StreamingStataExport' => 'Stata (xml)',
    );

    /**
     * Holds all registered export descriptions, which describe the models that can be exported
     * @var array of classnames of descriptions
     */
    protected $_exportModelSources = array(
        'AnswerExportModelSource' => 'Answers',
    );

    /**
     * The default values for the form. Defaults for a specific export-type should come
     * from that class
     *
     * @var array
     */
    protected $_defaults = array(
        'exportmodelsource' => 'AnswerExportModelSource',
        'type' => 'StreamingExcelExport'
    );

    /**
     *
     * @param type $container A container acting as source fro \MUtil_Registry_Source
     * @param array $dirs The directories where to look for requested classes
     */
    public function __construct($container, array $dirs)
    {
        parent::__construct($container, $dirs);

        // Make sure the export is known
        $this->addRegistryContainer(array('export' => $this));
    }

    /**
     * Add one or more export classes
     *
     * @param array $stack classname / description array of sourceclasses
     */
    public function addExportClasses($stack)
    {
        $this->_exportClasses = array_merge($this->_exportClasses, $stack);
    }

    public function getDefaults()
    {
        return $this->_defaults;
    }

    /**
     *
     * @return \Gems\Export\ExportInterface
     */
    public function getExport($type)
    {
        return $this->_getClass($type);
    }

    /**
     * Returns all registered export classes
     *
     * @return array Of classname => description
     */
    public function getExportClasses()
    {
        return $this->_exportClasses;
    }

    /**
     * Returns all registered export models
     *
     * @return array Of classnames
     */
    public function getExportModelSources()
    {
        return $this->_exportModelSources;
    }

    /**
     * Set the default options for the form
     *
     * @param array $defaults
     */
    public function setDefaults($defaults)
    {
        $this->_defaults = $defaults;
    }
}