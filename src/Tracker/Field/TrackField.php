<?php


namespace Gems\Tracker\Field;

use Gems\Html;
use Gems\Menu\RouteHelper;
use Gems\Tracker;
use Gems\Tracker\RespondentTrack;
use Gems\Util\Translated;
use DateTimeInterface;
use Zalt\Base\TranslatorInterface;

class TrackField extends FieldAbstract
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
    protected array|null $_effecteds = ['multiOptions'];

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
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings): void
    {
        $empty = $this->translatedUtil->getEmptyDropdownArray();

        $settings['elementClass'] = 'Select';
        $settings['multiOptions'] = $empty + $this->getLookup();
        $settings['formatFunction'] = array($this, 'showTrack');
    }
    
    public function calculateFieldInfo(mixed $currentValue, array $fieldData): mixed
    {
        // Always leave empty
        return false;
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
    public function getDataModelDependencyChanges(array $context, bool $new): ?array
    {
        if ($this->isReadOnly()) {
            return null;
        }
        $empty  = $this->translatedUtil->getEmptyDropdownArray();
        $tracks = $this->getLookup($context['gr2t_id_user'], $context['gr2t_id_organization']);
        unset($tracks[$context['gr2t_id_respondent_track']]);
        $output['multiOptions'] = $empty + $tracks;

        return $output;
    }

    /**
     * Return the lookup array for this field
     *
     * @param int $organizationId Organization Id
     * @return array
     */
    protected function getLookup(int $respondentId = null, int $organizationId = null): array
    {
        if ($respondentId !== null && $organizationId !== null) {

            $respondentTracks = $this->tracker->getRespondentTracks($respondentId, $organizationId);

            $respondentTrackPairs = [];
            foreach($respondentTracks as $respondentTrack) {
                $name = $respondentTrack->getTrackName();
                $startDate = $respondentTrack->getStartDate();
                if ($startDate instanceof DateTimeInterface) {
                    $name .= ' (' . $startDate->format('d-m-Y') . ')';
                }
                $respondentTrackPairs[$respondentTrack->getRespondentTrackId()] = $name;
            }

            return $respondentTrackPairs;
        }
        return [];
    }

    /**
     * Display a respondent track as text
     *
     * @param $value
     * @return string
     */
    public function showTrack(RespondentTrack|int|string|null $respondentTrack): ?string
    {
        $empty  = $this->translatedUtil->getEmptyDropdownArray();
        if (! $respondentTrack || in_array($respondentTrack, $empty)) {
            return null;
        }
        if (!$respondentTrack instanceof RespondentTrack && is_numeric($respondentTrack)) {
            $respondentTrack = $this->tracker->getRespondentTrack($respondentTrack);
        }

        if (!$respondentTrack instanceof RespondentTrack) {
            return null;
        }

        $url = $this->routeHelper->getRouteUrl('respondent.track.show', [
            'gr2t_id_respondent' => $respondentTrack->getPatientNumber(),
            'gr2t_id_organization' => $respondentTrack->getOrganizationId(),
            'gr2t_id_respondent_track' => $respondentTrack->getRespondentTrackId(),
        ]);

        if ($url) {
            return Html::create('a', $url, $respondentTrack->getTrackName());
        }

        return $respondentTrack->getTrackName();
    }
}