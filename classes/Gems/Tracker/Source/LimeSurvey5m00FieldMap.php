<?php


namespace Gems\Tracker\Source;


class LimeSurvey5m00FieldMap extends \Gems\Tracker\Source\LimeSurvey2m00FieldMap
{
    const ANSWERS_TRANSLATE_TABLE   = 'answer_l10ns';
    const GROUPS_TRANSLATE_TABLE   = 'group_l10ns';
    const QUESTION_TRANSLATE_TABLE = 'question_l10ns';

    /**
     * The answers table contains code => answer sets per question/language/scale_id
     *
     * @return string name of the answers table
     */
    protected function _getAnswersTranslateTableName()
    {
        return $this->tablePrefix . self::ANSWERS_TRANSLATE_TABLE;
    }

    private function _getFixedAnswers($type)
    {
        switch ($type) {
            case ':':
                $answers[1] = $this->translate->_('Yes');
                $answers[0] = $this->translate->_('No');
                break;
            case "C":
                $answers['Y'] = $this->translate->_('Yes');
                $answers['N'] = $this->translate->_('No');
                $answers['U'] = $this->translate->_('Uncertain');
                break;
            case "E":
                $answers['I'] = $this->translate->_('Increase');
                $answers['S'] = $this->translate->_('Same');
                $answers['D'] = $this->translate->_('Decrease');
                break;
            case 'G':
                $answers['F'] = $this->translate->_('Female');
                $answers['M'] = $this->translate->_('Male');
                break;
            case 'M':
            case 'P':
                $answers['Y'] = $this->translate->_('Checked');
                $answers['']  = $this->translate->_('Not checked');
                break;
            case "Y":
                $answers['Y'] = $this->translate->_('Yes');
                $answers['N'] = $this->translate->_('No');
                break;
            default:
                $answers = false;
        }
        return $answers;
    }

    /**
     * The groups table contains group texts per group id / language
     *
     * @return string name of the groups table
     */
    protected function _getGroupsTranslateTableName()
    {
        return $this->tablePrefix . self::GROUPS_TRANSLATE_TABLE;
    }

    /**
     * Returns the answers for a matrix or list type question from the answers table
     *
     * Uses 1 query to retrieve all answers and serves them as needed
     *
     * @param integer    $qid        Question ID
     * @param integer    $scaleId    Scale ID
     */
    private function _getHardAnswers($qid, $scaleId)
    {
        if (! is_array($this->_hardAnswers)) {
            $qaTable = $this->_getAnswersTableName();
            $qatTable = $this->_getAnswersTranslateTableName();
            $qTable  = $this->_getQuestionsTableName();

            $sql = 'SELECT a.*, qat.answer,  q.other FROM ' . $qaTable . ' AS a
                JOIN ' . $qatTable . ' AS qat ON a.aid = qat.aid
                LEFT JOIN ' . $qTable . ' AS q ON q.qid = a.qid
                WHERE q.sid = ? AND qat.language = ? ORDER BY a.qid, a.scale_id, sortorder';

            $this->_hardAnswers = array();
            if ($rows = $this->lsDb->fetchAll($sql, array($this->sourceSurveyId, $this->language))) {
                foreach ($rows as $row) {
                    $this->_hardAnswers[$row['qid']][$row['scale_id']][$row['code']] = $row['answer'];
                    if ($row['other']=='Y') {
                        $this->_hardAnswers[$row['qid']][$row['scale_id']]['-oth-'] =  $this->_getQuestionAttribute($row['qid'], 'other_replace_text', $this->translate->_('Other'));
                    }
                }
            }
        }

        if (array_key_exists($qid, $this->_hardAnswers) && array_key_exists($scaleId, $this->_hardAnswers[$qid])) {
            return $this->_hardAnswers[$qid][$scaleId];
        }

        return false;
    }

