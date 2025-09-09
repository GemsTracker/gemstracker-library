<?php

/**
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

use Gems\Config\ConfigAccessor;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\TokenRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\AutosearchPeriodFormSnippet;
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
 * @since      Class available since version 1.8.2
 */
abstract class SurveyExportSearchFormSnippetAbstract extends AutosearchPeriodFormSnippet
{
//    public const SURVEY_ACTIVE          = 'Sctive';
//    public const SURVEY_INACTIVE        = 'inactive';
//    public const SURVEY_SOURCE_INACTIVE = 'source inactive';

	/**
     * Defines the value used for 'no round description'
     *
     * It this value collides with a used round description, change it to something else
     */
    const NoRound = '-1';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        ConfigAccessor $configAccessor,
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        PeriodSelectRepository $periodSelectRepository,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly RespondentModel $respondentModel,
        protected readonly TrackDataRepository $trackDataRepository,
        protected readonly Tracker $tracker,
    ) {
        parent::__construct(
            $snippetOptions,
            $requestInfo,
            $translate,
            $configAccessor,
            $menuSnippetHelper,
            $metaModelLoader,
            $resultFetcher,
            $messenger,
            $periodSelectRepository
        );
    }

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data): array
    {
        $elements = $this->getSurveySelectElements($data);

        $elements[] = null;

        $organizations = $this->currentUserRepository->getCurrentUser()->getRespondentOrganizations();
        if (count($organizations) > 1) {
            $elements['gto_id_organization'] = $this->_createMultiCheckBoxElements('gto_id_organization', $organizations);

            $elements[] = null;
        }

        $dates = array(
            'gr2t_start_date'     => $this->_('Track start'),
            'gr2t_end_date'       => $this->_('Track end'),
            'gto_valid_from'      => $this->_('Valid from'),
            'gto_valid_until'     => $this->_('Valid until'),
            'gto_start_time'      => $this->_('Start date'),
            'gto_completion_time' => $this->_('Completion date'),
            );
        // $dates = 'gto_valid_from';
        $this->addPeriodSelectors($elements, $dates, 'gto_valid_from');

        $elements[] = null;

        $element = $this->form->createElement('textarea', 'ids');
        $element->setLabel($this->_('Respondent id\'s'))
            ->setAttrib('cols', 60)
            ->setAttrib('rows', 4)
            ->setDescription($this->_("Not respondent nr, but respondent id as exported here. Separate multiple id's with , or ;"));
        $elements['ids'] = $element;

        $elements[] = null;

        $elements['export_trackfield_codes'] = $this->form->createElement('text', 'export_trackfield_codes',
            [
                'size' => 50,
                'label' => 'Export trackfield codes',
                'description' => 'Export specific trackfield codes. This works without selecting a track. The columnname will be the code. Use , as a separator between codes.'
            ]
        );

        $elements[] = null;

        $elements[] = $this->_('Output');
        $elements['incomplete'] = $this->_createCheckboxElement(
                'incomplete',
                $this->_('Include incomplete surveys'),
                $this->_('Include surveys that have been started but have not been checked as completed')
                );
        $elements['column_identifiers'] = $this->_createCheckboxElement(
                'column_identifiers',
                $this->_('Column Identifiers'),
                $this->_('Prefix the column labels with an identifier. (A) Answers, (TF) Trackfields, (D) Description')
                );
        $elements[] = $this->_(' For subquestions ');
        $elements['subquestions'] = $this->_createRadioElement(
                'subquestions', [
                    'show_parent'  => $this->_('show parent as separate question'),
                    'prefix_child' => $this->_('add parent to each subquestion')
                ])->setSeparator(' ');
        /*$elements['show_parent'] = $this->_createCheckboxElement(
                'show_parent',
                $this->_('Show parent'),
                $this->_('Show the parent column even if it doesn\'t have answers')
                );
        $elements['prefix_child'] = $this->_createCheckboxElement(
                'prefix_child',
                $this->_('Prefix child'),
                $this->_('Prefix the child column labels with parent question label')
                );
         */
        $elements[] = null;

        $extraFields = $this->getExtraFieldElements($data);

        if ($extraFields) {
            $elements[] = $this->_('Add to export');
            $elements = $elements + $extraFields;
            $elements[] = null;
        }

        $elements['export'] = $this->form->createElement('submit', 'export', array('label' => $this->_('Export'), 'class' => 'button larger'));
        $elements['export']->setAttrib('formaction', $this->menuSnippetHelper->getRelatedRouteUrl('generate'));
        if (! $this->isExportable($data)) {
            $elements['export']->setAttrib('disabled', 'disabled');
        }
        return $elements;
    }

	/**
     * Returns extra field elements for auto search.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be useful, but no need to set them)
     * @return array Of \Zend_Form_Element's or static text to add to the html or null for group breaks.
     */
    protected function getExtraFieldElements(array $data): array
    {
        if (isset($data['gto_id_track']) && $data['gto_id_track']) {
            $elements['add_track_fields'] = $this->_createCheckboxElement(
                'add_track_fields',
                $this->_('Track fields'),
                $this->_('Add track fields to export')
            );
        }
        if ($this->currentUserRepository->getCurrentUser()->hasPrivilege('pr.export.add-resp-nr')) {
            $elements['export_resp_nr'] = $this->_createCheckboxElement(
                'export_resp_nr',
                $this->respondentModel->getMetaModel()->get('gr2o_patient_nr', 'label'),
                $this->_('Add respondent nr to export')
            );
        }
        if ($this->currentUserRepository->getCurrentUser()->hasPrivilege('pr.export.gender-age')) {
            $elements['export_resp_gender'] = $this->_createCheckboxElement(
                'export_resp_gender',
                $this->_('Respondent gender'),
                $this->_('Add respondent gender to export')
            );

            $elements['export_birth_year'] = $this->_createCheckboxElement(
                'export_birth_year',
                $this->_('Respondent birth year'),
                $this->_('Add respondent birth year to export')
            );

            $elements['export_birth_month'] = $this->_createCheckboxElement(
                'export_birth_month',
                $this->_('Respondent birth month'),
                $this->_('Add respondent birth month to export')
            );

            $elements['export_birth_yearmonth'] = $this->_createCheckboxElement(
                'export_birth_yearmonth',
                $this->_('Respondent birth year/month'),
                $this->_('Add respondent birth year and month to export')
            );
        }

        $elements['export_track_reception_code'] = $this->_createCheckboxElement(
            'export_track_reception_code',
            $this->_('Track reception code'),
            $this->_('Add reception code of track')
        );

        $elements['export_token_reception_code'] = $this->_createCheckboxElement(
            'export_token_reception_code',
            $this->_('Token reception code'),
            $this->_('Add reception code of token')
        );

        return $elements;
    }

    protected function getRoundsForExport(int|null $trackId = null, int|array|null $surveyId = null): array
    {
        // Read some data from tables, initialize defaults...
        // Fetch all round descriptions
        $select = $this->resultFetcher->getSelect('gems__tokens');
        $select->columns(['gto_round_description', 'gto_round_description'])
            ->quantifier($select::QUANTIFIER_DISTINCT)
            ->order(['gto_round_description']);
        $select->where->isNotNull('gto_round_description')->notEqualTo('gto_round_description', '');

        if (!empty($trackId)) {
            $select->where(['gto_id_track' => $trackId]);
        }

        if (!empty($surveyId)) {
            $select->where(['gto_id_survey' => $surveyId]);
        }

        return $this->resultFetcher->fetchPairs($select);
    }

    public function getSurveysForExport(int|null $trackId = null, string|null $roundDescription = null): array
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
            $subSelect->where(['gto_round_description' => $roundDescription]);
        }
        if ($trackId) {
            $addSubSelect = true;
            $subSelect->where(['gto_id_track' => $trackId]);
        }

        if ($addSubSelect) {
            $subSelect->columns(['gto_id_survey']);
            $select->where->in('gsu_id_survey', $subSelect);
        }
        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  $select->getSqlString($this->resultFetcher->getPlatform()) . "\n", FILE_APPEND);

        $result  = $this->resultFetcher->fetchAll($select);

        $surveys = [];
        if ($result) {
            // And transform to have inactive surveys in gems and source in a
            // different group at the bottom
            //
            // Not loading the survey objects speeds up this code by two seconds
            $active = $this->_('Active');
            $inactive = $this->_('Inactive');
            $sourceInactive = $this->_('Source inactive');
            $sources = [];
            foreach ($result as $surveyData) {
                $id   = $surveyData['gsu_id_survey'];
                $name = $surveyData['gsu_survey_name'];

                if (1 != $surveyData['gsu_surveyor_active'])  {
                    $sourceId = $surveyData['gsu_id_source'];
                    if (! isset($sources[$sourceId])) {
                        $sources[$sourceId] = $this->tracker->getSource($sourceId);
                    }
                    if ($sources[$sourceId]->canExportInactive()) {
                        $surveys[$sourceInactive][$id] = $name;
                    }
                } elseif (1 != $surveyData['gsu_active']) {
                    $surveys[$inactive][$id] = $name;
                } else {
                    $surveys[$active][$id] = $name;
                }
            }
//            foreach ($result as $surveyData) {
//                $survey = $this->tracker->getSurvey($surveyData);
//
//                $id   = $surveyData['gsu_id_survey'];
//                $name = $survey->getName();
//                if (! $survey->isActiveInSource()) {
//                    // Inactive in the source, for LimeSurvey this is a problem!
//                    if ($keepSourceInactive || $survey->getSource()->canExportInactive()) {
//                        if ($flat) {
//                            $surveys[$id] = $name . " ($sourceInactive) ";
//                        } else {
//                            $surveys[self::SURVEY_SOURCE_INACTIVE][$id] = $name;
//                        }
//                    }
//                } elseif (!$survey->isActive()) {
//                    if ($flat) {
//                        $surveys[$id] = $name . " ($inactive) ";
//                    } else {
//                        $surveys[self::SURVEY_INACTIVE][$id] = $name;
//                    }
//                } else {
//                    if ($flat) {
//                        $surveys[$id] = $name;
//                    } else {
//                        $surveys[self::SURVEY_ACTIVE][$id] = $name;
//                    }
//                }
//            }
        }
        ksort($surveys);
        return $surveys;
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
    abstract protected function getSurveySelectElements(array $data);

    abstract protected function isExportable(array $data): bool;
}
