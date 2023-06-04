<?php

namespace Gems\Snippets\Tracker\Compliance;

use Gems\Snippets\Tracker\TrackSearchFormSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ComplianceSearchFormSnippet extends TrackSearchFormSnippetAbstract
{
    /**
     * Should the filler be shown?
     *
     * @var bool
     */
    protected $showFiller = true;

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
        $elements = [];
        $this->addTrackSelect($elements, $data, 'gr2t_id_track');
        $this->addOrgSelect($elements, $data, 'gr2t_id_organization');

        $elements[] = null;

        $this->searchLabel = $this->_('Search respondent data');
        $elements = array_merge($elements, parent::getAutoSearchElements($data));

        $elements[] = null;

        $dates = array(
            'gr2t_start_date' => $this->_('Track start'),
            'gr2t_end_date'   => $this->_('Track end'),
        );
        $this->addPeriodSelectors($elements, $dates, 'gr2t_start_date');

        $elements[] = null;

        if ($this->showFiller) {
            $this->addFillerSelect($elements, $data);
        }

        return $elements;
    }
}
