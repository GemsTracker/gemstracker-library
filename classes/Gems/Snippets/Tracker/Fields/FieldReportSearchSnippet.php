<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldReportSearchSnippet.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 30-nov-2014 18:11:58
 */
class Gems_Snippets_Tracker_Fields_FieldReportSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

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
        $orgs = $this->currentUser->getRespondentOrganizations();

        $elements[] = $this->_createSelectElement(
                'gtf_id_track',
                $this->util->getTrackData()->getTracksForOrgs($orgs),
                $this->_('(select a track)')
                );

        if (count($orgs) > 1) {
            $elements[] = $this->_createSelectElement('gr2t_id_organization', $orgs, $this->_('(all organizations)'));
            // $elements[] = $this->_createMultiCheckBoxElement('gr2t_id_organization', $orgs, $this->_('Toggle'), ' ');
        }

        return $elements;
    }
}
