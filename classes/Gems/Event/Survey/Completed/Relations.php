<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Survey\Completed;

/**
 *
 *
 * @package    Gems
 * @subpackage Event\Survey
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class Relations extends \MUtil\Translate\TranslateableAbstract implements \Gems\Event\SurveyCompletedEventInterface
{

    /**
     * Map grr_field to lookup code, keep lookup lowercase
     *
     * @var Array
     */
    protected $fieldmap = [
        'grr_first_name' => 'firstname',
        'grr_last_name'  => 'lastname',
        'grr_birthdate'  => 'birthdate',
        'grr_gender'     => 'gender',
        'grr_email'      => 'email',
        'grr_type'       => 'type'
    ];

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     *
     * @var \Gems\Model\RespondentRelationModel
     */
    protected $relationModel;

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return sprintf($this->_("Import and assign relations, prefix fields with relationfield code, followed by %s"), join(', ', $this->fieldmap));
    }

    /**
     * Process the data and return the answers that should be changed.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @return array Containing the changed values
     */
    public function processTokenData(\Gems\Tracker\Token $token)
    {
        $respondentTrack = $token->getRespondentTrack();

        $relationFields = $respondentTrack->getTrackEngine()->getFieldsOfType('relation');

        if (!empty($relationFields)) {
            $this->relationModel = $this->loader->getModels()->getRespondentRelationModel();

            // Get lowercase question codes
            $tokenAnswers = array_change_key_case($token->getRawAnswers(), CASE_LOWER);
            $respondent   = $token->getRespondent();

            $fields = [];
            foreach ($relationFields as $fieldName => $fieldCode) {
                if ($relation = $this->findRelation($fieldCode, $tokenAnswers)) {
                    $relationId         = $this->handleRelation($relation, $respondent);
                    // Save the id so we only need to update the track once
                    $fields[$fieldCode] = $relationId;
                }
            }

            if (!empty($fields)) {
                $respondentTrack->setFieldData($fields);
            }
        }

        return;
    }

    /**
     * Extract the relevant fields for the given code of the relation field
     *
     * @param string $fieldCode
     * @param array $answers
     * @return [] Array of relation fields
     */
    protected function findRelation($fieldCode, $answers)
    {
        $relation       = [];
        $lowerFieldCode = strtolower($fieldCode);

        foreach ($this->fieldmap as $field => $code) {
            if (array_key_exists($lowerFieldCode . $code, $answers)) {
                $relation[$field] = $answers[$lowerFieldCode . $code];
            }
        }
        // fix gender
        if (array_key_exists('grr_gender', $relation)) {
            $relation['grr_gender'] = strtoupper($relation['grr_gender']);
            $options                = ['M', 'F', 'U'];
            if (!in_array($relation['grr_gender'], $options)) {
                unset($relation['grr_gender']);
            }
        }

        return $relation;
    }

    /**
     * Perform checks on the relation, does it already exist and need updating
     *
     * @param array $relation
     * @param \Gems\Tracker\Respondent $respondent
     * @return int The relation ID
     */
    protected function handleRelation($relation, \Gems\Tracker\Respondent $respondent)
    {

        $relationFilter = [
            'grr_id_respondent' => $respondent->getId(),
            'grr_active'        => 1
                ] + $relation;

        // If we want to select on only some fields that should match, define here otherwise search on all fields to match
        $relationFound = $this->relationModel->loadFirst($relationFilter);

        if ($relationFound === false) {
            // Not found
            if (array_key_exists('grr_birthdate', $relation)) {
                $dateformats               = [
                    'yyyy-MM-dd HH:mm:ss',
                    'yyyy-MM-dd'
                ];
                $relation['grr_birthdate'] = \MUtil\Date::ifDate($relation['grr_birthdate'], $dateformats);
            }

            $relation = ['grr_id_respondent' => $respondent->getId()] + $relation + $this->relationModel->loadNew();

            $relationFound = $this->relationModel->save($relation);
        }

        return $relationFound['grr_id'];
    }

}