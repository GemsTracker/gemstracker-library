<?php

/**
 * Copyright (c) 2016, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 21, 2016 3:52:53 PM
 */
class ModelImportSnippet extends \MUtil_Snippets_Standard_ModelImportSnippet
{
    /**
     *
     * @var \Gems_AccessLog
     */
    protected $accesslog;

    /**
     * Hook for after save
     *
     * @param \MUtil_Task_TaskBatch $batch that was just executed
     * @param \MUtil_Form_Element_Html $element Tetx element for display of messages
     * @return string a message about what has changed (and used in the form)
     */
    public function afterImport(\MUtil_Task_TaskBatch $batch, \MUtil_Form_Element_Html $element)
    {
        $text = parent::afterImport($batch, $element);

        $data = $this->formData;

        // Remove unuseful data
        unset($data['button_spacer'], $data['current_step']);

        // Add useful data
        $data['localfile']        = basename($this->_session->localfile);
        $data['extension']        = $this->_session->extension;

        $data['failureDirectory'] = '...' . substr($this->importer->getFailureDirectory(), -30);
        $data['longtermFilename'] = basename($this->importer->getLongtermFilename());
        $data['successDirectory'] = '...' . substr($this->importer->getSuccessDirectory(), -30);
        $data['tempDirectory']    = '...' . substr($this->tempDirectory, -30);

        $data['importTranslator'] = get_class($this->importer->getImportTranslator());
        $data['sourceModelClass'] = get_class($this->sourceModel);
        $data['targetModelClass'] = get_class($this->targetModel);

        ksort($data);

        $this->accesslog->logChange($this->request, null, array_filter($data));
    }
}
