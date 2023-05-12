<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Completed;

use Gems\Model\RespondentRelationModel;
use Gems\Tracker\Respondent;
use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyCompletedEventInterface;
use MUtil\Model;
use MUtil\Translate\Translator;
use Zalt\Loader\ProjectOverloader;

/**
 *
 *
 * @package    Gems
 * @subpackage Event\Survey
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class Relations implements SurveyCompletedEventInterface
{

    /**
     * Map grr_field to lookup code, keep lookup lowercase
     *
     * @var array
     */
    protected array $fieldmap = [
        'grr_first_name' => 'firstname',
        'grr_last_name'  => 'lastname',
        'grr_birthdate'  => 'birthdate',
        'grr_gender'     => 'gender',
        'grr_email'      => 'email',
        'grr_type'       => 'type'
    ];

    /**
     *
     * @var RespondentRelationModel
     */
    protected RespondentRelationModel $relationModel;

    public function __construct(protected Translator $translator, protected ProjectOverloader $overLoader)
    {}

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return sprintf($this->translator->_("Import and assign relations, prefix fields with relationfield code, followed by %s"), join(', ', $this->fieldmap));
    }

    protected function getRespondentRelationModel(): RespondentRelationModel
    {
        /**
         * @var RespondentRelationModel
         */
        return $this->overLoader->create('Model\\RespondentRelationModel');
    }

    /**
     * Process the data and return the answers that should be changed.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param Token $token \Gems token object
     * @return array Containing the changed values
     */
    public function processTokenData(Token $token): array
    {
        $respondentTrack = $token->getRespondentTrack();

        $relationFields = $respondentTrack->getTrackEngine()->getFieldsOfType('relation');

        if (!empty($relationFields)) {
            $this->relationModel = $this->getRespondentRelationModel();

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

        return [];
    }

    /**
     * Extract the relevant fields for the given code of the relation field
     *
     * @param string $fieldCode
     * @param array $answers
     * @return array Array of relation fields
     */
    protected function findRelation(string $fieldCode, array $answers): array
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
     * @param Respondent $respondent
     * @return int The relation ID
     */
    protected function handleRelation(array $relation, Respondent $respondent): int
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
                    'Y-m-d H:i:s',
                    'Y-m-d'
                ];
                $relation['grr_birthdate'] = Model::getDateTimeInterface($relation['grr_birthdate'], $dateformats);
            }

            $relation = ['grr_id_respondent' => $respondent->getId()] + $relation + $this->relationModel->loadNew();

            $relationFound = $this->relationModel->save($relation);
        }

        return $relationFound['grr_id'];
    }

}