    protected function _getMap()
    {
        $cacheId = 'lsFieldMap'.$this->sourceId . '_'.$this->sourceSurveyId.strtr($this->language, '-.', '__');
        $this->_fieldMap = $this->cache->getCacheItem($cacheId);

        if (null === $this->_fieldMap) {
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
                    CASE WHEN q.relevance IS NULL OR q.relevance = '' OR q.relevance = 1 OR NOT EXISTS (SELECT * FROM $cTable AS cn WHERE cn.qid = q.qid) THEN 0 ELSE 1 END AS hasConditon
                FROM $qTable AS q
                    JOIN $qtTable AS qt ON q.qid = qt.qid
                    LEFT JOIN $gTable AS g ON q.sid = g.sid AND q.gid = g.gid
                    LEFT JOIN $gtTable AS gt ON g.gid = gt.gid AND qt.language=gt.language
                    LEFT JOIN $qTable AS sq ON q.qid = sq.parent_qid
                    LEFT JOIN $qtTable AS sqt ON sq.parent_qid = sqt.qid
                    LEFT JOIN (SELECT * FROM $aTable WHERE attribute = 'hidden' AND (language = ''  OR language IS NULL)) AS at ON q.qid = at.qid
                WHERE g.sid = ? AND qt.language = ? AND gt.language = ? AND q.parent_qid = 0
                ORDER BY g.group_order, q.question_order, sq.scale_id DESC, sq.question_order";

            $rows = $this->lsDb->fetchAll($sql, array($this->sourceSurveyId, $this->language, $this->language));

            $rowscount = count($rows);
            foreach($rows as &$row) {
                $row['sgq'] = $row['sid'] . 'X' . $row['gid'] . 'X' . $row['qid'];
            }
            unset($row);    // To prevent strange error

            $map = array();
            for ($i = 0; $i < $rowscount; $i++) {
                $row = $rows[$i];
                $other = ($row['other'] == 'Y');

                switch ($row['type']) {
                    case '1':        //Dual scale
                        //Check scale header in attributes table
                        $row1 = $row;
                        $row1['sgq'] .= $row['sq_title'] . '#0';
                        $row1['code'] = $row['title'] . '_' . $row['sq_title'] . '#0';
                        $row1['sq_question1'] = $this->_getQuestionAttribute($row['qid'], 'dualscale_headerA', 'scale 1');
                        $map[$row1['sgq']] = $row1;

                        $row2 = $row;
                        $row2['scale_id'] = 1;
                        $row2['sgq'] .= $row['sq_title'] . '#1';
                        $row2['code'] = $row['title'] . '_' . $row['sq_title'] . '#1';
                        $row2['sq_question1'] = $this->_getQuestionAttribute($row['qid'], 'dualscale_headerB', 'scale 2');
                        $map[$row2['sgq']] = $row2;
                        break;

                    case 'R':     //Ranking question
                        //Check the needed slots in attributes table
                        $possibleAnswers = count($this->_getMultiOptions($row));
                        $maxAnswers      = $this->_getQuestionAttribute($row['qid'], 'max_answers', $possibleAnswers);
                        $slots           = min($maxAnswers, $possibleAnswers);

                        for ($a = 1; $a <= $slots; $a++) {
                            $row1 = $row;
                            $row1['code'] = $row['title'] . '_' . $a;
                            $row1['sgq'] = $row['sgq'] . $a;
                            $row1['sq_title'] = $a;
                            $row1['sq_question'] = sprintf($this->translate->_('Rank %d'), $a);
                            $map[$row1['sgq']] = $row1;
                        }
                        break;

                    case 'M':    //Multiple options with other
                    case 'O':    //List with comment
                    case 'P':    //Multiple options with other and comment
                        do {
                            $row = $rows[$i];
                            $row['sgq'] .= $row['sq_title'];
                            if ($rows[$i]['type'] === 'O') {    // List, only one answer don't add _
                                $row['code'] = $row['title'];
                            } else {
                                $row['code'] = $row['title'] . '_' . $row['sq_title'];
                            }
                            $map[$row['sgq']] = $row;
                            $row1 = $row;
                            if ($row['type'] !== 'M') {
                                $row1['sgq'] .= 'comment';
                                $row1['code'] .= 'comment';
                                $row1['sq_title'] .= 'comment';
                                $row1['sq_question'] = ($rows[$i]['type'] === 'O') ? $this->translate->_('Comment') : $row['sq_question'] . $this->translate->_(' (comment)');
                                $row1['type'] = 'S';
                                $map[$row1['sgq']] = $row1;
                            }
                            $i++;
                        } while ($i<$rowscount && $rows[$i]['qid']==$row['qid']);
                        $i--;
                        if ($other) {
                            $row = $rows[$i];
                            $row['sgq'] .= 'other';
                            $row['sq_title'] = 'other';
                            $row['code'] = $row['title'] . '_' . $row['sq_title'];
                            $row['sq_question'] = $this->_getQuestionAttribute($row['qid'], 'other_replace_text', $this->translate->_('Other'));
                            if ($row['type'] === 'P' || $row['type'] === 'M') {
                                $row['type'] = 'S';
                            }
                            $map[$row['sgq']] = $row;

                            if ($rows[$i]['type'] == 'P') {
                                $row['sgq'] .= 'comment';
                                $row['code'] .= 'comment';
                                $row['sq_title'] .= 'comment';
                                $row['sq_question'] = ($rows[$i]['type'] === 'O') ? $this->translate->_('Comment') : $row['sq_question'] . $this->translate->_(' (comment)');
                                $row['type'] = 'S';
                                $map[$row['sgq']] = $row;
                            }
                        }
                        break;

                    case ':':    //Multi flexi numbers
                    case ';':    //Multi flexi text
                        $tmprow = array();
                        do {
                            $tmprow[] = $rows[$i];
                            $i++;
                        } while ($rows[$i]['scale_id']=='1');
                        do {
                            foreach($tmprow as $row2) {
                                $row1 = $rows[$i];
                                $row1['sgq'] .= $row1['sq_title'] . '_' . $row2['sq_title'];
                                $row1['code'] = $row1['title']. '_' .$row1['sq_title'] . '_' . $row2['sq_title'];
                                $row1['sq_question1'] = $row2['sq_question'];
                                $map[$row1['sgq']] = $row1;
                            }
                            $i++;
                        } while ($i<$rowscount && $rows[$i]['qid']==$row1['qid']);
                        $i--;
                        break;

                    case '*':   //Equation type
                        $row['code'] = $row['title'];

                        // Since there is no question text (it contains the equation)
                        // We use the help text for that, but in case that is empty we use
                        // The question code
                        $row['equation'] = $row['question'];
                        $row['question'] = $row['help'];
                        $row['help']     = '';
                        if (empty($row['question'])) {
                            $row['question'] = $row['code'];
                        }
                        $map[$row['sgq']] = $row;
                        break;

                    default:
                        $row['code'] = $row['title'];
                        if (!is_null($row['sq_title'])) {
                            $row['sgq'] .= $row['sq_title'];
                            $row['code'] .= '_' . $row['sq_title'];
                        }
                        $map[$row['sgq']] = $row;
                        if ($other) {
                            $row['sgq'] .= 'other';
                            $row['code'] .= 'other';
                            $row['sq_title'] = 'other';
                            $row['sq_question'] = $this->_getQuestionAttribute($row['qid'], 'other_replace_text', $this->translate->_('Other'));
                            $row['type'] = 'S';
                            $map[$row['sgq']] = $row;
                        }
                }
            }
            // now add some default fields that need a datetime stamp
            $map = array(
                    'startdate' => array('type' => self::INTERNAL, 'qid' => 0, 'gid' => 0),
                    'submitdate' => array('type' => self::INTERNAL, 'qid' => 0, 'gid' => 0),
                    'datestamp' => array('type' => self::INTERNAL, 'qid' => 0, 'gid' => 0),
                ) + $map;

            $this->_fieldMap = $map;
            // \MUtil\EchoOut\EchoOut::track($map);
            // Use a tag (for cleaning if supported) and 1 day lifetime, maybe clean cache on sync survey?

            $item = $this->cache->getItem($cacheId);
            $item->set($this->_fieldMap);
            $item->tag(['fieldmap']);
            $item->expiresAfter(86400);
            $this->cache->save($item);
        }

        return (array)$this->_fieldMap;
    }

