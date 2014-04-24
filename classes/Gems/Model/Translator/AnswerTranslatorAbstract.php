<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AnswerTranslatorAbstract.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 24-apr-2014 16:08:57
 */
class Gems_Model_Translator_AnswerTranslatorAbstract extends MUtil_Model_ModelTranslatorAbstract
{
    /**
     * Create an empty form for filtering and validation
     *
     * @return \MUtil_Form
     */
    protected function _createTargetForm()
    {
        return new Gems_Form();
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws MUtil_Model_ModelException
     */
    public function getFieldsTranslations()
    {
        $fieldList   = array();

        foreach ($this->_targetModel->getCol('survey_question') as $name => $use) {
            if ($use) {
                $fieldList[$name] = $name;
            }
        }

        return $fieldList;
    }
}
