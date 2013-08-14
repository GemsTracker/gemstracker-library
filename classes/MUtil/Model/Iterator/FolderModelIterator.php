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
 * @subpackage FileModelIterator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $id: FileModelIterator.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage FileModelIterator
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Model_Iterator_FolderModelIterator extends FilterIterator
{
    /**
     * Optional preg expression for relative filename, use of backslashes for directory seperator required
     *
     * @var string
     */
    protected $mask;

    /**
     * The starting path
     *
     * @var string
     */
    protected $startPath;

    /**
     *
     * @param Iterator $iterator
     * @param string $startPath
     * * @param string $mask Preg expression for relative filename, use of backslashes for directory seperator required
     */
    public function __construct(Iterator $iterator, $startPath = '', $mask = false)
    {
        parent::__construct($iterator);

        $this->startPath = realpath($startPath);
        if ($this->startPath) {
            $this->startPath = $this->startPath . DIRECTORY_SEPARATOR;
        }
        // MUtil_Echo::track($startPath, $this->startPath);

        $this->mask = $mask;
    }

    /**
     * FilterIterator::accept — Check whether the current element of the iterator is acceptable
     *
     * @return boolean
     */
    public function accept()
    {
        $file = parent::current();

        if (! $file instanceof SplFileInfo) {
            return false;
        }

        if (!$file->isFile() || !$file->isReadable()) {
            return false;
        }

        if ($this->mask) {
            // The relative file name uses the windows directory seperator convention as this
            // does not screw up the use of this value as a parameter
            $rel = str_replace('/', '\\', MUtil_String::stripStringLeft($file->getRealPath(), $this->startPath));

            if (!preg_match($this->mask, $rel)) {
                return false;
            }
        }

        return true;
    }

    /**
     * FilesystemIterator::current — The current file
     *
     * @return mixed null or artray
     */
    public function current()
    {
        $file = parent::current();


        if (! $file instanceof SplFileInfo) {
            return null;
        }

        $real = $file->getRealPath();

        // The relative file name uses the windows directory seperator convention as this
        // does not screw up the use of this value as a parameter
        $rel = str_replace('/', '\\', MUtil_String::stripStringLeft($real, $this->startPath));

        // Function was first implemented in PHP 5.3.6
        if (method_exists($file, 'getExtension')) {
            $extension = $file->getExtension();
        } else {
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
        }

        return array(
            'fullpath'  => $real,
            'relpath'   => $rel,
            'path'      => $file->getPath(),
            'filename'  => $file->getFilename(),
            'extension' => $extension,
            'content'   => MUtil_Lazy::call('file_get_contents', $real),
            'size'      => $file->getSize(),
            'changed'   => new MUtil_Date($file->getMTime()),
            );
    }
}
