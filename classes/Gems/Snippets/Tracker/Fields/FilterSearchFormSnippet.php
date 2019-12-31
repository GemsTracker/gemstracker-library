<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Fields
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Fields;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Fields
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 31-Dec-2019 12:20:32
 */
class FilterSearchFormSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

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
        $agenda = $this->loader->getAgenda();

        $elements = parent::getAutoSearchElements($data);

        $elements['gaf_class']  = $this->_createSelectElement('gaf_class',  $this->model, $this->_('(all types)'));
        $elements['gaf_active'] = $this->_createSelectElement('gaf_active', $this->model, $this->_('(all active)'));

        $elements[] = \MUtil_Html::create('br');
        $elements[] = \MUtil_Html::create('strong', $this->_('Usage'));

        $tracks = [
            -1 => $this->_('(not used in any track)'),
            -2 => $this->_('(used in any track)'),
//            -3 => $this->_('(used but not to create track)'),
//            -4 => $this->_('(used to create track)'),
            ] + $this->util->getTrackData()->getTracksForOrgs($this->currentUser->getRespondentOrganizations());

        $elements['used_in_track'] = $this->_createSelectElement(
                'used_in_track',
                $tracks,
                $this->_('(select a track)')
                );

        $creators = [
            -1 => $this->_('(any creation)'),
            ] + $agenda->getTrackCreateOptions();
        $elements['creates_track'] = $this->_createSelectElement(
                'creates_track',
                $creators,
                $this->_('(select creation option)')
                );


        $filters = [
            -1 => $this->_('(not used in any filter)'),
            -2 => $this->_('(used in any filter)'),
            ] + $agenda->getFilterList();
        $elements['used_in_filter'] = $this->_createSelectElement(
                'used_in_filter',
                $filters,
                $this->_('(select a filter used)')
                );

        return $elements;
    }
}
