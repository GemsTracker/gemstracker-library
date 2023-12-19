<?php

namespace Gems\Tracker\Model;

use Gems\Tracker;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class AddTrackFieldsByCodeTransformer extends ModelTransformerAbstract
{

    protected array $trackFieldsByRespondentTrack = [];

    /**
     *
     * @param Tracker $tracker
     * @param array $includeCodes
      * @param string $respTrackIdField Overwrite the default field that contains the respondent track id (gr2t_id_respondent_track)
     */
    public function __construct(
        protected readonly Tracker $tracker,
        protected readonly array $includeCodes,
        protected readonly string $respTrackIdField = 'gr2t_id_respondent_track')
    {}

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param MetaModelInterface $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false): array
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

    protected function getTrackFields(int $trackId, int $respondentTrackId): array
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