<?php

namespace Gems\Tracker\Model;

class AddTrackFieldsByCodeTransformer extends \MUtil_Model_ModelTransformerAbstract
{


    protected $includeCodes;
    
    /**
     *
     * @var string The field that contains the respondent track id
     */
    protected $respTrackIdField = 'gr2t_id_respondent_track';

    /**
     * @var \Gems_Tracker_TrackerInterface
     */
    protected $tracker;

    protected $trackFieldsByRespondentTrack;

    /**
     *
     * @param \Gems_Tracker_TrackerInterface $tracker
     * @param array $includeCodes
      * @param $respTrackIdField Overwrite the default field that contains the respondent track id (gr2t_id_respondent_track)
     */
    public function __construct(\Gems_Tracker_TrackerInterface $tracker, array $includeCodes, $respTrackIdField = false)   
    {
        $this->includeCodes = $includeCodes;
        $this->tracker = $tracker;
        
        if ($respTrackIdField) {
            $this->respTrackIdField = $respTrackIdField;
        }
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
            if (isset($row[$this->respTrackIdField])) {
                $respTrackId = $row[$this->respTrackIdField];
                if (!isset($this->trackFieldsByRespondentTrack[$respTrackId])) {
                    $trackId = isset($row['gtr_id_track']) ? $row['gtr_id_track'] : $this->tracker->getToken($tokenId)->getTrackId();
                    $this->trackFieldsByRespondentTrack[$respTrackId] = $this->getTrackFields($trackId, $respTrackId);
                }
                $newData = $this->trackFieldsByRespondentTrack[$respTrackId];
                $data[$tokenId] = array_merge($data[$tokenId], $newData);
            }

        }

        return $data;
    }

    protected function getTrackfields($trackId, $respondentTrackId)
    {
        $engine = $this->tracker->getTrackEngine($trackId);

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