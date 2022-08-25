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

use Gems\Model;

/**
 * Snippet for showing the all tokens for a single track for a single patient
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see \MUtil\Registry\TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class TrackTokenOverviewSnippet extends \Gems\Snippets\TokenModelSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter = [
        'gro_active = 1 OR gro_active IS NULL',
        'gsu_active' => 1,
    ];

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = [
            'gto_round_order' => SORT_ASC,
            'gto_created'     => SORT_ASC
    ];

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
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $tData = $this->util->getTokenData();

        // Signal the bridge that we need these values
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2o_patient_nr;

        $bridge->tr()->appendAttrib('class', \MUtil\Lazy::iif(
                        $bridge->gro_id_round,
                        $bridge->row_class,
                        array($bridge->row_class , ' inserted')
                        ));

        // Add token status
        $bridge->td($tData->getTokenStatusLinkForBridge($bridge, false))->appendAttrib('class', 'text-right');

        // Columns
        $bridge->addSortable('gsu_survey_name')
                ->append(\MUtil\Lazy::iif(
                        $bridge->gro_icon_file,
                        \MUtil\Lazy::iif($bridge->gto_icon_file, \MUtil\Html::create('img', array('src' => $bridge->gto_icon_file, 'class' => 'icon')),
                            \MUtil\Lazy::iif($bridge->gro_icon_file, \MUtil\Html::create('img', array('src' => $bridge->gro_icon_file, 'class' => 'icon'))))
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
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $table = parent::getHtmlOutput($view);

        $table->class = $this->class;
        $this->applyHtmlAttributes($table);
        $this->class = false;
        $tableContainer = \MUtil\Html::create()->div(array('class' => 'table-container'), $table);

        return $tableContainer;
    }

    protected function getRespondentTrackId(): ?int
    {
        if (isset($this->trackData['gr2t_id_respondent_track'])) {
            return (int) $this->trackData['gr2t_id_respondent_track'];
        }
        if (isset($this->trackData['gto_id_respondent_track'])) {
            return (int) $this->trackData['gto_id_respondent_track'];
        }
        $params = $this->requestInfo->getRequestMatchedParams();
        if (isset($params[Model::RESPONDENT_TRACK])) {
            return (int) $params[Model::RESPONDENT_TRACK];
        }

        return null;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (! $this->respondentTrackId) {
            $this->respondentTrackId = $this->getRespondentTrackId();
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
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil\Model\ModelAbstract $model)
    {
        $model->setFilter(['gto_id_respondent_track' => $this->respondentTrackId]);

        $this->processSortOnly($model);
    }
}
