<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class FileImportAction extends \Gems\Actions\FileActionAbstract
{
    /**
     * Should the action look recursively through the files
     *
     * @var boolean
     */
    public $recursive = true;

    /**
     * Import answers to a survey
     */
    public function answersImportAction()
    {
        $controller   = 'answers';
        $importLoader = $this->loader->getImportLoader();

        $params['defaultImportTranslator'] = $importLoader->getDefaultTranslator($controller);
        $params['formatBoxClass']          = 'browser table';
        $params['importer']                = $importLoader->getImporter($controller);
        $params['importLoader']            = $importLoader;
        $params['tempDirectory']           = $importLoader->getTempDirectory();
        $params['importTranslators']       = $importLoader->getTranslators($controller);
        $params['routeAction']             = 'answers-import';  // Prevent going to a different action

        $this->addSnippets('Survey\\AnswerImportSnippet', $params);
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Files ready for import');
    }

    /**
     * Return the mask to use for the relpath of the file, use of / slashes for directory seperator required
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return string or null
     */
    public function getMask($detailed, $action)
    {
        return $this->loader->getImportLoader()->getFileImportMask();
    }

    /**
     * Return the path of the directory
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return string
     */
    public function getPath($detailed, $action)
    {
        return $this->loader->getImportLoader()->getFileImportRoot();
    }
}
