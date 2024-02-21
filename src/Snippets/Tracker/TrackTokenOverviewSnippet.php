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
use Gems\Snippets\TokenModelSnippetAbstract;
use Zalt\Html\Html;
use Zalt\Late\Late;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

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
class TrackTokenOverviewSnippet extends TokenModelSnippetAbstract
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
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $table = $bridge->getTable();

        $table->tr()->appendAttrib('class', Late::iif(
                        $bridge->getFormatted('gro_id_round'),
                        $bridge->getFormatted('row_class'),
                        array($bridge->getFormatted('row_class'), ' inserted')
                        ));

        // Add token status
        $table->td(Html::raw($this->tokenRepository->getTokenStatusLinkForBridge($bridge, $this->menuHelper)))->appendAttrib('class', 'text-right');

        // Columns
        $iconFile = $bridge->getFormatted('gto_icon_file');
        $roundIcon = Late::iif($iconFile, \Gems\Html::create('img', array('src' => $iconFile, 'class' => 'icon')), '');

        $bridge->addSortable('gsu_survey_name')->append($roundIcon);
        $bridge->addSortable('gto_round_description');
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('gto_valid_from',      null, 'date');
        $bridge->addSortable('gto_completion_time', null, 'date');
        $bridge->addSortable('gto_valid_until',     null, 'date');

        // Rights depended score column
        if ($this->currentUser->hasPrivilege('pr.respondent.result') &&
                (! $this->maskRepository->isFieldMaskedWhole('gto_result'))) {
            $bridge->addSortable('gto_result', $this->_('Score'), 'date');
        }

        $this->addActionLinks($bridge);
        $this->addTokenLinks($bridge);
    }

    public function getFilter(MetaModelInterface $metaModel): array
    {
        $filter = parent::getFilter($metaModel);

        $filter['gto_id_respondent_track'] = $this->respondentTrackId;

        return $filter;
    }

    /**
     * @inheritdoc
     */
    public function getHtmlOutput()
    {
        $table = parent::getHtmlOutput();

        $table->class = $this->class;
        $this->applyHtmlAttributes($table);
        $this->class = '';
        $tableContainer = \Zalt\Html\Html::create()->div(['class' => 'table-container'], $table);

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

    public function getRouteMaps(MetaModelInterface $metaModel): array
    {
        $output = parent::getRouteMaps($metaModel);
        $output[\MUtil\Model::REQUEST_ID1] = 'gr2o_patient_nr';
        $output[\MUtil\Model::REQUEST_ID2] = 'gr2o_id_organization';
        $output[\MUtil\Model::REQUEST_ID] = 'gto_id_token';
        return $output;
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
    public function hasHtmlOutput(): bool
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
}