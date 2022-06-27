<?php

/**
 *
 * @package    Gems
 * @subpackage Form
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * CKEditor form element.
 *
 * Allows setting path to ckeditor files and setting
 * config variables. Default config is to have bbcode output and only a small
 * subset of available buttons on the toolbars.
 *
 * @package    Gems
 * @subpackage Form
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_Form_Element_CKEditor extends \Zend_Form_Element_Textarea {
    /**
     * Use formTextarea view helper by default
     * @var string
     */
    public $helper = 'formCKEditor';

    /**
     * The path to the public files of the CKEditor
     *
     * @var string
     */
    public $basePath = '';

    /**
     * Holds the config array for CKEditor
     *
     * @var array
     */
    public $config = array(
        'extraPlugins' => 'bbcode',
        'toolbar'      => array(
            array('Source', '-', 'Undo', 'Redo'),
            array('Find', 'Replace', '-', 'SelectAll', 'RemoveFormat'),
            array('Link', 'Unlink', 'Image', 'SpecialChar'),
            '/',
            array('Bold', 'Italic', 'Underline'),
            array('NumberedList', 'BulletedList', '-', 'Blockquote'),
            array('Maximize')
        )
    );

    /**
     * Bootstrap class for an input tag. Remove if you want the normal layout.
     * @var string
     */
    protected $_elementClass = 'form-control';

    /**
     * Constructor
     *
     * $spec may be:
     * - string: name of element
     * - array: options with which to configure element
     * - \Zend_Config: \Zend_Config with options for configuring element
     *
     * @param  string|array|\Zend_Config $spec
     * @param  array|\Zend_Config $options
     * @return void
     * @throws \Zend_Form_Exception if no element name after initialization
     */
    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);
        $this->addClass($this->_elementClass);
    }

    /**
     * Add a class to an existing class, taking care of spacing
     * @param string $targetClass  The existing class
     * @param string $addClass    the Class or classes to add, seperated by spaces
     */
    protected function addClass($addClass) {
        $targetClass = $this->getAttrib('class');
        if(!empty($targetClass) && (strpos($targetClass, $addClass) === false)) {
            $targetClass .= " {$addClass}";
        } else {
            $targetClass = $addClass;
        }
        $this->setAttrib('class', $targetClass);
        return $this;
    }

    public function init() {
        // If basepath not set, try a default
        if (empty($this->basePath)) {
            $this->setBasePath(GEMS_WEB_DIR . '/gems/ckeditor');
        }
    }

    /**
     * Load default decorators
     *
     * @return \Zend_Form_Element
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $htmlTagOptions = array(
                'tag' => 'div',
                'id'  => array('callback' => array(get_class($this), 'resolveElementId')),
        );
        $labelOptions = array();
        $htmlTagOptions['class'] = 'element-container';

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('ViewHelper')
                 ->addDecorator('Errors')
                 ->addDecorator('Description', array('tag' => 'p', 'class' => 'help-block'))
                 ->addDecorator('HtmlTag', $htmlTagOptions)
                 ->addDecorator('Label')
                 ->addDecorator('BootstrapRow');
        }
        return $this;
    }

    /**
     * Set the path to the public files of the CKEditor
     *
     * @param string $basePath
     * @return CKEditor_Form_CKEditor
     */
    public function setBasePath($basePath) {
        $basePath = (string) $basePath;
        if (file_exists($basePath . '/ckeditor.js')) {
            $this->basePath = $basePath;
        } else {
            throw new \Zend_Exception(sprintf('CKEditor.php not found at %s', $basePath));
        }

        return $this;
    }

    /**
     * Set the configuration for the CKEditor
     *
     * ARRAY
     * Use array as first parameter to set all items at once. This will
     * overwrite the existing config. Use false as second parameter to ADD
     * to the existing config
     *
     * STRING
     * or use a string to set items one by one, the second parameter is
     * the value
     *
     * @param string|array $key
     * @param mixed $value
     */
    public function setCKConfig($key, $value = null) {
        if (is_array($key)) {
            if (false === $value) {
                // Overwrite existing config
                $this->config = $key;
            } else {
                // Add to existing config
                foreach ($key as $idx => $value)
                {
                    $this->config[$idx] = $value;
                }
            }
        } else {
            // Set individual item, add to existing config
            $this->config[$key] = $value;
        }
    }

    /**
     * Set the view object
     *
     * Ensures that the view object has the CKEditor view helper path set.
     *
     * @param  \Zend_View_Interface $view
     * @return CKEditor_Form_CKEditor
     */
    public function setView(\Zend_View_Interface $view = null) {
        if (null !== $view) {
            if (false === $view->getPluginLoader('helper')->getPaths('CKEditor_View_Helper')) {
                $view->addHelperPath('CKEditor/View/Helper', 'CKEditor_View_Helper');
            }
        }

        if (!file_exists($this->basePath . '/ckeditor.js')) {
            throw new \Zend_Exception('Use setBasePath() to set the full path to the file ckeditor.php in the public folder of ckedit.');
        }

        return parent::setView($view);
    }
}