    /**
     * Return an array with all possible answers for a given sid/field combination
     *
     * @param array $field    Field from getFieldMap function
     */
    protected function _getMultiOptions($field)
    {
        $scaleId = isset($field['scale_id']) ? $field['scale_id'] : 0;
        $qid     = $field['qid'];

        switch ($field['type']) {
            case 'F':
            case 'H':
            case 'L':
            case 'O':
            case 'R':
            case '1':
            case '!':
                $answers = $this->_getHardAnswers($qid, $scaleId);
                break;

            case ':':
                //Get the labels that could apply!
                $answers = false;
                if ($this->_getQuestionAttribute($qid, 'multiflexible_checkbox')) {
                    $answers = $this->_getFixedAnswers($field['type']);
                }
                break;

            case "C":
            case "E":
            case 'G':
            case 'M':
            case 'P':
            case 'Y':
                $answers = $this->_getFixedAnswers($field['type']);
                break;

            default:
                $answers = false;
        }

        return $answers;
    }

    /**
     * Return a certain question attribute or the default value if it does not exist.
     *
     * @param string $qid
     * @param string $attribute
     * @param mxied $default
     * @return mixed
     */
    protected function _getQuestionAttribute($qid, $attribute, $default = null)
    {
        if (! is_array($this->_attributes)) {
            $this->_attributes = [];
            $attributesTable  = $this->_getQuestionAttributesTableName();
            $questionsTable   = $this->_getQuestionsTableName();
            $surveyTable      = $this->_getSurveysTableName();

            $sql = 'SELECT a.qid, a.attribute, a.value FROM ' . $attributesTable . ' AS a
                LEFT JOIN ' . $questionsTable . ' AS q ON q.qid = a.qid
                LEFT JOIN ' . $surveyTable . ' AS s ON s.sid = q.sid
                WHERE s.sid = ?';

            $attributes = $this->lsDb->fetchAll($sql, $this->sourceSurveyId);

            if (false === $attributes) {
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
    protected function _getQuestionsTranslateTableName()
    {
        return $this->tablePrefix . self::QUESTION_TRANSLATE_TABLE;
    }
}
