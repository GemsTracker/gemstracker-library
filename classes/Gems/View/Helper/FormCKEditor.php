<?php
/**
 *
 * @package    Gems
 * @subpackage View\Helper
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * CKEditor view helper. Handles rendering the CKEditor element
 *
 * @package    Gems
 * @subpackage View\Helper
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_View_Helper_FormCKEditor extends \Zend_View_Helper_FormTextarea
{
    public $config = array();

    /**
     * Default basedir for CKEditor. Can be overwritten through the Decorator options.
     * @var string
     */
    protected $_basedir = 'gems/ckeditor';

    protected $_options = array();

    public function __construct($options = null)
    {
        //\MUtil_Echo::track('test');
        // If basepath not set, try a default
        if ($options) {
            if (is_array($options)) {
                $this->_options = $options;
            } else {
                $this->_options[] = $options;
            }
            if (isset($this->_options['baseDir'])) {
                $this->_basedir = $this->_options['baseDir'];
            }
        }
    }

    public function formCKEditor($name = null, $value = null, $attribs = null, $options = null) {
        // Remove from attribs what we need to have in options
        foreach ($attribs as $key => $val) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($val);
                unset($attribs[$key]);
            }
        }

        $baseUrl = \GemsEscort::getInstance()->basepath->getBasePath() . '/';

        $this->view->headScript()->appendFile($baseUrl . $this->_basedir . '/' . 'ckeditor.js');
        $this->view->headScript()->prependScript("
            CKEditorConfig = ".\Zend_Json::encode($this->config).";
            ");

        //\MUtil_Echo::track($baseUrl);
        //\MUtil_Echo::track('test');
        //
        //$markup = sprintf($this->_format, $id, $name, $value);

        $output = $this->formTextarea($name, $value, $attribs); // Get regular textarea output
        $output = sprintf('<div class="ckeditor">%s</div>', $output); // Wrap in a div

        $id = $name;

        $this->view->inlineScript()->appendScript("
            if (typeof CKEditorConfig === 'undefined') {
                CKEditorConfig = {};
            }
            CKEDITOR.replace( '{$id}', CKEditorConfig );
        ");

        return $output;
    }

    public function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    public function setConfig($config) {
        $this->config = $config;
    }
}