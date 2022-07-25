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

use Gems\Conditions;
use Gems\Condition\ConditionAbstract;
use Gems\Condition\TrackConditionInterface;

/**
 *
 * @package    Gem
 * @subpackage Condition\Track
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.8
 */
class LocationCondition extends ConditionAbstract implements TrackConditionInterface
{
    /**
     * @var \Gems\Tracker
     */
    protected $tracker;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * @inheritDoc
     */
    public function afterRegistry()
    {
        parent::afterRegistry();
        if ($this->loader && !$this->tracker) {
            $this->tracker = $this->loader->getTracker();
        }
    }

    /**
     * Return a help text for this filter.
     *
     * It can be multiline but should not use formatting other than line endings.
     *
     * @return string|void
     */
    public function getHelp()
    {
        return $this->_("Track condition will be true when:\n- one of the location fields in a track is equal to\n- either a location in a track field\n- or the location field with the track field code.");
    }

    /**
     * @return array locId => Location label
     */
    protected function getLocations()
    {
        return $this->util->getTranslated()->getEmptyDropdownArray() +
            $this->loader->getAgenda()->getLocationsWithOrganization();;
    }

    /**
     * Get the settings for the gcon_condition_textN fields
     *
     * @param array $context
     * @param boolean $new
     * @return array textN => array(modelFieldName => fieldValue)
     */
    public function getModelFields($context, $new)
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
    public function getName()
    {
        return $this->_('Location');
    }

    public function getNotValidReason($value, $context)
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
    public function getTrackDisplay($trackId)
    {
        if ($this->_data['gcon_condition_text4']) {
            $start = sprintf($this->_('A location field with code %s set to: '),
                             $this->_data['gcon_condition_text4']);
        } else {
            $start = $this->_('A location field set to: ');
        }

        $locations = $this->loader->getAgenda()->getLocations();
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
    protected function getTrackFields()
    {
        // Load the track fields that have a code, and return code => name array
        $fields = $this->tracker->getAllCodeFields();

        //  We now have field ids, and codes, filter to have unique codes
        $result = $this->util->getTranslated()->getEmptyDropdownArray();
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
    protected function getUsedFieldsValues(\Gems\Tracker\RespondentTrack $respTrack, array $fieldData = null)
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
    protected function getUsedLocations()
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
    public function isValid($value, $context)
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
    public function isTrackValid(\Gems\Tracker\RespondentTrack $respTrack, array $fieldData = null)
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