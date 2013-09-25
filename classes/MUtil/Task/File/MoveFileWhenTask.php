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
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $id: MoveFileWhenTask.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Task
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Task_File_MoveFileWhenTask extends MUtil_Task_TaskAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param string $file The file to move
     * @param string $destination The destionation (folder or new name
     * @param string $counter Name of coutner threshold - or leave empty for always move
     * @param int $min Optional minimum counter value
     * @param int $max Optional maximum counter value (use either minimum or both)
     */
    public function execute($file = null, $destination = null, $counter = null, $min = null, $max = null)
    {
        if (! file_exists($file)) {
            return;
        }

        $batch = $this->getBatch();
        if ($counter) {
            $value = $batch->getCounter($counter);

            if ((null !== $min) && ($value < $min)) {
                return;
            }
            if ((null !== $max) && ($value > $max)) {
                return;
            }
        }

        if (is_dir($destination)) {
            $destination = $destination . DIRECTORY_SEPARATOR . basename($file);
        }
        MUtil_File::ensureDir(dirname($destination));

        if (@copy($file, $destination)) {
            $batch->addMessage(sprintf($this->_('Archived file as "%s".'), basename($destination)));
            if (! @unlink($file)) {
                $batch->addMessage(sprintf($this->_('Could not remove original file "%s".'), basename($file)));
            }
        } else {
            $batch->addMessage(sprintf(
                    $this->_('Could not move "%s" to "%s".'),
                    basename($file),
                    basename($destination)
                    ));
        }
    }
}
