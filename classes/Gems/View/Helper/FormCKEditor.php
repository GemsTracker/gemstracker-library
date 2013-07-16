<?php
/**
 * CKEditor view helper. Handles rendering the CKEditor element
 *
 * @package CKEditor
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class Gems_View_Helper_FormCKEditor extends Zend_View_Helper_FormTextarea{
    public $config = array();
    public $basePath = '';

    public function formCKEditor($name = null, $value = null, $attribs = null, $options = null) {
        // Remove from attribs what we need to have in options
        foreach ($attribs as $key => $val) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($val);
                unset($attribs[$key]);
            }
        }

        include_once $this->basePath . '/ckeditor.php'; // Now make sure the CKEditor class can be loaded
        
        // Now set the location to the js/css
        $baseUrl = $this->view->serverUrl() . GemsEscort::getInstance()->basepath->getBasePath();
        $CKEditor = new CKEditor($baseUrl . '/gems/ckeditor/');
        $CKEditor->returnOutput = true; // We capture the output

        $output = $this->formTextarea($name, $value, $attribs); // Get regular textarea output
        $output = sprintf('<div class="ckeditor">%s</div>', $output); // Wrap in a div
        $output .= $CKEditor->replace($attribs['id'], $this->config); // And add init code for CKEditor

        return $output;
    }
    
    public function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    public function setConfig($config) {
        $this->config = $config;
    }
}