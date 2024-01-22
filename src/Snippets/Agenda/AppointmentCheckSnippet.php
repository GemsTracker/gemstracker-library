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

use Gems\Agenda\Agenda;
use Gems\Agenda\FilterTracer;
use Gems\Audit\AuditLog;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Snippets\FormSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Util\Translated;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Sequence;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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
     * @var \Gems\Agenda\Appointment
     */
    protected $appointment;

    /**
     *
     * @var \Gems\Loader
     */
//    protected $loader;

    /**
     * The form Id used for the save button
     *
     * If empty save button is not added
     *
     * @var string
     */
    protected $saveButtonId = '';

    /**
     *
     * @var \Gems\Agenda\FilterTracer
     */
    protected $tracer;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        protected readonly Agenda $agenda,
        protected readonly Tracker $tracker,
        protected readonly Translated $translatedUtil,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);

        $this->tracer = new FilterTracer();

        $appId = $this->requestInfo->getParam(Model::APPOINTMENT_ID);
        if ($appId) {
            $this->appointment = $this->agenda->getAppointment($appId);
        }
    }

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(mixed $form)
    {
        /**
         * @var \Zend_Form $form
         */
        $options = [
            'label' => $this->_('Check mode:'),
            'description' => $this->_('This option can change tracks!'),
            'multiOptions' => $this->getModeOptions(),
            'class' => 'auto-submit',
            //'separator' => ' ',
            ];

        $keyElement = $form->createElement('Radio', 'runmode', $options);
        $form->addElement($keyElement);

        $form->removeElement($form->focusTrackerElementId);
        $form->focusTrackerElementId = null;

        $form->setAttrib('class', 'form-inline');
    }

    /**
     *
     * @param \Zalt\Html\Sequence $seq
     */
    protected function appendCheckedTracks(Sequence $seq)
    {
        $tracks = $this->tracer->getTracks();
        $seq->h2($this->_('Existing tracks check'));
        if ($tracks) {
            $list          = $seq->ol();

            foreach ($tracks as $respTrackId => $trackData) {
                $li = $list->li();

                $respTrackUrl = $this->menuHelper->getRouteUrl('respondent.tracks.show',
                    $this->requestInfo->getRequestMatchedParams() + [Model::RESPONDENT_TRACK => $respTrackId]
                );
                if ($respTrackUrl) {
                    $li->em()->a(
                            $respTrackUrl,
                            $trackData['trackName']
                            );
                } else {
                    $li->em($trackData['trackName']);
                }
                if ($trackData['trackStart'] instanceof \DateTimeInterface) {
                    $startDate = $this->translatedUtil->describeDateFromNow($trackData['trackStart']);
                } else {
                    $startDate = $this->_('startdate unknown');
                }

                if ($trackData['trackInfo']) {
                    $li->append(sprintf($this->_(' [%s, startdate %s]'), $trackData['trackInfo'], $startDate));
                } else {
                    $li->append(sprintf($this->_(' [startdate %s]'), $startDate));
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
            $seq->pInfo($this->_('No tracks to check.'));
        }
    }

    /**
     *
     * @param \Zalt\Html\Sequence $seq
     */
    protected function appendFiltersChecked(Sequence $seq)
    {
        $seq->h2($this->_('Creation by filter check'));

        $filters = $this->tracer->getFilters();
        if ($filters) {
            $baseUrl       = [
                MetaModelInterface::REQUEST_ID1 => $this->appointment->getPatientNumber(),
                MetaModelInterface::REQUEST_ID2 => $this->appointment->getOrganizationId(),
                ];
            $list          = $seq->ol();
//            $menuField     = $this->menu->findAllowedController('track-fields', 'show');
//            $menuFilter    = $this->menu->findAllowedController('agenda-filter', 'show');
//            $menuRespTrack = $this->menu->findAllowedController('track', 'show-track');
//            $menuTrack     = $this->menu->findAllowedController('track-maintenance', 'show');

            foreach ($filters as $filterId => $filterData) {
                $li = $list->li();
                $track = $this->tracker->getTrackEngine($filterData['filterTrack']);
                $fields = $track->getFieldNames();
                $field  = isset($fields[$filterData['filterField']]) ? $fields[$filterData['filterField']] : null;

                $li->append(sprintf($this->_('%s: '), ucfirst($this->_('filter'))));
                $menuFilterUrl = $this->menuHelper->getRouteUrl('setup.agenda.filter.show', [MetaModelInterface::REQUEST_ID => $filterId]);
                if ($menuFilterUrl) {
                    $li->em()->a(
                        $menuFilterUrl,
                        $filterData['filterName']
                        );
                } else {
                    $li->em($filterData['filterName']);
                }

                $li->append(sprintf($this->_(', %s: '), $this->plural('track', 'tracks', 1)));
                $menuTrackUrl = $this->menuHelper->getRouteUrl('track-builder.track-maintenance.show', ['trackId' => $filterData['filterTrack']]);
                if ($menuTrackUrl) {
                    $li->em()->a($menuTrackUrl, $track->getTrackName());
                } else {
                    $li->em($track->getTrackName());
                }
                if ($field) {
                    $li->append(sprintf($this->_(', %s: '), $this->plural('field', 'fields', 1)));
                    $fieldSplit = FieldsDefinition::splitKey($filterData['filterField']);
                    $menuFieldUrl = $this->menuHelper->getRouteUrl('track-builder.track-maintenance.track-fields.show', [
                        'trackId' => $filterData['filterTrack'],
                        Model::FIELD_ID => $fieldSplit['gtf_id_field'],
                        'sub' => $fieldSplit['sub'],
                    ]);
                    if ($menuFieldUrl) {
                        $li->em()->a($menuFieldUrl, $field);
                    } else {
                        $li->em($field);
                    }
                }
                $li->br();

                if ($filterData['respTrackId']) {
                    $menuRespTrackUrl = $this->menuHelper->getRouteUrl('respondent.tracks.show', $baseUrl + [Model::RESPONDENT_TRACK => $filterData['respTrackId']]);;
                } else {
                    $menuRespTrackUrl = false;
                }
                if ($filterData['skipMessage']) {
                    $li->strong($this->_('track not created because: '));
                    if ($menuRespTrackUrl) {
                        $li->em()->a($menuRespTrackUrl, $filterData['skipMessage']);
                    } else {
                        $li->em($filterData['skipMessage']);
                    }
                    $li->append('.');
                } elseif ($filterData['createTrack']) {
                    if ($this->tracer->executeChanges) {
                        if ($menuRespTrackUrl) {
                            $li->em()->a($menuRespTrackUrl, $this->_('track was created!'));
                        } else {
                            $li->em($this->_('track was created!'));
                        }
                    } else {
                        $li->em($this->_('track would be created!'));
                    }
                } else {
                    $li->em($this->_('track not created, reason unknown!'));
                }
            }
        } else {
            $seq->pInfo($this->_('No creation filters to check.'));
        }
    }

    /**
     *
     * @param \Zalt\Html\Sequence $seq
     */
    protected function appendFiltersSkipped(Sequence $seq)
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
     * @param \Zalt\Html\Sequence $seq
     */
    protected function appendFormInfo(Sequence $seq)
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
    protected function getDefaultFormValues(): array
    {
        return ['runmode' => 0];
    }

    public function getHtmlOutput()
    {
        $form = parent::getHtmlOutput();

        $seq = $this->getHtmlSequence();
        $seq->append($form);

        $this->appendFormInfo($seq);

        $this->tracer->executeChanges = $this->formData['runmode'];

        $this->agenda->updateTracksForAppointment($this->appointment, $this->tracer);

        $seq->h1($this->_('Result'));
        $this->appendCheckedTracks($seq);

        if ($this->tracer->getSkippedFilterCheck()) {
            $this->appendFiltersSkipped($seq);
        } else {
            $this->appendFiltersChecked($seq);
        }
        if ($this->tracer->executeChanges) {
            $this->logChanges(1);
        }

        return $seq;
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems\Menu\MenuList
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
     * @see \Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute(): ?string
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
     * @return self (continuation pattern)
     */
    protected function setAfterSaveRoute(array $params = array())
    {
        $this->afterSaveRouteUrl = '';

        return $this;
    }
}
