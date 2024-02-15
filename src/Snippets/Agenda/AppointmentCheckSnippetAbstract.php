<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Agenda\FilterTracer;
use Gems\Audit\AuditLog;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
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
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @since      Class available since version 1.0
 */
abstract class AppointmentCheckSnippetAbstract extends \Gems\Snippets\FormSnippetAbstract
{
    /**
     * The form Id used for the save button
     *
     * If empty save button is not added
     *
     * @var string
     */
    protected $saveButtonId = '';

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
    }

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(mixed $form)
    {
        /**
         * @var \Gems\Form $form
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
        $form->focusTrackerElementId = '';

        $form->setAttrib('class', 'form-inline');
    }

    /**
     *
     * @param \Zalt\Html\Sequence $seq
     */
    protected function appendCheckedTracks(Sequence $seq, FilterTracer $tracer)
    {
        $tracks = $tracer->getTracks();
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
                if ($tracer->executeChanges) {
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
    protected function appendFiltersChecked(Sequence $seq, FilterTracer $tracer)
    {
        $filters = $tracer->getFilters();
        if ($filters) {
            $baseUrl       = [
                MetaModelInterface::REQUEST_ID1 => $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID1),
                MetaModelInterface::REQUEST_ID2 => $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID2),
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
                    if ($tracer->executeChanges) {
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
    protected function appendFiltersSkipped(Sequence $seq, FilterTracer $tracer, Appointment $appointment)
    {
        if ($appointment->isActive()) {
            $seq->pInfo($this->_('Check skipped because the appointment is in the past.'));
        } else {
            $seq->pInfo($this->_('Check skipped because the appointment is not active.'));
        }
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
     * @inheritdoc
     */
    public function getRedirectRoute(): ?string
    {
        return null;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return '';
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