<?php
/**
 * CKEditor form element. Allows setting path to ckeditor files and setting 
 * config variables. Default config is to have bbcode output and only a small
 * subset of available buttons on the toolbars.
 * 
 * @package CKEditor
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class Gems_Form_Element_CKEditor extends Zend_Form_Element_Textarea {
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
        'toolbar' => array(
			array('Source','-','Undo','Redo'),
			array('Find','Replace','-','SelectAll','RemoveFormat'),
			array('Link', 'Unlink', 'Image', 'SpecialChar'),
			'/',
			array('Bold', 'Italic','Underline'),
			array('NumberedList','BulletedList','-','Blockquote'),
			array('Maximize')
            )
        );
    
    public function init() {
        // If basepath not set, try a default
        if (empty($this->basePath)) {
            $this->setBasePath(GEMS_WEB_DIR . '/gems/ckeditor');
        }
    }
            
      
    /**
     * Set the path to the public files of the CKEditor
     * 
     * @param string $basePath
     * @return CKEditor_Form_CKEditor
     */
    public function setBasePath($basePath) {
        $basePath = (string) $basePath;
        if (file_exists($basePath . '/ckeditor.php')) {
            $this->basePath = $basePath;
        } else {
            throw new Zend_Exception(sprintf('CKEditor.php not found at %s', $basePath));
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
                foreach ($key as $idx => $value) {
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
     * @param  Zend_View_Interface $view
     * @return CKEditor_Form_CKEditor
     */
    public function setView(Zend_View_Interface $view = null)
    {
        if (null !== $view) {
            if (false === $view->getPluginLoader('helper')->getPaths('CKEditor_View_Helper')) {
                $view->addHelperPath('CKEditor/View/Helper', 'CKEditor_View_Helper');
            }
        }
        
        if (!file_exists($this->basePath . '/ckeditor.php')) {
            throw new Zend_Exception('Use setBasePath() to set the full path to the file ckeditor.php in the public folder of ckedit.');
        }
        
        return parent::setView($view);
    }
}