<?php

namespace Gems\Tracker\Model;

class AddTrackFieldsByCodeTransformer extends \MUtil_Model_ModelTransformerAbstract
{


    protected $includeCodes;

    /**
     * @var \Gems_Tracker_TrackerInterface
     */
    protected $tracker;

    protected $trackFieldsByRespondentTrack;

    public function __construct(\Gems_Tracker_TrackerInterface $tracker, array $includeCodes)
    {
        $this->includeCodes = $includeCodes;
        $this->tracker = $tracker;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $tokenId=>$row) {
            if (isset($row['gto_id_respondent_track'])) {
                if (!isset($this->trackFieldsByRespondentTrack[$row['gto_id_respondent_track']])) {
                    $trackData = array_filter($row, function($key) {
                        return strpos($key, 'gtr_') === 0;
                    }, ARRAY_FILTER_USE_KEY);
                    $this->trackFieldsByRespondentTrack[$row['gto_id_respondent_track']] = $this->getTrackFields($trackData, $row['gto_id_respondent_track']);
                }
                $newData = $this->trackFieldsByRespondentTrack[$row['gto_id_respondent_track']];
                $data[$tokenId] = array_merge($data[$tokenId], $newData);
            }

        }

        return $data;
    }

    protected function getTrackfields(array $trackData, $respondentTrackId)
    {
        $engine = $this->tracker->getTrackEngine($trackData);

        $fieldCodes = $engine->getFieldCodes();
        $filteredFieldCodes = array_intersect($fieldCodes, $this->includeCodes);

        if (empty($filteredFieldCodes)) {
            return [];
        }
        $fieldData = $engine->getFieldsData($respondentTrackId);

        $filteredFieldData = [];
        foreach($filteredFieldCodes as $fieldId=>$fieldCode) {
            $filteredFieldData[$fieldCode] = $fieldData[$fieldId];
        }

        return $filteredFieldData;
    }
}