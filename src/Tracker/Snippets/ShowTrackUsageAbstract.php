<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

use Gems\Util\Translated;
use Zalt\Model\Data\DataReaderInterface;

/**
 * Displays the assignments of a track to a respondent.
 *
 * This code contains some display options for excluding or marking a single track
 * and for processing the passed parameters identifying the respondent and the
 * optional single track.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class ShowTrackUsageAbstract extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('gr2t_created' => SORT_DESC);

    /**
     * Optional, when true current item is not shown, when false the current row is marked as the currentRow.
     *
     * @var boolean
     */
    protected $excludeCurrent = false;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Required
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Optional, required when using $trackEngine or $trackId only
     *
     * @var int Organization Id
     */
    protected $organizationId;

    /**
     * Optional, required when using $trackEngine or $trackId only
     *
     * @var int Patient Id
     */
    protected $patientId;

    /**
     * Optional, one of $respondentTrack, $respondentTrackId, $trackEngine, $trackId should be set
     *
     * @var \Gems\Tracker\RespondentTrack
     */
    protected $respondentTrack;

    /**
     *
     * @var int Respondent Track Id
     */
    protected $respondentTrackId;

    /**
     * Option to manually diasable the menu
     *
     * @var boolean
     */
    protected bool $showMenu = false;

    /**
     * Optional, one of $respondentTrack, $respondentTrackId, $trackEngine, $trackId should be set
     *
     * $trackEngine and TrackId need $patientId and $organizationId to be set as well
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, one of $respondentTrack, $respondentTrackId, $trackEngine, $trackId should be set
     *
     * $trackEngine and TrackId need $patientId and $organizationId to be set as well
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->db && $this->loader;
    }

    protected function createModel(): DataReaderInterface
    {
        $model = $this->loader->getTracker()->getRespondentTrackModel();

        $model->getMetaModel()->set('gtr_track_name', ['label' => $this->_('Track')]);
        $model->getMetaModel()->set('gr2t_track_info', [
            'label' => $this->_('Description'),
            'description' => $this->_('Enter the particulars concerning the assignment to this respondent.')
        ]);
        $model->getMetaModel()->set('assigned_by', ['label' => $this->_('Assigned by')]);
        $model->getMetaModel()->set('gr2t_start_date', [
            'label' => $this->_('Start'),
            'dateFormat' => 'dd-MM-yyyy',
            'formatFunction' => $this->translatedUtil->formatDate,
            'default' => new \DateTimeImmutable()
        ]);
        $model->getMetaModel()->set('gr2t_reception_code');

        return $model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     */
    public function getHtmlOutput()
    {
        $seq = $this->getHtmlSequence();

        $seq->h3($this->getTitle());

        $table = parent::getHtmlOutput();
        $this->applyHtmlAttributes($table);

        $seq->append($table);

        return $seq;
    }

    /**
     * Get a display version of the patient name
     *
     * @return string
     */
    protected function getRespondentName()
    {
        if ($this->respondentTrack instanceof \Gems\Tracker\RespondentTrack) {
            return $this->respondentTrack->getRespondentName();
        } else {
            $select = $this->db->select();
            $select->from('gems__respondents')
                ->joinInner('gems__respondent2org', 'grs_id_user = gr2o_id_user', array())
                ->where('gr2o_patient_nr = ?', $this->patientId)
                ->where('gr2o_id_organization = ?', $this->organizationId);

            $data = $this->db->fetchRow($select);

            if ($data) {
                return trim($data['grs_first_name'] . ' ' . $data['grs_surname_prefix']) . ' ' . $data['grs_last_name'];
            }
        }

        return '';
    }

    abstract protected function getTitle();

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
        // Try to set $this->respondentTrackId, it can be ok when not set
        if (! $this->respondentTrackId) {
            if ($this->respondentTrack) {
                $this->respondentTrackId = $this->respondentTrack->getRespondentTrackId();
            } else {
                $this->respondentTrackId = $this->requestInfo->getParam(\Gems\Model::RESPONDENT_TRACK);
            }
        }
        // First attempt at trackId
        if ((! $this->trackId) && $this->trackEngine) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        // Check if a sufficient set of data is there
        if (! ($this->trackId || $this->patientId || $this->organizationId)) {
            // Now we really need $this->respondentTrack
            if (! $this->respondentTrack) {
                if ($this->respondentTrackId) {
                    $this->respondentTrack = $this->loader->getTracker()->getRespondentTrack($this->respondentTrackId);
                } else {
                    // Parameters not valid
                    return false;
                }
            }
        }

        if (! $this->trackId) {
            $this->trackId = $this->respondentTrack->getTrackId();
        }
        if (! $this->patientId) {
            $this->patientId = $this->respondentTrack->getPatientNumber();
        }
        if (! $this->organizationId) {
            $this->organizationId = $this->respondentTrack->getOrganizationId();
        }

        // \MUtil\EchoOut\EchoOut::track($this->trackId, $this->patientId, $this->organizationId, $this->respondentTrackId);

        return $this->getModel()->loadFirst() && parent::hasHtmlOutput();
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil\Model\ModelAbstract $model)
    {
        if ($this->requestInfo) {
            $this->processSortOnly($model);
        }

        $filter['gtr_id_track']         = $this->trackId;
        $filter['gr2o_patient_nr']      = $this->patientId;
        $filter['gr2o_id_organization'] = $this->organizationId;

        if ($this->excludeCurrent) {
            $filter[] = $this->db->quoteInto('gr2t_id_respondent_track != ?', $this->respondentTrackId);
        }

        $model->setFilter($filter);
    }
}
