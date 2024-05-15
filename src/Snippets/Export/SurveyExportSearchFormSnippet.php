<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\TokenRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Tracker;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.0
 */
class SurveyExportSearchFormSnippet extends SurveyExportSearchFormSnippetAbstract
{
    public const SURVEY_ACTIVE          = 'active';
    public const SURVEY_INACTIVE        = 'inactive';
    public const SURVEY_SOURCE_INACTIVE = 'source inactive';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        PeriodSelectRepository $periodSelectRepository,
        CurrentUserRepository $currentUserRepository,
        RespondentModel $respondentModel,
        protected readonly TrackDataRepository $trackDataRepository,
        protected readonly Tracker $tracker,
    ) {
        parent::__construct(
            $snippetOptions,
            $requestInfo,
            $translate,
            $menuSnippetHelper,
            $metaModelLoader,
            $resultFetcher,
            $messenger,
            $periodSelectRepository,
            $currentUserRepository,
            $respondentModel
        );
    }

    /**
     * Returns start elements for auto search.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getSurveySelectElements(array $data): array
    {
        // get the current selections
        $roundDescr = isset($data['gto_round_description']) ? $data['gto_round_description'] : null;
        $surveyId   = isset($data['gto_id_survey']) ? $data['gto_id_survey'] : null;
        $trackId    = isset($data['gto_id_track']) ? $data['gto_id_track'] : null;

        // Get the selection data
        $rounds = $this->getRoundsForExport($trackId, $surveyId);
        $surveys = $this->getSurveysForExport($trackId, $roundDescr);
        if ($surveyId) {
            $tracks = $this->trackDataRepository->getTracksBySurvey($surveyId);
        } else {
            $tracks = $this->trackDataRepository->getTracksForOrgs($this->currentUserRepository->getCurrentUser()->getRespondentOrganizations());
        }

        $elements['gto_id_survey'] = $this->_createSelectElement(
            'gto_id_survey',
            $surveys,
            $this->_('(select a survey)')
            );
        $elements['gto_id_track'] = $this->_createSelectElement(
                'gto_id_track',
                $tracks,
                $this->_('(select a track)')
                );
       	$elements['gto_round_description'] = $this->_createSelectElement(
                'gto_round_description',
                [parent::NoRound => $this->_('No round description')] + $rounds,
                $this->_('(select a round)')
                );

        foreach ($elements as $element) {
            if ($element instanceof \Zend_Form_Element_Multi) {
                $element->setAttrib('class', 'auto-submit');
            }
        }

        return $elements;
   }

   protected function getRoundsForExport(int|null $trackId = null, int|null $surveyId = null): array
    {
        // Read some data from tables, initialize defaults...
        // Fetch all round descriptions
        $select = $this->resultFetcher->getSelect('gems__tokens');
        $select->columns(['gto_round_description', 'gto_round_description'])
            ->quantifier($select::QUANTIFIER_DISTINCT)
            ->order(['gto_round_description']);
        $select->where->isNotNull('gto_round_description')->notEqualTo('gto_round_description', '');

        if (!empty($trackId)) {
            $select->where(['gto_id_track', $trackId]);
        }

        if (!empty($surveyId)) {
            $select->where(['gto_id_survey', $surveyId]);
        }

        return $this->resultFetcher->fetchPairs($select);
    }

    public function getSurveysForExport(int|null $trackId = null, string|null $roundDescription = null, bool $flat = false, bool $keepSourceInactive = false): array
    {
        // Read some data from tables, initialize defaults...
        $select = $this->resultFetcher->getSelect('gems__surveys');

        // Fetch all surveys
        $select
            ->join('gems__sources', 'gsu_id_source = gso_id_source')
            ->where([
                'gso_active' => 1,
                'gsu_allow_export' => 1,

            ])
            ->where('gsu_allow_export = 1')
            ->where(TokenRepository::getShowAnswersExpression($this->currentUserRepository->getCurrentUser()->getGroupId(true)))
            //->where('gsu_surveyor_active = 1')
            // Leave inactive surveys, we toss out the inactive ones for limesurvey
            // as it is no problem for OpenRosa to have them in
            ->order(['gsu_active DESC', 'gsu_surveyor_active DESC', 'gsu_survey_name']);

        $subSelect = $this->resultFetcher->getSelect('gems__tokens');

        $addSubSelect = false;
        if ($roundDescription) {
            $addSubSelect = true;
            $subSelect->where(['gsu_round_description' => $roundDescription]);
        }
        if ($trackId) {
            $addSubSelect = true;
            $subSelect->where(['gto_id_track' => $trackId]);
        }

        if ($addSubSelect) {
            $select->where->in('gsu_id_survey', $subSelect);
        }

        $result  = $this->resultFetcher->fetchAll($select);

        $surveys = [];
        if ($result) {
            // And transform to have inactive surveys in gems and source in a
            // different group at the bottom
            $inactive = $this->_('inactive');
            $sourceInactive = $this->_('source inactive');
            foreach ($result as $surveyData) {
                $survey = $this->tracker->getSurvey($surveyData);

                $id   = $surveyData['gsu_id_survey'];
                $name = $survey->getName();
                if (! $survey->isActiveInSource()) {
                    // Inactive in the source, for LimeSurvey this is a problem!
                    if ($keepSourceInactive || $survey->getSource()->canExportInactive()) {
                        if ($flat) {
                            $surveys[$id] = $name . " ($sourceInactive) ";
                        } else {
                            $surveys[self::SURVEY_SOURCE_INACTIVE][$id] = $name;
                        }
                    }
                } elseif (!$survey->isActive()) {
                    if ($flat) {
                        $surveys[$id] = $name . " ($inactive) ";
                    } else {
                        $surveys[self::SURVEY_INACTIVE][$id] = $name;
                    }
                } else {
                    if ($flat) {
                        $surveys[$id] = $name;
                    } else {
                        $surveys[self::SURVEY_ACTIVE][$id] = $name;
                    }
                }
            }
        }

        return $surveys;
    }
}