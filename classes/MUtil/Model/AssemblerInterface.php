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
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: AssemblerInterface.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
interface MUtil_Model_AssemblerInterface
{
    /**
     * Get the processed output of the input or a lazy object if the data is repeated
     * or not yet set using setRepeater() or setRow().
     *
     * @param string $name
     * @return mixed MUtil_Lazy_Call when not using setRow(), actual output otherwise
     */
    public function getOutput($name);

    /**
     * Returns the processor for the name
     *
     * @param string $name
     * @return MUtil_Model_ProcessorInterface or null when it does not exist
     */
    public function getProcessor($name);

    /**
     * Returns true if a processor exist for $name
     *
     * @param string $name
     * @return boolean
     */
    public function hasProcessor($name);

    /**
     * Set the model of this assembler
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return MUtil_Model_AssemblerInterface
     */
    public function setModel(MUtil_Model_ModelAbstract $model);

    /**
     * Set the processor for a name
     *
     * @param string $name
     * $param MUtil_Model_ProcessorInterface $processor
     * @return MUtil_Model_AssemblerInterface (continuation pattern)
     */
    public function setProcessor($name, MUtil_Model_ProcessorInterface $processor);

    /**
     * Use this method when you want to repeat the output for each row when rendering.
     *
     * The assembler does not itself loop through the multiple rows, for that to happen
     * you need to place the outputs of the gets on something that has the same repeater
     * and does repeat it, e.g. an MUtil_Html object.
     *
     * Either setRepeater() or setRow() should be set. setRow() is dominant.
     *
     * @param mixed $repeater MUtil_Lazy_RepeatableInterface or something that can be made into one.
     * @return MUtil_Model_AssemblerInterface (continuation pattern)
     */
    public function setRepeater($repeater);

    /**
     * Use this method when using a single row of input, i.e. do nothing lazy
     * and just draw the current row.
     *
     * Either setRepeater() or setRow() should be set. setRow() is dominant.
     *
     * @param array $data An array with data.
     * @return MUtil_Model_AssemblerInterface (continuation pattern)
     */
    public function setRow(array $data);
}
