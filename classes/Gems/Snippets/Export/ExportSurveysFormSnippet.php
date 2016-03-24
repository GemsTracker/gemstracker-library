<?php

namespace Gems\Snippets\Export;

use MUtil\Snippets\SnippetAbstract;

class ExportSurveysFormSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * Optional string format for date
     *
     * @var string
     */
    protected $dateFormat;

    public $currentUser;

    protected $form;

	public $loader;

    /**
     * Defines the value used for 'no round description'
     *
     * It this value collides with a used round description, change it to something else
     */
    const NoRound = '-1';

    public $request;

    public $util;

	public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $post = $this->request->getPost();

        $currentTrack = null;
        if (isset($post['gto_id_track'])) {
            $currentTrack = $post['gto_id_track'];
        }
        $currentRound = null;
        if (isset($post['gto_round_description'])) {
            $currentRound = $post['gto_round_description'];
        }

        $export        = $this->loader->getExport();
        $exportTypes   = $export->getExportClasses();
        
        if (isset($post['type'])) {
            $currentType = $post['type'];
        } else {
            reset($exportTypes);
            $currentType = key($exportTypes);
        }

        $dbLookup      = $this->util->getDbLookup();
        $translated    = $this->util->getTranslated();
        $noRound       = array(self::NoRound => $this->_('No round description'));
        $empty         = $translated->getEmptyDropdownArray();
        $tracks        = $empty + $this->util->getTrackData()->getAllTracks();
        $rounds        = $empty + $noRound + $dbLookup->getRoundsForExport($currentTrack);

        $surveys       = $dbLookup->getSurveysForExport($currentTrack, $currentRound, true);

        $organizations = $this->currentUser->getRespondentOrganizations();

        if (\MUtil_Bootstrap::enabled()) {
            $this->form = new \Gems_Form(array('class' => 'form-horizontal'));
        } else {
            $this->form = new \Gems_Form_TableForm();
        }

        $url = \MUtil_Html::attrib('href', array('action' => 'index', 'step' => 'batch'));
        $this->form->setAction($url);

        $elements = array();

        $elements['gto_id_track'] = $this->form->createElement('select',
            'gto_id_track',
            array('label' => $this->_('Track'), 'multiOptions' => $tracks, 'class' => 'autosubmit')
            );

        $elements['gto_round_description'] = $this->form->createElement('select',
            'gto_round_description',
            array('label' => $this->_('Round'), 'multiOptions' => $rounds, 'class' => 'autosubmit')
            );

        $elements['gto_id_survey'] = $this->form->createElement('multiCheckbox',
            'gto_id_survey',
            array('label' => $this->_('Survey'), 'multiOptions' => $surveys)
            );

        if (count($organizations) > 1) {
            $elements['gto_id_organization'] = $this->form->createElement('multiCheckbox',
                'gto_id_organization',
                array('label' => $this->_('Organizations'), 'multiOptions' => $organizations)
                );   

            if (\MUtil_Bootstrap::enabled()) {
                $element = new \MUtil_Bootstrap_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=gto_id_organization]'));
            } else {
                $element = new \Gems_JQuery_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=gto_id_organization]'));
            }

            $element->setLabel($this->_('Toggle'));
            $elements[] = $element;
        }

        $dates = array(
            'gto_start_date' => $this->_('Track start'),
            'gto_end_date'   => $this->_('Track end'),
            'gto_valid_from'  => $this->_('Valid from'),
            'gto_valid_until' => $this->_('Valid until'),
            );
        // $dates = 'gto_valid_from';
        $periodElements = $this->getPeriodSelectors($dates, 'gto_valid_from');

        $elements += $periodElements;

        $element = $this->form->createElement('checkbox', 'column_identifiers');
        $element->setLabel($this->_('Column Identifiers'));
        $element->setDescription($this->_('Prefix the column labels with an identifier. (A) Answers, (TF) Trackfields, (D) Description'));
        $elements['column_identifiers'] = $element;

        $element = $this->form->createElement('checkbox', 'show_parent');
        $element->setLabel($this->_('Show parent'));
        $element->setDescription($this->_('Show the parent column even if it doesn\'t have answers'));
        $elements['show_parent'] = $element;

        $element = $this->form->createElement('checkbox', 'prefix_child');
        $element->setLabel($this->_('Prefix child'));
        $element->setDescription($this->_('Prefix the child column labels with parent question label'));
        $elements['prefix_child'] = $element;

        $elements['type'] = $this->form->createElement('select', 'type', array('label' => $this->_('Export to'), 'multiOptions' => $exportTypes, 'class' => 'autosubmit'));

        $this->form->addElements($elements);

        $exportClass = $export->getExport($currentType);
        $exportName = $exportClass->getName();
        $exportFormElements = $exportClass->getFormElements($this->form, $data);
        $exportFormElements['firstCheck'] = $this->form->createElement('hidden', $currentType);
        $this->form->addElements($exportFormElements);

        if (!isset($post[$currentType])) {
            $post[$exportName] = $exportClass->getDefaultFormValues();
        }

        $element = $this->form->createElement('submit', 'export_submit', array('label' => $this->_('Export')));
        $this->form->addElement($element);

        if ($post) {
            $this->form->populate($post);
        }

        $container = \MUtil_Html::div(array('id' => 'export-surveys-form'));
        $container->append($this->form);
        $this->form->setAttrib('id', 'autosubmit');
        $this->form->setAutoSubmit(\MUtil_Html::attrib('href', array('action' => 'index', 'RouteReset' => true)), 'export-surveys-form', true);

        return $container;
    }

    /**
     * Generate two date selectors and - depending on the number of $dates passed -
     * either a hidden element containing the field name or an radio button or
     * dropdown selector for the type of date to use.
     *
     * @param mixed $dates A string fieldName to use or an array of fieldName => Label
     * @param string $defaultDate Optional element, otherwise first is used.
     * @param int $switchToSelect The number of dates where this function should switch to select display
     */
    protected function getPeriodSelectors($dates, $defaultDate = null, $switchToSelect = 4)
    {
        $elements = array();
        if (is_array($dates) && (1 === count($dates))) {
            $fromLabel = reset($dates);
            $dates = key($dates);
        } else {
            $fromLabel = $this->_('From');
        }
        if (is_string($dates)) {
            $element = $this->form->createElement('hidden', 'dateused');
            $element->setValue($dates);
        } else {
            if (count($dates) >= $switchToSelect) {
                $element = $this->form->createElement('select', 'dateused', array('label' => $this->_('For date'), 'multiOptions' => $dates));
                $fromLabel = '';
            } else {
                $element = $this->form->createElement('radio', 'dateused', array('label' => $this->_('For date'), 'multiOptions' => $dates));
                $element->setSeparator(' ');
                $fromLabel = html_entity_decode(' &raquo; ',  ENT_QUOTES, 'UTF-8');
            }
            $fromLabel .= $this->_('from');

            if ((null === $defaultDate) || (! isset($dates[$defaultDate]))) {
                // Set value to first key
                reset($dates);
                $defaultDate = key($dates);
            }
            $element->setValue($defaultDate);
        }
        $elements['dateused'] = $element;

        $type = 'date';
        if ($this->dateFormat) {
            $options['dateFormat'] = $this->dateFormat;
            list($dateFormat, $separator, $timeFormat) = \MUtil_Date_Format::splitDateTimeFormat($options['dateFormat']);

            if ($timeFormat) {
                if ($dateFormat) {
                    $type = 'datetime';
                } else {
                    $type = 'time';
                }
            }
        }
        $options['label'] = $fromLabel;
        \MUtil_Model_Bridge_FormBridge::applyFixedOptions($type, $options);

        $elements['datefrom'] = new \Gems_JQuery_Form_Element_DatePicker('datefrom', $options);

        $options['label'] = ' ' . $this->_('until');
        $elements['dateuntil'] = new \Gems_JQuery_Form_Element_DatePicker('dateuntil', $options);

        return $elements;
    }
}