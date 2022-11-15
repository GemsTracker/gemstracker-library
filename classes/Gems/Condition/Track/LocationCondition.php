<?php
                
/**
 *
 * @package    Gem
 * @subpackage Condition\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Condition\Track;

use Gems\Condition\ConditionLoader;
use Gems\Condition\ConditionAbstract;
use Gems\Condition\TrackConditionInterface;
use Gems\Tracker;
use Gems\Tracker\RespondentTrack;
use Gems\Util\Translated;

/**
 *
 * @package    Gem
 * @subpackage Condition\Track
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.8
 */
class LocationCondition extends ConditionAbstract implements TrackConditionInterface
{
    public function __construct(protected ConditionLoader $conditions,
        protected Tracker $tracker,
        protected Translated $translatedUtil,
        protected Agenda $agenda,
    )
    {
        parent::__construct($conditions);
    }

    /**
     * Return a help text for this filter.
     *
     * It can be multiline but should not use formatting other than line endings.
     *
     * @return string|void
     */
    public function getHelp(): string
    {
        return $this->_("Track condition will be true when:\n- one of the location fields in a track is equal to\n- either a location in a track field\n- or the location field with the track field code.");
    }

    /**
     * @return array locId => Location label
     */
    protected function getLocations(): array
    {
        return $this->translatedUtil->getEmptyDropdownArray() +
            $this->agenda->getLocationsWithOrganization();
    }

    /**
     * Get the settings for the gcon_condition_textN fields
     *
     * @param array $context
     * @param boolean $new
     * @return array textN => array(modelFieldName => fieldValue)
     */
    public function getModelFields(array $context, bool $new): array
    {
        $fields    = $this->getTrackFields();
        $locations = $this->getLocations();

        for ($i = 1; $i < 4; $i++) {
            $result['gcon_condition_text' . $i] = [
                'label' => sprintf($this->_('Location %d'), $i),
                'elementClass' => 'select',
                'multiOptions' => $locations,
                'required' => 1 == $i,
                ];
        }
        $result['gcon_condition_text4'] = [
            'label' => $this->_('Track field code'),
            'description' => $this->_('Optional field code'),
            'elementClass' => 'select',
            'multiOptions' => $fields,
            'required' => false,
        ];

        return $result;
    }

    /**
     * Get the name to use in dropdowns for this condition
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_('Location');
    }

    public function getNotValidReason(int $value, array $context): string
    {
        // Not used at the moment
        // Reasons could be:
        //   - no location field(s)
        //   - no location field(s) with field code
        // \MUtil\EchoOut\EchoOut::track($value, $context);

        return '';
    }

    /**
     * @inheritDoc
     */
    public function getTrackDisplay(int $trackId): string
    {
        if ($this->_data['gcon_condition_text4']) {
            $start = sprintf($this->_('A location field with code %s set to: '),
                             $this->_data['gcon_condition_text4']);
        } else {
            $start = $this->_('A location field set to: ');
        }

        $locations = $this->agenda->getLocations();
        $output    = [];
        foreach ($this->getUsedLocations() as $locId) {
            if (isset($locations[$locId])) {
                $output[] = $locations[$locId];
            }
        }
        if (! $output) {
            $output[] = $this->_('n/a');
        }

        return $start . implode($this->_(', '), $output);
    }

    /**
     * @return array code => code
     */
    protected function getTrackFields(): array
    {
        // Load the track fields that have a code, and return code => name array
        $fields = $this->tracker->getAllCodeFields();

        //  We now have field ids, and codes, filter to have unique codes
        $result = $this->translatedUtil->getEmptyDropdownArray();
        foreach($fields as $code)
        {
            $result[$code] = $code;
        }

        return $result;
    }

    /**
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @param array $fieldData Optional field data to use instead of data currently stored at object
     * @return array fieldKey => value
     */
    protected function getUsedFieldsValues(RespondentTrack $respTrack, array $fieldData = null): array
    {
        $defs      = $respTrack->getTrackEngine()->getFieldsDefinition();
        $fields    = $fieldData ? $fieldData : $respTrack->getFieldData();
        $forCode   = $this->_data['gcon_condition_text4'];
        $output    = [];

        foreach ($defs->getFieldCodesOfType('location') as $key => $code) {
            if ((! $forCode) || ($code == $forCode)) {
                if (isset($fields[$key])) {
                    $output[$key] = $fields[$key];
                }
            }
        }

        return $output;
    }

    /**
     * @return array locId => locId
     */
    protected function getUsedLocations(): array
    {
        $output = array_filter([
                                   $this->_data['gcon_condition_text1'],
                                   $this->_data['gcon_condition_text2'],
                                   $this->_data['gcon_condition_text3'],
                               ]);

        return array_combine($output, $output);
    }

    /**
     * @inheritDoc
     */
    public function isValid(int $value, array $context): bool
    {
        // \MUtil\EchoOut\EchoOut::track($value, $context);
        // Not used at the moment
        return true;
    }

    /**
     * Is the condition for this round (token) valid or not
     *
     * This is the actual implementation of the condition
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @param array $fieldData Optional field data to use instead of data currently stored at object
     * @return bool
     */
    public function isTrackValid(RespondentTrack $respTrack, array $fieldData = null): bool
    {
        $locations = $this->getUsedLocations();

        foreach ($this->getUsedFieldsValues($respTrack, $fieldData) as $key => $value) {
            if (isset($locations[$value])) {
                return true;
            }
        }

        return false;
    }
}