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
 * @subpackage Pulse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: MailLogSearchSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Pulse
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Snippets_Mail_Log_MailLogSearchSnippet extends Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var Gems_Loader
     */
    public $loader;

    /**
     * Creates the form itself
     *
     * @param array $options
     * @return Gems_Form
     */
    protected function createForm($options = null)
    {
        $form = parent::createForm($options);

        $form->activateJQuery();

        return $form;
    }

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
        // Search text
        $elements = parent::getAutoSearchElements($data);

        $this->_addPeriodSelectors($elements, array('grco_created' => $this->_('Date sent')));

        $br  = MUtil_Html::create()->br();

        $elements[] = null;

        $dbLookup = $this->util->getDbLookup();

        $elements[] = $this->_createSelectElement(
                'gto_id_track',
                $this->util->getTrackData()->getSteppedTracks(),
                $this->_('(select a track)')
                );

        $elements[] = $this->_createSelectElement('gto_id_survey',
                $this->util->getTrackData()->getAllSurveys(),
                $this->_('(all surveys)'));

        $elements[] = $this->_createSelectElement(
                'grco_organization',
                $this->loader->getCurrentUser()->getRespondentOrganizations(),
                $this->_('(all organizations)')
                );

        return $elements;
    }

    /**
     *
     * @return array The data to fill the form with
     */
    protected function getSearchData()
    {
        $data = parent::getSearchData();

        if (!array_key_exists('grco_organization', $data)) {
            $data['grco_organization'] = $this->loader->getOrganization()->getId();
        }

        return $data;
    }
}
