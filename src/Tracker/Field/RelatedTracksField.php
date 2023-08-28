<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

use Gems\Html;
use Gems\Menu\RouteHelper;
use Gems\Tracker;
use Gems\Tracker\RespondentTrack;
use Gems\Util\Translated;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Html\HtmlElement;

/**
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class RelatedTracksField extends MultiselectField
{
    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array Null or an array of respondent track fields.
     */
    protected array|null $_dependsOn = [
        'gr2t_id_user',
        'gr2t_id_organization',
        'gr2t_id_respondent_track'
    ];

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected array|null $_effecteds = [
        'description',
        'elementClass',
        'multiOptions'
    ];

    /**
     * @var string class for showtracks list display
     */
    protected string $displayClass;

    /**
     * @var bool When true the value is saved with padded seperators
     */
    protected bool $padSeperators = true;

    public function __construct(
        int $trackId,
        string $fieldKey,
        array $fieldDefinition,
        TranslatorInterface $translator,
        Translated $translatedUtil,
        protected readonly Tracker $tracker,
        protected readonly RouteHelper $routeHelper,
    ) {
        parent::__construct($trackId, $fieldKey, $fieldDefinition, $translator, $translatedUtil);
    }

    /**
     * @inheritDoc
     */
    protected function addModelSettings(array &$settings): void
    {
        parent::addModelSettings($settings);
        // \MUtil\EchoOut\EchoOut::track(array_keys($settings));

        $settings['escape'] = false;
        $settings['formatFunction'] = [$this, 'showTracks'];
    }

    /**
     * Returns the changes to the model for this field that must be made in an array consisting of
     *
     * <code>
     *  array(setting1 => $value1, setting2 => $value2, ...),
     * </code>
     *
     * By using [] array notation in the setting array key you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array (setting => value)
     */
    public function getDataModelDependencyChanges(array $context, bool $new): array
    {
        if ($this->isReadOnly()) {
            $output['elementClass'] = 'Exhibitor';
            $output['description']  = $this->translator->_('No other tracks to select');
        } else {
            $respondentTracks = $this->tracker->getRespondentTracks($context['gr2t_id_user'], $context['gr2t_id_organization'], ['gr2t_start_date DESC']);

            // Remove the current track itself as an option
            unset($respondentTracks[$context['gr2t_id_respondent_track']]);

            if ($respondentTracks) {
                $options = [];
                foreach ($respondentTracks as $respondentTrack) {
                    if ($respondentTrack instanceof RespondentTrack) {
                        $class = $this->getTrackClass($respondentTrack);
                        $label = $this->getTrackLabel($respondentTrack);
                        
                        if ($class) {
                            $label = Html::create('span', $label, ['class' => $class])->render();
                        }
                        $options[$respondentTrack->getRespondentTrackId()] = $label;
                    }
                }
                $output['multiOptions'] = $options;
            } else {
                $output['description'] = $this->translator->_('No other tracks to select');
                $output['elementClass'] = 'Exhibitor';
            }
            // \MUtil\EchoOut\EchoOut::track($context,count($respondentTracks), array_keys($respondentTracks));
        }
        
        return $output;
    }

    /**
     * @param \Gems\Tracker\RespondentTrack $respondentTrack
     * @return string The label to display
     */
    public function getTrackClass(RespondentTrack $respondentTrack): string
    {
        return $respondentTrack->getReceptionCode()->isSuccess() ? '' : 'deleted';
    }
    
    /**
     * @param \Gems\Tracker\RespondentTrack $respondentTrack
     * @return string The label to display
     */
    public function getTrackLabel(RespondentTrack $respondentTrack): string
    {
        return $respondentTrack->getTrackName() . ' ' . $respondentTrack->getFieldsInfo();
    }

    /**
     * Converting the field value when saving to a respondent track
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataSave(mixed $currentValue, array $fieldData): mixed
    {
        if (is_array($currentValue)) {
            rsort($currentValue, SORT_NUMERIC);
        }

        return parent::onFieldDataSave($currentValue, $fieldData);
    }
    
    /**
     * @param $value
     * @return HtmlElement
     */
    public function showTracks(array|string $value): HtmlElement
    {
        if (! is_array($value)) {
            $value = explode('|', $value);
        }

        $ul = Html::create('span', $this->translator->_('No linked tracks selected.'));
        if ($value) {
            $ul = \Zalt\Html\Html::create('ul', ['class' => $this->displayClass]);
            foreach ($value as $respondentTrackId) {
                // \MUtil\EchoOut\EchoOut::track($respondentTrackId);
                $respondentTrack = $this->tracker->getRespondentTrack($respondentTrackId);

                $label = $this->getTrackLabel($respondentTrack);

                $li = $ul->li();
                $li->class = $this->getTrackClass($respondentTrack);

                $url = $this->routeHelper->getRouteUrl('respondent.tracks.show-track', [
                   Model::REQUEST_ID1 => $respondentTrack->getPatientNumber(),
                   Model::REQUEST_ID2 => $respondentTrack->getOrganizationId(),
                   \Gems\Model::RESPONDENT_TRACK => $respondentTrack->getRespondentTrackId(),
                ]);
                if ($url) {
                    $li->a($url, $label);
                } else {
                    $li->append($label);
                }
            }
        }
        
        return $ul;
    }
}