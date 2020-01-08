<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 08-Jan-2020 12:30:07
 */
class ApplyFiltersInformation extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $seq = $this->getHtmlSequence();
        $seq->br();
        $div = $seq->div(['class' => 'alert alert-info', 'role' => "alert"]);

        $div->h2($this->_('Explanation of Appointment Filter Application'), ['style' => 'margin-top: 5px;']);

        $p = $div->pInfo($this->_('Appointment filters are used to automatically assign appointments to filters.'));
        $p->append(' ' . $this->_('The filters are applied in consecutive steps for application to existing tracks and to create new tracks.'));

        $div->h3($this->_('Step 1: Application to existing tracks'));

        $ul = $div->ul();
        $ul->li($this->_('The tracks that use a filter for the appointment are selected.'));
        $ul->li($this->_('The tracks that use the appointment are added to the selection.'));
        $ul->li($this->_('In the order of track creation all these tracks are checked.'));
        $ul->li($this->_('During the check all the fields of the tracks wil be recalculated.'));
        $ul->li($this->_('Field recalculation uses the filters to options to select the appointment.'));
        $ul->li($this->_('The field definition can add additional date / time constraints to find the correct appointment.'));
        $ul->li($this->_('If the track is open and valid the tokens are recalculated afterwards.'));

        $div->h3($this->_('Step 2: Creation of new tracks'));

        $ul = $div->ul();
        $ul->li($this->_('The filters for the appointment are loaded in the (execution) order specified for the filters.'));
        $ul->li($this->_('The filters are matched to the tracks fields using them, the lowest (field) order first. If those are equal then the older track is used first.'));
        $ul->li($this->_('In this order the filters and fields are processed.'));
        $ul->li($this->_("If the field 'does something' 'when not assigned' that method is checked."));
        $ul->li($this->_("When the 'when not assigned' options is fulfilled (optionally with a difference in days) a new track is created."));

        $div->pInfo($this->_('These checks are run automatically every time an appointment is created or changed.'));

        return $seq;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        return FieldMaintenanceModel::APPOINTMENTS_NAME == $this->request->getParam('sub', FieldMaintenanceModel::APPOINTMENTS_NAME);
    }
}
