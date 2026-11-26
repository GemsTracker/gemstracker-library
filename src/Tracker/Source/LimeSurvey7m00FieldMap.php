<?php

namespace Gems\Tracker\Source;

class LimeSurvey7m00FieldMap extends LimeSurvey5m00FieldMap
{
    public const QUESTION_COMMENT_FIELD_SUFFIX = '_Ccomment';
    public const QUESTION_OTHER_FIELD_SUFFIX = '_Cother';

    /**
     * Suffix for the virtual rank slot fields. LS7 stores a ranking answer as a JSON
     * array of subquestion titles in the single column Q{qid}; GemsTracker exposes
     * it as Q{qid}_R1 - Q{qid}_Rn so codes stay {title}_1 - {title}_n as in LS < 7.
     */
    public const RANKING_SLOT_SUFFIX = '_R';

    /**
     * @var array<int, array<int, string>>|null qid => [slot => virtual field key]
     */
    protected ?array $_rankingFields = null;

    /**
     * LS7 ranking items are subquestions, not answer options: add them as the answers of such questions
     */
    protected function _setHardAnswers(): void
    {
        parent::_setHardAnswers();

        $qTable  = $this->_getQuestionsTableName();
        $qtTable = $this->_getQuestionsTranslateTableName();

        $sql = "SELECT sq.parent_qid, sq.title, sqt.question
            FROM $qTable AS q
                JOIN $qTable AS sq ON sq.parent_qid = q.qid
                JOIN $qtTable AS sqt ON sqt.qid = sq.qid AND sqt.language = ?
            WHERE q.sid = ? AND q.type = 'R' AND q.parent_qid = 0
            ORDER BY sq.parent_qid, sq.question_order";

        $rows = $this->lsResultFetcher->fetchAll($sql, [$this->language, $this->sourceSurveyId]);

        foreach ($rows as $row) {
            $this->_hardAnswers[$row['parent_qid']][0][$row['title']] = $this->removeMarkup($row['question']);
        }
    }

    protected function getRankingFieldName(array $fieldInfo, int $slot): string
    {
        return $fieldInfo['sgq'] . static::RANKING_SLOT_SUFFIX . $slot;
    }

    /**
     * @return array<int, array<int, string>> qid => [slot => virtual field key]
     */
    protected function getRankingFields(): array
    {
        if (null === $this->_rankingFields) {
            $this->_rankingFields = [];
            foreach ($this->_getMap() as $key => $field) {
                if (($field['type'] ?? null) === 'R') {
                    $this->_rankingFields[(int) $field['qid']][(int) $field['sq_title']] = (string) $key;
                }
            }
            foreach ($this->_rankingFields as &$slots) {
                ksort($slots);
            }
            unset($slots);
        }

        return $this->_rankingFields;
    }

    protected function getRankingSlotCount(array $fieldInfo, int $possibleAnswers): int
    {
        $slots   = parent::getRankingSlotCount($fieldInfo, $possibleAnswers);
        $maxSubQuestions = (int) $this->_getQuestionAttribute($fieldInfo['qid'], 'max_subquestions', $slots);

        return $maxSubQuestions > 0 ? min($slots, $maxSubQuestions) : $slots;
    }

    /**
     * Expand the JSON ranking column into the virtual rank slot fields before mapping to titles.
     *
     * The slot fields take the position of the ranking column, so the field order stays the same as in LS < 7.
     */
    public function mapKeysToTitles(array $values): array
    {
        $rankingColumns = [];
        foreach ($this->getRankingFields() as $qid => $slots) {
            $rankingColumns[$this->getBaseSurveyQuestionFieldName(['qid' => $qid])] = $slots;
        }

        if ($rankingColumns && array_intersect_key($values, $rankingColumns)) {
            $expanded = [];
            foreach ($values as $key => $value) {
                if (! isset($rankingColumns[$key])) {
                    $expanded[$key] = $value;
                    continue;
                }
                $items = is_string($value) ? json_decode($value, true) : null;
                $items = is_array($items) ? array_values($items) : [];
                foreach ($rankingColumns[$key] as $slot => $fieldKey) {
                    $expanded[$fieldKey] = $items[$slot - 1] ?? null;
                }
            }
            $values = $expanded;
        }

        return parent::mapKeysToTitles($values);
    }

    /**
     * Collapse the virtual rank slot fields back into the JSON ranking column after mapping to keys
     *
     * Only the slots present in $values are used, so pass all slots of a ranking question.
     */
    public function mapTitlesToKeys(array $values): array
    {
        $values = parent::mapTitlesToKeys($values);

        foreach ($this->getRankingFields() as $qid => $slots) {
            if (! array_intersect_key($values, array_flip($slots))) {
                continue;
            }
            $items = [];
            foreach ($slots as $key) {
                if (isset($values[$key]) && '' !== $values[$key]) {
                    $items[] = (string) $values[$key];
                }
                unset($values[$key]);
            }
            $column = $this->getBaseSurveyQuestionFieldName(['qid' => $qid]);
            $values[$column] = $items ? json_encode($items, JSON_UNESCAPED_UNICODE) : null;
        }

        return $values;
    }

    protected function getBaseSurveyQuestionFieldName(array $fieldInfo): string
    {
        return 'Q' . $fieldInfo['qid'];
    }

    protected function getSurveySubQuestionFieldPart(array $fieldInfo): string
    {
        return '_S' . $fieldInfo['sq_qid'];
    }

    protected function getSurveyDualSubQuestionFieldPart(array $fieldInfo1, array $fieldInfo2): string
    {
        return '_S' . $fieldInfo1['sq_qid'] . '_S' . $fieldInfo2['sq_qid'];
    }
}
