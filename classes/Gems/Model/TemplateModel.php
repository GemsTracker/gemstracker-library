<?php
/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4
 */
class TemplateModel extends \MUtil\Model\ArrayModelAbstract {

    protected $_modelFields = array('name', 'path');
    protected $_templates = array();
    protected $_template = '';
    protected $_templateData = array();
    protected $_path = '';

    /**
     * @var \Zend_Translate_Adapter
     */
    protected $translate;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $_project;

    protected $_saveable = true;

    /**
     *
     * @param string $modelName
     * @param \Gems\Project\ProjectSettings $project
     */
    public function __construct($modelName, $project)
    {
        $this->_project = $project;
        parent::__construct($modelName);

        $this->set('name', 'label', $this->_('Name'), 'elementClass', 'Hidden');
        $this->set('path');
        $this->setKeys(array('name'));
    }

    /**
     * proxy for easy access to translations
     *
     * @param  string             $messageId Translation string
     * @param  string|\Zend_Locale $locale    (optional) Locale/Language to use, identical with locale
     *                                       identifier, @see \Zend_Locale for more information
     * @return string
     */
    private function _($messageId, $locale = null)
    {
        if ($this->translate) {
            return $this->translate->_($messageId, $locale);
        }

        return $messageId;
    }

    protected function _load(array $filter, array $sort)
    {
        $data = parent::_load($filter, $sort);

        if ($filter && count($data) == 1) {
            $template = reset($data);
            // Loaded one item, now set this to be the current template
            $this->setTemplate($template['name'], $template['path'], $data);
        }

        return $data;
    }

    protected function _loadAllTraversable()
    {
        if (!$this->_templates) {
            $templates = array();
            $css = $this->_project->css;

            foreach ($css as $info) {
                if (is_array($info)) {
                    if (array_key_exists('url', $info)) {
                        $url = $info['url'];
                    } else {
                        $url = '';
                    }
                } else {
                    $url = $info;
                }

                if (strpos($url, '/')) {
                    $urlParts = explode('/', $url);
                    if (!array_key_exists($urlParts[0], $templates)) {
                        $templates[$urlParts[0]] = array(
                            'name' => $urlParts[0],
                            'path' => GEMS_WEB_DIR . '/' . $urlParts[0]
                        );
                    }

                    $templates[$urlParts[0]]['sheets'][] = $url;
                }
            }
            $this->_templates = $templates;
        }

        return $this->_templates;
    }

    /**
     *
     * @param type $path
     * @return \Zend_Config_Ini
     */
    protected function _loadConfig($path) {
        if (file_exists($path . '/template.ini')) {
            $config = new \Zend_Config_Ini($path . '/template.ini', null, array('allowModifications' => true));
            if (file_exists($path . '/template-local.ini')) {
                $config->merge(new \Zend_Config_Ini($path . '/template-local.ini', null, array('allowModifications' => true)));
            }
            return $config;
        }
    }

    protected function _saveAllTraversable(array $data)
    {
        foreach ($data as $template => $templateData) {
            if (count($templateData)> 3) {
                // Save values
                $this->addChanged($this->saveTemplate($template, $templateData));
            }
        }
    }

    /**
     * Reset a template to it's default values by deleteing template-local.ini
     *
     * @param string $id
     * @return boolean true on success
     */
    public function reset($id) {
        $template = $this->load(array('name'=>$id));

        $result = false;
        if (count($template) == 1) {
            if (unlink($this->_path . '/template-local.ini')) {
                // Now force recompile
                $this->_templates = false;
                $this->_template = '';

                $template = $this->load(array('name'=>$id));
                $this->saveTemplate($id, $template[$id]);
                $result = true;
            }
        }

        return $result;
    }

    public function setTemplate($template, $path, &$data)
    {
        if ($this->_template !== $template) {
            $this->_template = $template;
            $this->_path = $path;

            // Clean the model
            foreach($this->getItemNames() as $name)
            {
                if (!in_array($name, $this->_modelFields)) {
                    $this->del($name);
                }
            }

            // Now read the ini file for the template
            $iniFile = $path;

            if ($config = $this->_loadConfig($path)) {
                foreach($config->config as $field => $fieldData) {
                    $this->set($field, $fieldData->toArray());
                }
                foreach($config->data as $field => $fieldValue) {
                    $data[$template][$field] = $fieldValue;
                }
                $this->_templateData = $data[$template];
            } else {
                $this->set('warning', 'label', $this->_('Warning'), 'elementClass', 'Exhibitor', 'value', $this->_('A template.ini file should exist in the template directory in order to configure the template'));
           }
        } else {
            $data[$template] = $data[$template] + $this->_templateData;
        }
    }

    public function saveTemplate($template, $data) {
        $changed = 0;

        $config = $this->_loadConfig($this->_path);
        $original = $config->data->toArray();
        foreach ($original as $field => $value)
        {
            if ($value !== $data[$field]) {
                $config->data->$field = $data[$field];
                $changed = 1;
            }
        }

        if($changed || true) {  // Maybe shut off later, for now always recompile
            $writer = new \Zend_Config_Writer_Ini();
            unset($config->config);
            $writer->write($this->_path . '/template-local.ini', $config);

            // Also output to less file for variables
            $variables = $config->data->toArray();
            $file = fopen($this->_path . '/variables.less', 'w');
            fwrite($file, "/* GemsTracker variables, do not change directly, use template configuration screen */\n");
            foreach ($variables as $variable => $value)
            {
                fwrite($file, sprintf("@%s: %s;\n", $variable, $value));
            }
            fclose($file);

            // Force recompile of less files
            $compiled = 0;

            $view = \Zend_Layout::getMvcInstance()->getView();
            $headlink = $view->headLink();

            if ($headlink instanceof \MUtil\Less\View\Helper\HeadLink) {
                foreach($data['sheets'] as $url) {
                    if (\MUtil\StringUtil\StringUtil::endsWith($url, '.less', true)) {
                        $result = $headlink->compile($view, $url, true);

                        if ($result) {
                            $compiled++;
                        }
                    }
                }
            }

            \Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage(
                        sprintf($this->_('Compiled %s file(s)'), $compiled));
        }

        return $changed;
    }
}
