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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_RespondentSearchSnippet extends Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        $elements = parent::getAutoSearchElements($data);

        $user = $this->loader->getCurrentUser();
        if ($user->hasPrivilege('pr.respondent.select-on-track')) {
            $elements[] = $this->_createCheckboxElement('show_with_track',    $this->_('Has track'));
            $elements[] = $this->_createCheckboxElement('show_without_track', $this->_('No track'));
            $lineBreak = true;
        } else {
            $lineBreak = false;
        }

        if ($user->hasPrivilege('pr.respondent.show-deleted')) {
            $elements[] = $this->_createCheckboxElement('grc_success', $this->_('Show active'));
        }

        if ($this->model->isMultiOrganization()) {
            $element = $this->_createSelectElement(
                    MUtil_Model::REQUEST_ID2,
                    $user->getRespondentOrganizations(),
                    $this->_('(all organizations)')
                    );

            if ($lineBreak) {
                $element->setLabel($this->_('Organization'));
                $elements[] = \MUtil_Html::create('br');
            }
            $elements[] = $element;
        }

        return $elements;
    }
}
