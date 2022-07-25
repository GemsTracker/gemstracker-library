<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

/**
 * Displays a respondent's details with assigned surveys and tracks in extra columns.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class DetailsWithAssignmentsSnippet extends \Gems\Snippets\RespondentDetailSnippetAbstract
{
    /**
     * Require
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     * Place to set the data to display
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @return void
     */
    protected function addTableCells(\MUtil\Model\Bridge\VerticalTableBridge $bridge)
    {
        $bridge->setColumnCount(1);

        $HTML = \MUtil\Html::create();

        $bridge->tdh($this->getCaption(), array('colspan' => 2));

        // Caption for tracks
        $trackLabel = $this->_('Assigned tracks');
        if ($menuItem = $this->findMenuItem('track', 'index')) {
            $href = $menuItem->toHRefAttribute($this->request, $bridge);
            $bridge->tdh(array('class' => 'linked'))->a($href, $trackLabel);
        } else {
            $bridge->tdh($trackLabel, array('class' => 'linked'));
        }

        $bridge->tr();

        // ROW 1
        $bridge->addItem($bridge->gr2o_patient_nr, $this->_('Respondent nr: '));

        $rowspan = 10;

        // Column for tracks
        $tracksModel = $this->model->getRespondentTracksModel();
        $tracksData  = \MUtil\Lazy::repeat(
            $tracksModel->load(
                array('gr2o_patient_nr' => $this->repeater->gr2o_patient_nr, 'gr2o_id_organization' => $this->repeater->gr2o_id_organization),
                array('gr2t_created' => SORT_DESC)));
        $tracksList  = $HTML->div($tracksData, array('class' => 'tracksList'));
        $tracksList->setOnEmpty($this->_('No tracks'));
        if ($menuItem = $this->findMenuItem('track', 'show-track')) {
            $href = $menuItem->toHRefAttribute($tracksData, array('gr2o_patient_nr' => $this->repeater->gr2o_patient_nr));
            $tracksTarget = $tracksList->p()->a($href);
        } else {
            $tracksTarget = $tracksList->p();
        }
        $tracksTarget->strong($tracksData->gtr_track_name);
        $tracksTarget[] = ' ';
        $tracksTarget->em($tracksData->gr2t_track_info, array('renderWithoutContent' => false));
        $tracksTarget[] = ' ';
        $tracksTarget[] = \MUtil\Lazy::call($this->util->getTranslated()->formatDate, $tracksData->gr2t_created);
        $bridge->td($tracksList, array('rowspan' => $rowspan, 'class' => 'linked tracksList'));

        // OTHER ROWS
        $bridge->addItem(
            $HTML->spaced($bridge->itemIf('grs_last_name', array($bridge->grs_last_name, ',')), $bridge->grs_first_name, $bridge->grs_surname_prefix),
            $this->_('Respondent'));
        $bridge->addItem('grs_gender');
        $bridge->addItem('grs_birthday');
        $bridge->addItem('gr2o_email');
        $bridge->addItem('gr2o_created');
        $bridge->addItem('gr2o_created_by');

        if ($this->onclick) {
            // TODO: can we not use $repeater?
            $href = array('location.href=\'', $this->onclick, '\';');

            foreach ($bridge->tbody() as $tr) {
                foreach ($tr as $td) {
                    if (strpos($td->class, 'linked') === false) {
                        $td->onclick = $href;
                    } else {
                        $td->onclick = 'event.cancelBubble=true;';
                    }
                }
            }
            $bridge->tbody()->onclick = '// Dummy for CSS';
        }
   }
}
