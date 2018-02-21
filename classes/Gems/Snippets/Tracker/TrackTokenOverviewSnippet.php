<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

/**
 * Snippet for showing the all tokens for a single track for a single patient
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see \MUtil_Registry_TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class TrackTokenOverviewSnippet extends \Gems_Snippets_TokenModelSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter = array(
        'gro_active = 1 OR gro_active IS NULL',
        'gsu_active' => 1,
    );

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
            'gto_round_order' => SORT_ASC,
            'gto_created'     => SORT_ASC
        );

    /**
     * The respondent2track ID
     *
     * @var int
     */
    protected $respondentTrackId;

    /**
     * Optional: the display data of the track shown
     *
     * @var array
     */
    protected $trackData;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $tData = $this->util->getTokenData();

        // Signal the bridge that we need these values
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2o_patient_nr;

        $bridge->tr()->appendAttrib('class', \MUtil_Lazy::iif(
                        $bridge->gro_id_round,
                        $bridge->row_class,
                        array($bridge->row_class , ' inserted')
                        ));

        // Add token status
        $bridge->td($tData->getTokenStatusLinkForBridge($bridge, false));

        // Columns
        $bridge->addSortable('gsu_survey_name')
                ->append(\MUtil_Lazy::iif(
                        $bridge->gro_icon_file,
                        \MUtil_Lazy::iif($bridge->gto_icon_file, \MUtil_Html::create('img', array('src' => $bridge->gto_icon_file, 'class' => 'icon')),
                            \MUtil_Lazy::iif($bridge->gro_icon_file, \MUtil_Html::create('img', array('src' => $bridge->gro_icon_file, 'class' => 'icon'))))
                        ));
        $bridge->addSortable('gto_round_description');
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('gto_valid_from',      null, 'date');
        $bridge->addSortable('gto_completion_time', null, 'date');
        $bridge->addSortable('gto_valid_until',     null, 'date');

        // Rights depended score column
        if ($this->currentUser->hasPrivilege('pr.respondent.result') &&
                (! $this->currentUser->isFieldMaskedWhole('gto_result'))) {
            $bridge->addSortable('gto_result', $this->_('Score'), 'date');
        }

        $this->addActionLinks($bridge);
        $this->addTokenLinks($bridge);
    }

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
        $table = parent::getHtmlOutput($view);

        $table->class = $this->class;
        $this->applyHtmlAttributes($table);
        $this->class = false;
        $tableContainer = \MUtil_Html::create()->div(array('class' => 'table-container'), $table);

        return $tableContainer;
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
        if (! $this->respondentTrackId) {
            if (isset($this->trackData['gr2t_id_respondent_track'])) {
                $this->respondentTrackId = $this->trackData['gr2t_id_respondent_track'];

            } elseif (isset($this->trackData['gto_id_respondent_track'])) {
                $this->respondentTrackId = $this->trackData['gto_id_respondent_track'];

            } elseif ($this->request && ($respondentTrackId = $this->request->getParam(\Gems_Model::RESPONDENT_TRACK))) {
                $this->respondentTrackId = $respondentTrackId;
            }
        }
        if ($this->respondentTrackId) {
            return parent::hasHtmlOutput();
        } else {
            return false;
        }
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        $model->setFilter(array('gto_id_respondent_track' => $this->respondentTrackId));

        $this->processSortOnly($model);
    }
}
