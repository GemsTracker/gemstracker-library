<?php


namespace Gems\Tracker\Source;


class LimeSurvey5m00FieldMap extends LimeSurvey3m00FieldMap
{
    public const ANSWERS_TRANSLATE_TABLE   = 'answer_l10ns';
    public const GROUPS_TRANSLATE_TABLE   = 'group_l10ns';
    public const QUESTION_TRANSLATE_TABLE = 'question_l10ns';

    /**
     * The answers table contains code => answer sets per question/language/scale_id
     *
     * @return string name of the answers table
     */
    protected function _getAnswersTranslateTableName(): string
    {
        return $this->tablePrefix . self::ANSWERS_TRANSLATE_TABLE;
    }

    /**
     * The groups table contains group texts per group id / language
     *
     * @return string name of the groups table
     */
    protected function _getGroupsTranslateTableName(): string
    {
        return $this->tablePrefix . self::GROUPS_TRANSLATE_TABLE;
    }

    protected function _setHardAnswers(): void
    {
        $qaTable = $this->_getAnswersTableName();
        $qatTable = $this->_getAnswersTranslateTableName();
        $qTable  = $this->_getQuestionsTableName();

        $sql = 'SELECT a.*, qat.answer,  q.other FROM ' . $qaTable . ' AS a
            JOIN ' . $qatTable . ' AS qat ON a.aid = qat.aid
            LEFT JOIN ' . $qTable . ' AS q ON q.qid = a.qid
            WHERE q.sid = ? AND qat.language = ? ORDER BY a.qid, a.scale_id, sortorder';

        $this->_hardAnswers = [];
        if ($rows = $this->lsResultFetcher->fetchAll($sql, array($this->sourceSurveyId, $this->language))) {
            foreach ($rows as $row) {
                $this->_hardAnswers[$row['qid']][$row['scale_id']][$row['code']] = $row['answer'];
                if ($row['other']=='Y') {
                    $this->_hardAnswers[$row['qid']][$row['scale_id']]['-oth-'] =  $this->_getQuestionAttribute($row['qid'], 'other_replace_text', $this->translate->_('Other'));
                }
            }
        }
    }

    protected function getQuestionMapRows(): array
    {
        $aTable = $this->_getQuestionAttributesTableName();
        $cTable = $this->_getQuestionConditonsTableName();
        $gTable = $this->_getGroupsTableName();
        $gtTable = $this->_getGroupsTranslateTableName();
        $qTable = $this->_getQuestionsTableName();
        $qtTable = $this->_getQuestionsTranslateTableName();

        $sql    = "
            SELECT q.sid, q.type, q.qid, q.gid, qt.question, q.title, qt.help,
                q.other, q.question_order,
                g.group_order, gt.group_name, gt.description,
                sq.title AS sq_title, sq.question_order, sqt.question AS sq_question, sq.scale_id,
                at.value AS hidden,
                CASE WHEN q.relevance IS NULL OR q.relevance = '' OR q.relevance = 1 OR NOT EXISTS (SELECT * FROM $cTable AS cn WHERE cn.qid = q.qid) THEN 0 ELSE 1 END AS hasConditon,
                q.relevance AS relevance,
                q.mandatory AS required
            FROM $qTable AS q
                JOIN $qtTable AS qt ON q.qid = qt.qid
                LEFT JOIN $gTable AS g ON q.sid = g.sid AND q.gid = g.gid
                LEFT JOIN $gtTable AS gt ON g.gid = gt.gid AND qt.language=gt.language
                LEFT JOIN $qTable AS sq ON q.qid = sq.parent_qid
                LEFT JOIN $qtTable AS sqt ON sq.qid = sqt.qid
                LEFT JOIN (SELECT * FROM $aTable WHERE attribute = 'hidden' AND (language = ''  OR language IS NULL)) AS at ON q.qid = at.qid
            WHERE g.sid = ? AND qt.language = ? AND gt.language = ? AND q.parent_qid = 0
            ORDER BY g.group_order, q.question_order, sq.scale_id DESC, sq.question_order";

        return $this->lsResultFetcher->fetchAll($sql, [$this->sourceSurveyId, $this->language, $this->language]);
    }

    /**
     * Return a certain question attribute or the default value if it does not exist.
     *
     * @param int $qid
     * @param string $attribute
     * @param mixed $default
     * @return mixed
     */
    protected function _getQuestionAttribute(int $qid, string $attribute, mixed $default = null): mixed
    {
        if (! isset($this->_attributes)) {
            $this->_attributes = [];
            $attributesTable  = $this->_getQuestionAttributesTableName();
            $questionsTable   = $this->_getQuestionsTableName();
            $surveyTable      = $this->_getSurveysTableName();

            $sql = 'SELECT a.qid, a.attribute, a.value FROM ' . $attributesTable . ' AS a
                LEFT JOIN ' . $questionsTable . ' AS q ON q.qid = a.qid
                LEFT JOIN ' . $surveyTable . ' AS s ON s.sid = q.sid
                WHERE s.sid = ?';

            $attributes = $this->lsResultFetcher->fetchAll($sql, [$this->sourceSurveyId]);

            if (null === $attributes) {
                // If DB lookup failed, return the default
                return $default;
            }

            foreach ($attributes as $attrib) {
                $this->_attributes[$attrib['qid']][$attrib['attribute']] = $attrib['value'];
            }
        }

        if (isset($this->_attributes[$qid][$attribute]) && strlen(trim($this->_attributes[$qid][$attribute]))) {
            return $this->_attributes[$qid][$attribute];
        } else {
            return $default;
        }
    }

    /**
     * The question table contains one row per language per question per survey in LS.
     *
     * All non-text data is replicated over all the question/language rows.
     *
     * @return string name of the questions table
     */
    protected function _getQuestionsTranslateTableName(): string
    {
        return $this->tablePrefix . self::QUESTION_TRANSLATE_TABLE;
    }
}
