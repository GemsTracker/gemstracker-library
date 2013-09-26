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
 * @version    $id: FileActionAbstract.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
abstract class Gems_Default_FileActionAbstract extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'FolderModelTableSnippet';

    /**
     * The snippets used for the import action
     *
     * @var mixed String or array of snippets name
     */
    protected $importSnippets = 'Import_FileImportSnippet';

    /**
     * The regex mask for filenames, use of / slashes for directory seperator required
     *
     * @var string Regular expression
     */
    protected $mask;

    /**
     * Should the action look recursively through the files
     *
     * @var boolean
     */
    public $recursive = false;

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new MUtil_Model_FolderModel(
                $this->getPath($detailed, $action),
                $this->getMask($detailed, $action),
                $this->recursive
                );

        if ($this->recursive) {
            $model->set('relpath',  'label', $this->_('File (local)'),
                    'maxlength', 255,
                    'size', 40,
                    'validators', array('File_Path', 'File_IsRelativePath')
                    );
            $model->set('filename', 'elementClass', 'Exhibitor');
        }
        if ($detailed || (! $this->recursive)) {
            $model->set('filename',  'label', $this->_('Filename'), 'size', 30, 'maxlength', 255);
        }
        if ($detailed) {
            $model->set('path',      'label', $this->_('Path'), 'elementClass', 'Exhibitor');
            $model->set('fullpath',  'label', $this->_('Full name'), 'elementClass', 'Exhibitor');
            $model->set('extension', 'label', $this->_('Type'), 'elementClass', 'Exhibitor');
            $model->set('content',   'label', $this->_('Content'),
                    'formatFunction', array(MUtil_Html::create(), 'pre'),
                    'elementClass', 'TextArea');
        }
        $model->set('size',      'label', $this->_('Size'),
                'formatFunction', array('MUtil_File', 'getByteSized'),
                'elementClass', 'Exhibitor');
        $model->set('changed',   'label', $this->_('Changed on'),
                'dateFormat', $this->util->getTranslated()->dateTimeFormatString,
                'elementClass', 'Exhibitor');

        return $model;
    }

    /**
     * Confirm has been moved to javascript
     */
    public function deleteAction()
    {
        $model = $this->getModel();
        $model->applyRequest($this->getRequest());

        $model->delete();
        $this->_reroute(array($this->getRequest()->getActionKey() => 'index', MUTil_Model::REQUEST_ID => null));
    }

    /**
     * Action for downloading the file
     */
    public function downloadAction()
    {
        $model = $this->getModel();
        $model->applyRequest($this->getRequest());

        $fileData = $model->loadFirst();

        header('Content-Type: application/x-download');
        header('Content-Length: '.$fileData['size']);
        header('Content-Disposition: inline; filename="'.$fileData['filename'].'"');
        header('Pragma: public');

        echo $fileData['content'];
        exit();
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return $string
     */
    public function getEditTitle()
    {
        return sprintf($this->_('Edit file %s'), $this->_getIdParam());
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
        return $this->mask;
    }

    /**
     * Returns the on empty texts for the autofilter snippets
     *
     * @static boolean $warned Listen very closely. I shall tell this only once!
     * @return string
     */
    public function getOnEmptyText()
    {
        static $warned;

        $dir = $this->getPath(false, 'index');

        if (! is_dir($dir)) {
            try {
                MUtil_File::ensureDir($dir);

            } catch (Zend_Exception $e) {
                $text = $e->getMessage();

                if (! $warned) {
                    $warned = true;
                    $this->addMessage($text);
                }
                return $text;
            }
        }

        return parent::getOnEmptyText();
    }

    /**
     * Return the path of the directory
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return string
     */
    abstract public function getPath($detailed, $action);

    /**
     * Helper function to get the title for the show action.
     *
     * @return $string
     */
    public function getShowTitle()
    {
        return sprintf($this->_('Showing file %s'), $this->_getIdParam());
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('file', 'files', $count);
    }

    /**
     * Import the file
     */
    public function importAction()
    {
        $id    = $this->_getIdParam();
        $model = $this->getModel();
        
        $data = $model->loadFirst(array('urlpath' => $id));
        
        if (! ($data && isset($data['fullpath']))) {
            $this->addMessage(sprintf($this->_('File "%s" not found on the server.'), $id));
            return;                    
        }
        
        $importLoader = $this->loader->getImportLoader();
        $importer = $importLoader->getFileImporter($data['fullpath']);

        if (! $importer) {
            $this->addMessage(sprintf($this->_('Automatic import not possible for file "%s".'), $data['relpath']));
            return;                    
        }
        
        $batch = $importer->getCheckAndImportBatch();
        
        $title = $this->_('Import the file.');

        $this->_helper->batchRunner($batch, $title);

        $this->html->pInfo($this->_('Checks this file for validity and then performs an import.'));
        
        // MUtil_Echo::track($data);
    }
}
