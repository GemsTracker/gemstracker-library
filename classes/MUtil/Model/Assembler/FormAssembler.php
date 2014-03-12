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
 * @version    $Id: FormAssembler.php 203 2012-01-01t 12:51:32Z matijs $
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
class MUtil_Model_Assembler_FormAssembler extends MUtil_Model_AssemblerAbstract
{
    /**
     * When true, the item is a new item
     *
     * @var boolean
     */
    protected $createData = false;

    /**
     *
     * @var Zend_Form
     */
    protected $form;

    /**
     * Create the processor for this name
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param string $name
     * @return MUtil_Model_ProcessorInterface or string or array for creation null when it does not exist
     */
    protected function _assemble(MUtil_Model_ModelAbstract $model, $name)
    {
        if ($model->has($name, 'elementProcessor')) {
            return $model->get($name, 'elementProcessor');
        }

        if ($model->has($name, 'elementClass')) {
            $class = 'Element_' . $model->get($name, 'elementClass') . 'ElementProcessor';

            try {
                MUtil_Model::getProcessorLoader()->load($class);
                return $class;
            } catch (Exception $e) {
                if (MUtil_Model_AssemblerAbstract::$verbose) {
                    MUtil_Echo::r($class, 'ElementClass not found');
                }
            }
        }

        if (! $model->has($name, 'label')) {
            return 'Element_HiddenElementProcessor';
        }

        if ($model->has($name, 'multiOptions')) {
            return 'Element_SelectElementProcessor';
        }

        return 'Element_TextElementProcessor';
    }

    /**
     * Perform the actual processing
     *
     * @param MUtil_Model_ProcessorInterface $processor
     * @param string $name
     * @param array $data
     * @return mixed The outpur
     */
    protected function _process(MUtil_Model_ProcessorInterface $processor, $name, array $data)
    {
        if ($processor instanceof MUtil_Model_Processor_ElementProcessorInterface) {

            $processor->setCreatingData($this->isCreatingData());

            // Or should we throw an exception otherwise?
            if ($this->hasForm()) {
                $processor->setForm($this->getForm());
            }
        }

        return parent::_process($processor, $name, $data);
    }

    /**
     * Get the form - if known
     *
     * @return Zend_Form or null
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Is the form set
     *
     * @return boolean
     */
    public function hasForm()
    {
        return $this->form instanceof Zend_Form;
    }

    /**
     * When true we're editing a new item
     *
     * @return boolean
     */
    public function isCreatingData()
    {
        return $this->createData;
    }


    /**
     * When true we're editing a new item
     *
     * @param boolean $isNew
     * @return \MUtil_Model_Processor_ElementProcessorAbstract (Continuatiuon pattern)
     */
    public function setCreatingData($isNew = true)
    {
        $this->createData = $isNew;
        return $this;
    }

    /**
     * Set the form - if known
     *
     * @param Zend_Form $form
     * @return \MUtil_Model_Processor_ElementProcessorAbstract (Continuatiuon pattern)
     */
    public function setForm(Zend_Form $form)
    {
        $this->form = $form;
        return $this;
    }
}
