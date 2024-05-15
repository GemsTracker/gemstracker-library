<?php

/**
 * @package    Gems
 * @subpackage Snippets\Export
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
use Gems\Snippets\AutosearchFormSnippet;
use Gems\Snippets\AutosearchPeriodFormSnippet;
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
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        PeriodSelectRepository $periodSelectRepository,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly RespondentModel $respondentModel,
    ) {
        parent::__construct(
            $snippetOptions,
            $requestInfo,
            $translate,
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
}
