<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model\Transform;

use Gems\Tracker\Source\SourceInterface;
use Gems\Tracker\Survey;
use Gems\Tracker\TrackerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 13-feb-2014 16:33:25
 */
class AddAnswersTransformer extends ModelTransformerAbstract
{

    protected int $changed = 0;

    /**
     * @var string The field in the suppied data array that holds the Token ID
     */
    protected string $tokenField = 'gto_id_token';

    public function __construct(
        protected readonly Survey $survey,
        protected readonly SourceInterface $source,
        protected readonly TrackerInterface $tracker,
    )
    {
    }

    /**
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged(): int
    {
        return $this->changed;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil\Model\ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false): array
    {
        // get tokens

        $tokens = array_column($data, $this->tokenField);

        $answerRows = $this->source->getRawTokenAnswerRows(['token' => $tokens], $this->survey->getSurveyId());
        $resultRows = [];

        $emptyRow = array_fill_keys($model->getItemNames(), null);

        foreach ($data as $row) {
            $tokenId = $row[$this->tokenField];

            if (isset($answerRows[$tokenId])) {
                $resultRows[$tokenId] = $row + $answerRows[$tokenId] + $emptyRow;
            } else {
                $resultRows[$tokenId] = $row + $emptyRow;
            }
        }

        // No changes
        return $resultRows;
    }

    /**
     * This transform function performs the actual save (if any) of the transformer data and is called after
     * the saving of the data in the source model.
     *
     * @param MetaModelInterface $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(MetaModelInterface $model, array $row): array
    {
        $token = $this->tracker->getToken($row[$this->tokenField]);
        $answers = $row;
        $surveyId = $this->survey->getSurveyId();
        if ($this->source->setRawTokenAnswers($token, $answers, $surveyId)) {
            $this->changed++;
        }

        // No changes
        return $row;
    }
}
