<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Default_FileImportAction extends Gems_Default_FileActionAbstract
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
        $params['formatBoxClass']          = 'browser';
        $params['importer']                = $importLoader->getImporter($controller);
        $params['importLoader']            = $importLoader;
        $params['tempDirectory']           = $importLoader->getTempDirectory();
        $params['importTranslators']       = $importLoader->getTranslators($controller);

        $this->addSnippets('Survey_AnswerImportSnippet', $params);
    }

    /**
     * Action for automaticaly loading all ready files
     * /
    public function autoAction()
    {
        $importLoader = $this->loader->getImportLoader();
        $files        = $this->getModel()->load(true, 'changed');

        $batch = $this->loader->getTaskRunnerBatch('auto-file-import');

        foreach ($files as $data) {
            $importer = $importLoader->getFileImporter($data['fullpath']);

            if ($importer) {
                $this->addMessage(sprintf($this->_('"%s" is go!'), $data['relpath']));

                // Batch is reset :((
                $importer->getCheckAndImportBatch($batch);
            } else {
                $this->addMessage(sprintf($this->_('Automatic import not possible for file "%s".'), $data['relpath']));
            }
        }

        $title = $this->_('Import the files.');

        $this->_helper->batchRunner($batch, $title);

        $this->html->pInfo($this->_('Checks the files for validity and then performs an import.'));
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
