<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\FilterTracer;
use Gems\Snippets\FormSnippetAbstract;
use Gems\Tracker\Engine\FieldsDefinition;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 07-Jan-2020 11:49:55
 */
class AppointmentCheckSnippet extends FormSnippetAbstract
{
    /**
     *
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     *
     * @var \Gems_Agenda_Appointment
     */
    protected $appointment;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The form Id used for the save button
     *
     * If empty save button is not added
     *
     * @var string
     */
    protected $saveButtonId = null;

    /**
     *
     * @var \Gems\Agenda\FilterTracer
     */
    protected $tracer;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        $options = [
            'label' => $this->_('Check mode:'),
            'description' => $this->_('This option can change tracks!'),
            'multiOptions' => $this->getModeOptions(),
            'onchange' => 'this.form.submit();',
            //'separator' => ' ',
            ];

        $keyElement = $form->createElement('Radio', 'runmode', $options);
        $form->addElement($keyElement);

        $form->removeDecorator('AutoFocus');
        $form->removeElement($form->focusTrackerElementId);
        $form->focusTrackerElementId = null;

        $form->setAttrib('class', 'form-inline');
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->agenda      = $this->loader->getAgenda();
        $this->appointment = $this->agenda->getAppointment($this->request->getParam(\Gems_Model::APPOINTMENT_ID));
        $this->tracer      = new FilterTracer();
        $this->tracker     = $this->loader->getTracker();

