<?php

/**
 * Copyright (c) 201e, Erasmus MC
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
 * @package    MUtil
 * @subpackage FileListModel
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $id: FileListModel.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * A model for listing files in directory structures
 *
 * @package    MUtil
 * @subpackage FileListModel
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Model_FolderModel extends MUtil_Model_ArrayModelAbstract
{
    /**
     * The directory to list
     *
     * @var string
     */
    protected $dir;


    /**
     * Regex filename mask, use of / slashes for directory seperator required
     *
     * @var string
     */
    protected $mask;

    /**
     * When true searches directories recursively
     *
     * @var boolean
     */
    protected $recursive;

    /**
     *
     * @param string $dir
     * @param string $pregMask An optional regex file mask, use of / for directory seperator required
     * @param boolean $recursive When true the directory is searched recursively
     */
    public function __construct($dir, $mask = null, $recursive = false)
    {
        parent::__construct($dir);

        $this->dir = $dir;

        $this->mask = $mask;

        $this->recursive = $recursive;

        $this->set('fullpath',     'type', MUtil_Model::TYPE_STRING);
        $this->set('path',         'type', MUtil_Model::TYPE_STRING);
        $this->set('filename',     'type', MUtil_Model::TYPE_STRING);
        $this->set('relpath',      'type', MUtil_Model::TYPE_STRING);
        $this->set('urlpath',      'type', MUtil_Model::TYPE_STRING);
        $this->set('extension',    'type', MUtil_Model::TYPE_STRING);
        $this->set('content',      'type', MUtil_Model::TYPE_STRING);
        $this->set('size',         'type', MUtil_Model::TYPE_NUMERIC);
        $this->set('changed',      'type', MUtil_Model::TYPE_DATETIME);

        $this->setKeys(array('urlpath'));
    }

    /**
     * An ArrayModel assumes that (usually) all data needs to be loaded before any load
     * action, this is done using the iterator returned by this function.
     *
     * @return Traversable Return an iterator over or an array of all the rows in this object
     */
    protected function _loadAllTraversable()
    {
        if (! is_dir($this->dir)) {
            return array();
        }

        if ($this->recursive) {
            $dirIter = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($this->dir, FilesystemIterator::CURRENT_AS_FILEINFO),
                    RecursiveIteratorIterator::SELF_FIRST,
                    RecursiveIteratorIterator::CATCH_GET_CHILD
                    );
        } else {
            $dirIter = new DirectoryIterator($this->dir, FilesystemIterator::CURRENT_AS_FILEINFO);
        }

        $modelIter = new MUtil_Model_Iterator_FolderModelIterator($dirIter, $this->dir, $this->mask);

        return $modelIter;
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true)
    {
        $deleteable = $this->load($filter);

        $count = 0;
        foreach ($deleteable as $fileData) {
            if (unlink($fileData['fullpath'])) {
                $count = $ocunt + 1;
            } elseif (file_exists($fileData['fullpath'])) {
                throw new MUtil_Model_ModelException(sprintf(
                        'Unable to delete %s: %s',
                        $fileData['fullpath'],
                        MUtil_Error::getLastPhpErrorMessage('reason unknown')
                        ));
            }
        }
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null)
    {
        $filename = false;
        if ($this->recursive) {
            if (isset($newValues['relpath'])) {
                $filename = $newValues['relpath'];
            }
        }

        if (!$filename && isset($newValues['filename'])) {
            $filename = $newValues['filename'];
        }

        if (! $filename) {
            throw new MUtil_Model_ModelException('Cannot save file: no filename known');
        }

        $filename = trim($filename, '\\/');

        if ($this->dir) {
            $filename = $this->dir . DIRECTORY_SEPARATOR . $filename;
        }

        $content = isset($newValues['content']) ? $newValues['content'] : '';

        MUtil_File::ensureDir(dirname($filename));

        if (false === file_put_contents($filename, $content) || (!file_exists($filename))) {
            throw new MUtil_Model_ModelException(sprintf(
                    'Unable to save %s: %s',
                    $filename,
                    MUtil_Error::getLastPhpErrorMessage('reason unknown')
                    ));
        }
        $this->setChanged(1);

        return $newValues;
    }
}
