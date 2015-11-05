<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Respondent;

/**
 * Displays a respondent's details
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class RespondentDetailsSnippet extends \Gems_Snippets_RespondentDetailSnippetAbstract
{
    /**
     * Place to set the data to display
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    protected function addTableCells(\MUtil_Model_Bridge_VerticalTableBridge $bridge)
    {
        $HTML = \MUtil_Html::create();

        $bridge->caption($this->getCaption());

        $br = $HTML->br();
        $address[] = $bridge->grs_address_1;
        $address[] = $br;
        if ($this->model->has('grs_address_2')) {
            $address[] = $bridge->grs_address_2;
            $address[] = $bridge->itemIf('grs_address_2', $br);
        }
        $address[] = $bridge->grs_zipcode;
        $address[] = $bridge->itemIf('grs_zipcode', new \MUtil_Html_Raw('&nbsp;&nbsp;'));
        $address[] = $bridge->grs_city;

        // ROW 0
        $label = $this->model->get('gr2o_patient_nr', 'label'); // Try to read label from model...
        if (empty($label)) {
            $label = $this->_('Respondent nr: ');               // ...but have a fall-back
        }
        $bridge->addItem($bridge->gr2o_patient_nr, $label);
        $bridge->addItem(
            $HTML->spaced($bridge->itemIf('grs_last_name', array($bridge->grs_last_name, ',')), $bridge->grs_gender, $bridge->grs_first_name, $bridge->grs_surname_prefix),
            $this->_('Respondent'));

        // ROW 1
        $bridge->addItem('grs_birthday');
        $bridge->addItem('grs_phone_1');

        // ROW 2
        $bridge->addItem('grs_email');
        $bridge->addItem($address, $this->_('Address'));
    }
}