        $this->appointment->setFilterTracer($this->tracer);
    }

    /**
     *
     * @param \MUtil_Html_Sequence $seq
     */
    protected function appendCheckedTracks(\MUtil_Html_Sequence $seq)
    {
        $translated = $this->util->getTranslated();

        $tracks = $this->tracer->getTracks();
        $seq->h2($this->_('Existing tracks check'));
        if ($tracks) {
            $baseUrl       = [
                'gr2o_patient_nr'      => $this->appointment->getPatientNumber(),
                'gr2o_id_organization' => $this->appointment->getOrganizationId(),
                ];
            $list          = $seq->ol();
            $menuRespTrack = $this->menu->findAllowedController('track', 'show-track');

            foreach ($tracks as $respTrackId => $trackData) {
                $li = $list->li();

                if ($menuRespTrack) {
                    $li->em()->a(
                            $menuRespTrack->toHRefAttribute($baseUrl + ['gr2t_id_respondent_track' => $respTrackId]),
                            $trackData['trackName']
                            );
                } else {
                    $li->em($trackData['trackName']);
                }
                if ($trackData['trackStart'] instanceof \Zend_Date) {
                    $startDate = $translated->formatDateTime($trackData['trackStart']);
                } else {
                    $startDate = $this->_('startdate unknown');
                }

                if ($trackData['trackInfo']) {
                    $li->append(sprintf($this->_(' [%s, starttime %s]'), $trackData['trackInfo'], $startDate));
                } else {
                    $li->append(sprintf($this->_(' [starttime %s]'), $startDate));
                }
                $li->append(' ');
                if ($this->tracer->executeChanges) {
                    if ($trackData['fieldsChanged']) {
                        $li->strong($this->_('fields were changed'));
                    } else {
                        $li->append($this->_('no fields changed'));
                    }
                    $li->append(' ');
                    if ($trackData['tokensChanged']) {
                        $li->em(sprintf(
                                $this->plural('%d token changed', '%d tokens changed', $trackData['tokensChanged']),
                                $trackData['tokensChanged']));
                    } else {
                        $li->append($this->_('no tokens changed'));
                    }
                } else {
                    $li->append($this->_('no checks executed'));
                }
            }
        } else {
            $seq->pInfo($this->_('No tracks to check'));
        }
    }

    /**
     *
     * @param \MUtil_Html_Sequence $seq
     */
    protected function appendFiltersChecked(\MUtil_Html_Sequence $seq)
    {
        $seq->h2($this->_('Creation by filter check'));

        $filters = $this->tracer->getFilters();
        if ($filters) {
            $baseUrl       = [
                'gr2o_patient_nr'      => $this->appointment->getPatientNumber(),
                'gr2o_id_organization' => $this->appointment->getOrganizationId(),
                ];
            $list          = $seq->ol();
            $menuField     = $this->menu->findAllowedController('track-fields', 'show');
            $menuFilter    = $this->menu->findAllowedController('agenda-filter', 'show');
            $menuRespTrack = $this->menu->findAllowedController('track', 'show-track');
            $menuTrack     = $this->menu->findAllowedController('track-maintenance', 'show');

            foreach ($filters as $filterId => $filterData) {
                $li = $list->li();
                $track = $this->tracker->getTrackEngine($filterData['filterTrack']);
                $fields = $track->getFieldNames();
                $field  = isset($fields[$filterData['filterField']]) ? $fields[$filterData['filterField']] : null;

                $li->append(sprintf($this->_('%s: '), ucfirst($this->_('filter'))));
                if ($menuFilter) {
                    $li->em()->a(
                            $menuFilter->toHRefAttribute([\MUtil_Model::REQUEST_ID => $filterId]),
                            $filterData['filterName']
                            );
                } else {
                    $li->em($filterData['filterName']);
                }
                $li->append(sprintf($this->_(', %s: '), $this->plural('track', 'tracks', 1)));
                if ($menuTrack) {
                    $li->em()->a(
                            $menuTrack->toHRefAttribute([\MUtil_Model::REQUEST_ID => $filterData['filterTrack']]),
                            $track->getTrackName()
                            );
                } else {
                    $li->em($track->getTrackName());
                }
                if ($field) {
                    $li->append(sprintf($this->_(', %s: '), $this->plural('field', 'fields', 1)));
                    if ($menuField) {
                        $li->em()->a(
                                $menuField->toHRefAttribute(
                                        ['gtf_id_track' => $filterData['filterTrack']] +
                                        FieldsDefinition::splitKey($filterData['filterField'])
                                        ),
                                $field
                                );
                    } else {
                        $li->em($field);
                    }
                }
                $li->br();

                if ($filterData['createTrack']) {
                    if ($this->tracer->executeChanges) {
                        if ($menuRespTrack && $filterData['respTrackId']) {
                            $li->em()->a(
                                    $menuRespTrack->toHRefAttribute($baseUrl + [
                                        'gr2t_id_respondent_track' => $filterData['respTrackId'],
                                        ]),
                                    $this->_('track was created!')
                                    );
                        } else {
                            $li->em($this->_('track was created!'));
                        }
                    } else {
                        $li->em($this->_('track would be created!'));
                    }
                } elseif ($filterData['skipMessage']) {
                    $li->strong($this->_('track not created because: '));
                    if ($menuRespTrack && $filterData['respTrackId']) {
                        $li->em()->a(
                                $menuRespTrack->toHRefAttribute($baseUrl + [
                                    'gr2t_id_respondent_track' => $filterData['respTrackId'],
                                    ]),
                                $filterData['skipMessage']
                                );
                    } else {
                        $li->em($filterData['skipMessage']);
                    }
                    $li->append('.');
                } else {
                    $li->em($this->_('track not created, reason unknown!'));
                }
            }
        } else {
            $seq->pInfo($this->_('No creation filters to check'));
        }
    }

    /**
     *
     * @param \MUtil_Html_Sequence $seq
     */
    protected function appendFiltersSkipped(\MUtil_Html_Sequence $seq)
    {
        $seq->h2($this->_('Creation by filter check skipped'));
        if ($this->appointment->isActive()) {
            $seq->pInfo($this->_('Check skipped because the appointment is in the past.'));
        } else {
            $seq->pInfo($this->_('Check skipped because the appointment is not active.'));
        }
    }

    /**
     *
     * @param \MUtil_Html_Sequence $seq
     */
    protected function appendFormInfo(\MUtil_Html_Sequence $seq)
    {
        $seq->br();

        $options = $this->getModeOptions();

        $seq->div(sprintf(
                $this->_("'%s' shows what will be checked, clicking on '%s' will perform actual changes!'"),
                $options[0],
                $options[1]
                ), ['class' => 'alert alert-danger', 'role' => 'alert']);
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues()
    {
        return ['runmode' => 0];
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
        // \MUtil_Echo::track('Hi');
        $form = parent::getHtmlOutput($view);

        $seq = $this->getHtmlSequence();
        $seq->append($form);

        $this->appendFormInfo($seq);

        $this->tracer->executeChanges = $this->formData['runmode'];

        $this->appointment->updateTracks();

        $seq->h1($this->_('Result'));
        $this->appendCheckedTracks($seq);

        if ($this->tracer->getSkippedFilterCheck()) {
            $this->appendFiltersSkipped($seq);
        } else {
            $this->appendFiltersChecked($seq);
        }

        $seq->append(parent::getMenuList());
        return $seq;
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        return null;
    }

    /**
     *
     * @return array
     */
    protected function getModeOptions()
    {
        return [
            0 => $this->_('Just check'),
            1 => $this->_('Check and apply'),
            ];
    }

    /**
     * When hasHtmlOutput() is false a snippet user should check
     * for a redirectRoute.
     *
     * When hasHtmlOutput() is true this functions should not be called.
     *
     * @see Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    {
        return false;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return null;
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * #param array $params Url items to set for this route
     * @return MUtil_Snippets_ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute(array $params = array())
    {
        $this->afterSaveRouteUrl = false;

        return $this;
    }
}
