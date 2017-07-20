<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 04-Jul-2017 19:06:01
 */
class MultiSurveysSearchFormSnippet extends SurveyExportSearchFormSnippetAbstract
{
    /**
     *
     * @var \Gems_Util_BasePath
     */
    protected $basepath;

    /**
     * Creates the form itself
     *
     * @param array $options
     * @return \Gems_Form
     * /
    protected function createForm($options = null)
    {
        $form = parent::createForm($options);

        $form->setAction($this->basepath->getBasePath() . '/' .
                $this->request->getControllerName() . '/' .
                $this->request->getActionName() .
                '/step/batch');

        return $form;
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
    protected function getAutoSearchElements(array $data)
    {
        $elements = parent::getAutoSearchElements($data);

        $elements = $elements + $this->getExportTypeElements($data);
        $elements[] = null;

        return $elements;
    }

    /**
     * Creates a submit button
     *
     * @return \Zend_Form_Element_Submit
     */
    protected function getAutoSearchSubmit()
    {
        return $this->form->createElement('submit', 'step', array('label' => $this->_('Export'), 'class' => 'button small'));
    }

	/**
     * Returns export field elements for auto search.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getExportTypeElements(array $data)
    {
        $export = $this->loader->getExport();
        $exportTypes = $export->getExportClasses();

        if (isset($data['type'])) {
            $currentType = $data['type'];
        } else {
            reset($exportTypes);
            $currentType = key($exportTypes);
        }

        $elements['type_label'] = $this->_('Export to');

        $elements['type'] = $this->_createSelectElement( 'type', $exportTypes);
        $elements['type']->setAttrib('onchange', 'this.form.submit();');
        // $elements['step'] = $this->form->createElement('hidden', 'step');;

        $exportClass = $export->getExport($currentType);
        $exportName  = $exportClass->getName();
        $exportFormElements = $exportClass->getFormElements($this->form, $data);

        if ($exportFormElements) {
            $elements['firstCheck'] = $this->form->createElement('hidden', $currentType);
            foreach ($exportFormElements as $key => $formElement) {
                $elements['type_br_' . $key] = \MUtil_Html::create('br');
                $elements['type_el_' . $key] = $formElement;
            }
        }

//        if (!isset($data[$currentType])) {
//            $data[$exportName] = $exportClass->getDefaultFormValues();
//        }

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
    protected function getSurveySelectElements(array $data)
    {
        $dbLookup      = $this->util->getDbLookup();
        $translated    = $this->util->getTranslated();
        // $noRound       = array(self::NoRound => $this->_('No round description'));
        // $empty         = $translated->getEmptyDropdownArray();

        // get the current selections
        $roundDescr = isset($data['gto_round_description']) ? $data['gto_round_description'] : null;
        // $surveyIds  = isset($data['gto_id_survey']) ? $data['gto_id_survey'] : null;
        $trackId    = isset($data['gto_id_track']) ? $data['gto_id_track'] : null;

        // Get the selection data
        $rounds  = $dbLookup->getRoundsForExport($trackId);
        $tracks  = $this->util->getTrackData()->getTracksForOrgs($this->currentUser->getRespondentOrganizations());
        $surveys = $dbLookup->getSurveysForExport($trackId, $roundDescr, true);

        $elements['gto_id_track'] = $this->_createSelectElement(
                'gto_id_track',
                $tracks,
                $this->_('(any track)')
                );
        $elements['gto_id_track']->setAttrib('onchange', 'this.form.submit();');

        $elements[] = \MUtil_Html::create('br');

       	$elements['gto_round_description'] = $this->_createSelectElement(
                'gto_round_description',
                [self::NoRound => $this->_('No round description')] + $rounds,
                $this->_('(any round)')
                );
        $elements['gto_round_description']->setAttrib('onchange', 'this.form.submit();');

        $elements[] = \MUtil_Html::create('br');

        $elements[] = $this->_createMultiCheckBoxElements('gto_id_survey', $surveys);

        return $elements;
    }
}
