<?php

/**
 *
 * @package    Gems
 * @subpackage Pulse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class Gems_Snippets_Mail_Log_MailLogSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        // Search text
        $elements = parent::getAutoSearchElements($data);

        $this->_addPeriodSelectors($elements, array('grco_created' => $this->_('Date sent')));

        $br  = \MUtil_Html::create()->br();

        $elements[] = null;

        $dbLookup = $this->util->getDbLookup();

        $elements[] = $this->_createSelectElement(
                'gto_id_track',
                $this->util->getTrackData()->getAllTracks(),
                $this->_('(select a track)')
                );

        $elements[] = $this->_createSelectElement(
                'gto_id_survey',
                $this->util->getTrackData()->getAllSurveys(),
                $this->_('(all surveys)')
                );

        $elements[] = $this->_createSelectElement(
                'grco_organization',
                $this->loader->getCurrentUser()->getRespondentOrganizations(),
                $this->_('(all organizations)')
                );

        return $elements;
    }
}
