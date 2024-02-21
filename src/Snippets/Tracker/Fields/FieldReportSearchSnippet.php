<?php

namespace Gems\Snippets\Tracker\Fields;

use Gems\Snippets\Tracker\TrackSearchFormSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
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
class FieldReportSearchSnippet extends \Gems\Snippets\Tracker\Compliance\ComplianceSearchFormSnippet
{
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
        $this->addTrackSelect($elements, $data, 'gtf_id_track');
        $this->addOrgSelect($elements, $data, 'gr2t_id_organization');
        
        $elements[] = null;

        $dates = array(
            'gr2t_start_date' => $this->_('Track start'),
            'gr2t_end_date'   => $this->_('Track end'),
        );
        $this->addPeriodSelectors($elements, $dates, 'gto_valid_from');
        
        $elements[] = null;
        
        return $elements;
    }
}