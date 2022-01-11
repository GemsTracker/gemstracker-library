<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * A fieldmap object adds LS source code knowledge and interpretation to the database data
 * about a survey. This enables the code to work with the survey object.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Source_LimeSurvey1m9FieldMap
{
    const ANSWERS_TABLE      = 'answers';
    const ATTRIBUTES_TABLE   = 'question_attributes';
    const CONDITIONS_TABLE   = 'conditions';
    const GROUPS_TABLE       = 'groups';
    const QUESTIONS_TABLE    = 'questions';

    /**
     * Internal type, used for startdate, submitdate, datestamp fields
     */
    const INTERNAL           = 'stamp';

    protected $_answers;
    protected $_attributes;
    protected $_fieldMap;
    protected $_hardAnswers;
    protected $_titlesMap;

    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * The language of this fieldmap
     *
     * @var string
     */
    protected $language;

    /**
     * Limesurvey database adapter
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $lsDb;

    protected $tableMetaData;

    /**
     * Prefix to add to standard table name
     *
     * @var string
     */
    protected $tablePrefix;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * The Lime Survey survey Id
     *
     * @var int
     */
    protected $sourceSurveyId;

    /**
     * The GemsTracker source id
     *
     * @var int
     */
    protected $sourceId;

    /**
     * Construct a fieldmap object to add LS source code knowledge and interpretation to the database data about a survey.
     *
     * @param int $sourceSurveyId            Survey ID
     * @param string $language               (ISO) Language
     * @param \Zend_Db_Adapter_Abstract $lsDb The Lime Survey database connection
     * @param \Zend_Translate $translate      A translate object
     * @param string $tablePrefix              The prefix to use for all LS tables (in this installation)
     * @param \Zend_Cache_Core $cache
     */
    public function __construct($sourceSurveyId, $language, \Zend_Db_Adapter_Abstract $lsDb, \Zend_Translate $translate, $tablePrefix, \Zend_Cache_Core $cache, $sourceId)
    {
        $this->sourceSurveyId = $sourceSurveyId;
        $this->language       = $language;
        $this->lsDb           = $lsDb;
        $this->translate      = $translate;
        $this->tablePrefix    = $tablePrefix;
        $this->cache          = $cache;
        $this->sourceId       = $sourceId;
    }

    /**
     * The answers table contains code => answer sets per question/language/scale_id
     *
     * @return string name of the answers table
     */
    protected function _getAnswersTableName()
    {
        return $this->tablePrefix . self::ANSWERS_TABLE;
    }

    /**
     * The groups table contains group texts per group id / language
     *
     * @return string name of the groups table
     */
    protected function _getGroupsTableName()
    {
        return $this->tablePrefix . self::GROUPS_TABLE;
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
                $answers['']  = '';
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
            $qTable  = $this->_getQuestionsTableName();

            $sql = 'SELECT a.*, q.other FROM ' . $qaTable . ' AS a
                LEFT JOIN ' . $qTable . ' AS q ON q.qid = a.qid AND q.language = a.language
                WHERE q.sid = ? AND q.language = ? ORDER BY a.qid, a.scale_id, sortorder';

            $this->_hardAnswers = array();
            if ($rows = $this->lsDb->fetchAll($sql, array($this->sourceSurveyId, $this->language))) {
                foreach ($rows as $row) {
                    $this->_hardAnswers[$row['qid']][$row['scale_id']][$row['code']] = $this->removeMarkup($row['answer']);
                    if ($row['other']=='Y') {
                        $this->_hardAnswers[$row['qid']][$row['scale_id']]['-oth-'] = $this->removeMarkup($this->_getQuestionAttribute($row['qid'], 'other_replace_text', $this->translate->_('Other')));
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
        $this->_fieldMap = $this->cache->load($cacheId);

        if (false === $this->_fieldMap) {
            $aTable = $this->_getQuestionAttributesTableName();
            $cTable = $this->_getQuestionConditonsTableName();
            $gTable = $this->_getGroupsTableName();
            $qTable = $this->_getQuestionsTableName();

            $sql    = "
                SELECT q.sid, q.type, q.qid, q.gid, q.question, q.title, q.help,
                    q.other, q.question_order,
                    g.group_order, g.group_name, g.description,
                    sq.title AS sq_title, sq.question_order, sq.question AS sq_question, sq.scale_id,                
                    at.value AS hidden, 
                    CASE WHEN q.relevance IS NULL OR q.relevance = '' OR q.relevance = 1 OR NOT EXISTS (SELECT * FROM $cTable AS cn WHERE cn.qid = q.qid) THEN 0 ELSE 1 END AS hasConditon
                FROM $qTable AS q
                    LEFT JOIN $gTable AS g ON q.sid = g.sid AND q.gid = g.gid AND q.language=g.language
                    LEFT JOIN $qTable AS sq ON q.qid = sq.parent_qid AND q.language = sq.language
                    LEFT JOIN (SELECT * FROM $aTable WHERE attribute = 'hidden' AND (language = ''  OR language IS NULL)) AS at ON q.qid = at.qid
                WHERE g.sid = ? AND g.language = ? AND q.parent_qid = 0
                ORDER BY g.group_order, q.question_order, sq.scale_id DESC, sq.question_order";

            // \MUtil_Echo::track($sql, $this->sourceSurveyId, $this->language);
            $rows = $this->lsDb->fetchAll($sql, array($this->sourceSurveyId, $this->language));

            $rowscount = count($rows);
            foreach($rows as &$row) {
                $row['sgq'] = $row['sid'] . 'X' . $row['gid'] . 'X' . $row['qid'];
            }
            unset($row);    // To prevent strange error

            $map = array();
            for ($i = 0; $i < $rowscount; $i++) {
                $row = $rows[$i];
                $other  = ($row['other'] == 'Y');
                $row['hidden']      = (boolean) (1 == $row['hidden']);
                $row['hasConditon'] = (boolean) (1 == $row['hasConditon']);

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
            // \MUtil_Echo::track($map);
            // Use a tag (for cleaning if supported) and 1 day lifetime, maybe clean cache on sync survey?
            $this->cache->save($this->_fieldMap, $cacheId, array('fieldmap'), 86400);   //60*60*24=86400
        }

        return $this->_fieldMap;
    }

    /**
     * Return an array with all possible answers for a given sid/field combination
     *
     * @param $field    Field from getFieldMap function
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
     * Return an array with all possible answers for a given sid/field combination
     *
     * @param $field    Field from getFieldMap function
     */
    private function _getPossibleAnswers($field)
    {
        $scaleId = isset($field['scale_id']) ? $field['scale_id'] : 0;
        $code    = $field['code'];

        if (isset($this->_answers[$code][$scaleId])) {
            return $this->_answers[$code][$scaleId];
        }

        $qid  = $field['qid'];

        // Get the real multioption
        $answers = $this->_getMultiOptions($field);
        if (!$answers) {
            // If not present, try to find ranges or a description
            switch ($field['type']) {
                case ':':
                    //Get the labels that could apply!
                    if ($this->_getQuestionAttribute($qid, 'multiflexible_checkbox')) {
                        $answers = $this->_getFixedAnswers($field['type']);
                        break;
                    }
                    $maxvalue  = $this->_getQuestionAttribute($qid, 'multiflexible_max', 10);
                    $minvalue  = $this->_getQuestionAttribute($qid, 'multiflexible_min', 1);
                    $stepvalue = $this->_getQuestionAttribute($qid, 'multiflexible_step', ($minvalue > $maxvalue ? 1 : -1));
                    $answers   = (array) range($minvalue, $maxvalue, $stepvalue);
                    $answers   = array_combine($answers, $answers);
                    break;
                case '5':
                case 'A':
                    $answers = range(1, 5);
                    $answers = array_combine($answers, $answers);
                    break;
                case 'B':
                    $answers = range(1, 10);
                    $answers = array_combine($answers, $answers);
                    break;
                case 'K':
                    $maxvalue  = $this->_getQuestionAttribute($qid, 'slider_max', 100);
                    $minvalue  = $this->_getQuestionAttribute($qid, 'slider_min', 0);
                    $stepvalue = $this->_getQuestionAttribute($qid, 'slider_accuracy', 1);
                    $answers   = range($minvalue, $maxvalue, $stepvalue);
                    $answers   = array_combine($answers, $answers);
                    break;
                case 'D':
                    $answers = $this->translate->_('Date');
                    break;
                case 'N':
                    $answers = $this->translate->_('Free number');
                    break;
                case 'X':
                    $answers = '';  // Boilerplate, this has no answer
                    break;
                case 'T':
                    $answers = $this->translate->_('Free text (long)');
                    break;
                case 'U':
                    $answers = $this->translate->_('Free text (very long)');
                    break;
                default:
                    $answers = $this->translate->_('Free text');
            }
        }
        $this->_answers[$code][$scaleId] = $answers;

        return $this->_answers[$code][$scaleId];
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
                LEFT JOIN ' . $surveyTable . ' AS s ON s.sid = q.sid AND q.language = s.language
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
     * The question attributes table contains all non-translateable settings for a question, e.g. 'page_break' or 'hidden'
     *
     * @return string name of the question attributes table
     */
    protected function _getQuestionAttributesTableName()
    {
        return $this->tablePrefix . self::ATTRIBUTES_TABLE;
    }

    /**
     * The question conditions table contains all conditions not in the relevance equation
     *
     * @return string name of the question attributes table
     */
    protected function _getQuestionConditonsTableName()
    {
        return $this->tablePrefix . self::CONDITIONS_TABLE;
    }

    /**
     * The question table contains one row per language per question per survey in LS.
     *
     * All non-text data is replicated over all the question/language rows.
     *
     * @return string name of the questions table
     */
    protected function _getQuestionsTableName()
    {
        return $this->tablePrefix . self::QUESTIONS_TABLE;
    }

    /**
     * The survey table contains one row per each survey in LS
     *
     * @return string name of the surveys table
     */
    protected function _getSurveysTableName()
    {
        return $this->tablePrefix . \Gems_Tracker_Source_LimeSurvey1m9Database::SURVEYS_TABLE;
    }

    /**
     * There exists a survey table for each active survey. The table contains the answers to the survey
     *
     * @return string Name of survey table for this survey
     */
    protected function _getSurveyTableName()
    {
        return $this->tablePrefix . \Gems_Tracker_Source_LimeSurvey1m9Database::SURVEY_TABLE . $this->sourceSurveyId;
    }

    /**
     * Returns a map of databasecode => questioncode
     *
     * @return array
     */
    private function _getTitlesMap() {
        if (! is_array($this->_titlesMap)) {
            $map    = $this->_getMap();
            $result = array();

            foreach ($map as $key => $row) {

                // Title does not have to be unique. So if a title is used
                // twice we only use it for the first result.
                if (isset($row['code']) && $row['code'] && (! isset($result[$row['code']]))) {
                    $result[$row['code']] = $key;
                }
            }

            $this->_titlesMap = $result;
        }

        return $this->_titlesMap;
    }

    /**
     * Returns
     *
     * @param $field    Field from _getMap function
     * @return int \MUtil_Model::TYPE_ constant
     */
    protected function _getType($field)
    {
        switch ($field['type']) {
            case ':':
                //Get the labels that could apply!
                if ($this->_getQuestionAttribute($field['qid'], 'multiflexible_checkbox')) {
                    return \MUtil_Model::TYPE_STRING;
                }

            case '5':
            case 'A':
            case 'B':
            case 'K':
            case 'N':
                return \MUtil_Model::TYPE_NUMERIC;

            case 'D':
                //date_format
                if ($format = $this->_getQuestionAttribute($field['qid'], 'date_format')) {
                    $date = false;
                    $time = false;
                    if (strpos($format, ':')) {
                        $time = true;
                    }

                    // Find any of -\/ to mark as a date
                    $regExp = '/.*[-\/\\\\]+.*/m';
                    $matches = [];
                    preg_match($regExp, $format, $matches);
                    if (count($matches) > 0) {
                        $date = true;
                    }

                    if ($date && !$time) {
                        return \MUtil_Model::TYPE_DATE;
                    } elseif (!$date && $time) {
                        return \MUtil_Model::TYPE_TIME;
                    } else {
                        return \MUtil_Model::TYPE_DATETIME;
                    }
                }
                return \MUtil_Model::TYPE_DATE;

            case 'X':
                return \MUtil_Model::TYPE_NOVALUE;

            case self::INTERNAL:
                // Not a limesurvey type, used internally for meta data
                return \MUtil_Model::TYPE_DATETIME;

            default:
                return \MUtil_Model::TYPE_STRING;
        }
    }

    /**
     * Applies the fieldmap data to the model
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    public function applyToModel(\MUtil_Model_ModelAbstract $model)
    {
        $map    = $this->_getMap();
        $oldfld = null;
        $parent = null;

        foreach ($map as $name => $field) {

            $tmpres = array();
            $tmpres['thClass']         = \Gems_Tracker_SurveyModel::CLASS_MAIN_QUESTION;
            if (isset($field['hidden']) && $field['hidden']) {
                $tmpres['thClass']      .= ' hideAlwaysQuestion';
                $tmpres['alwaysHidden'] = $field['hidden'];
            }
            if (isset($field['hasConditon']) && $field['hasConditon']) {
                $tmpres['thClass']     .= ' conditionQuestion';
                $tmpres['hasConditon'] = $field['hasConditon'];
            }

            $tmpres['group']           = $field['gid'];
            $tmpres['groupName']       = isset($field['group_name']) ? preg_replace('/&(?!(#[0-9]{2,4}|[A-z]{2,6})+;)/', '&amp;', $field['group_name']) : null;
            $tmpres['type']            = $this->_getType($field);
            $tmpres['survey_question'] = true;
            $tmpres['sourceId']        = $name;

            if ($tmpres['type'] === \MUtil_Model::TYPE_DATETIME || $tmpres['type'] === \MUtil_Model::TYPE_DATE || $tmpres['type'] === \MUtil_Model::TYPE_TIME) {
                if ($dateFormats = $this->getDateFormats($name, $tmpres['type'])) {
                    $tmpres = $tmpres + $dateFormats;
                }
            }

            if ($options = $this->_getMultiOptions($field)) {
                $tmpres['multiOptions'] = $options;

                // Limesurvey defines numeric options as string, maybe we can convert it back
                if ($tmpres['type'] === \MUtil_Model::TYPE_STRING) {
                    $changeType = true;
                    foreach(array_keys($options) as $key) {
                        // But if we find a numeric = false, we leave as is
                        if(!is_numeric($key)) {
                            $changeType = false;
                            break;
                        }
                    }
                    if ($changeType == true) {
                        $tmpres['type'] = \MUtil_Model::TYPE_NUMERIC;
                    }
                }
            }

            if ($tmpres['type'] === \MUtil_Model::TYPE_NUMERIC && !isset($tmpres['multiOptions'])) {
                $tmpres['formatFunction'] = array($this, 'handleFloat');
            }

            if (isset($field['question'])) {
                $tmpres['label'] = \MUtil_Html::raw($this->removeMarkup($field['question']));
                $tmpres['label_raw'] = \MUtil_Html::raw($field['question']);
            }
            if (isset($field['help']) && $field['help']) {
                $tmpres['description'] = \MUtil_Html::raw($this->removeMarkup($field['help']));
            }

            $oldQid = isset($oldfld['qid']) ? $oldfld['qid'] : 0;
            // Juggle the labels for sub-questions etc..
            if (isset($field['sq_question'])) {
                if ($oldQid !== $field['qid']) {
                    // Add non answered question for grouping and make it the current parent
                    //$parent = '_' . $name . '_';
                    $parent = $field['title'];
                    $model->set($parent, $tmpres);
                    $model->set($parent, 'type', \MUtil_Model::TYPE_NOVALUE);
                }
                if (isset($field['sq_question1'])) {
                    $tmpres['label'] = \MUtil_Html::raw(sprintf(
                            $this->translate->_('%s: %s'),
                            $this->removeMarkup($field['sq_question']),
                            $this->removeMarkup($field['sq_question1'])
                            ));
                    $tmpres['label_raw'] = \MUtil_Html::raw(sprintf(
                            $this->translate->_('%s: %s'),
                            $field['sq_question'],
                            $field['sq_question1']
                            ));
                } else {
                    $tmpres['label'] = \MUtil_Html::raw($this->removeMarkup($field['sq_question']));
                    $tmpres['label_raw'] = \MUtil_Html::raw($field['sq_question']);
                }
                $tmpres['thClass'] = \Gems_Tracker_SurveyModel::CLASS_SUB_QUESTION;
            }

            // Code does not have to be unique. So if a title is used
            // twice we only use it for the first result.
            if (isset($field['code']) && (! $model->has($field['code']))) {
                $name = $field['code'];
            }

            // Parent storage
            if (\Gems_Tracker_SurveyModel::CLASS_SUB_QUESTION !== $tmpres['thClass']) {
                $parent = $name;
            } elseif ($parent) {
                // Add the name of the parent item
                $tmpres['parent_question'] = $parent;
            }

            $model->set($name, $tmpres);

            $oldfld = $field;
        }
    }

    protected function getDateFormats($fieldname, $type)
    {
        if ($dataType = $this->getFieldTableDataType($fieldname)) {
            if ($dataType == 'datetime' || $dataType == 'timestamp') {
                $tmpres['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
            } elseif ($dataType == 'date') {
                $tmpres['storageFormat'] = 'yyyy-MM-dd';
            }
        } else {
            if ($type === \MUtil_Model::TYPE_DATETIME || $type === \MUtil_Model::TYPE_TIME) {
                $tmpres['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
            } elseif ($type === \MUtil_Model::TYPE_DATE) {
                $tmpres['storageFormat'] = 'yyyy-MM-dd';
            }
        }

        if ($type === \MUtil_Model::TYPE_DATETIME) {
            $tmpres['dateFormat']    = 'dd MMMM yyyy HH:mm';
        } elseif ($type === \MUtil_Model::TYPE_DATE) {
            $tmpres['dateFormat']    = 'dd MMMM yyyy';
        } elseif ($type === \MUtil_Model::TYPE_TIME) {
            $tmpres['dateFormat']    = 'HH:mm:ss';
        }

        return $tmpres;
    }

    protected function getFieldTableDataType($fieldname)
    {
        if (!$this->tableMetaData) {
            $this->loadTableMetaData();
        }

        if (isset($this->tableMetaData[$fieldname])) {
            return $this->tableMetaData[$fieldname]['DATA_TYPE'];
        }

        return false;
    }

    /**
     * Returns an array of array with the structure:
     *      question => string,
     *      class    => question|question_sub
     *      group    => is for grouping
     *      type     => (optional) source specific type
     *      answers  => string for single types,
     *                  array for selection of,
     *                  nothing for no answer
     *
     * @return array Nested array
     */
    public function getQuestionInformation()
    {
        $map    = $this->_getMap();
        $oldfld = null;
        $result = array();

        foreach ($map as $name => $field) {
            if ($field['type'] == self::INTERNAL) {
                continue;
            }

            // \MUtil_Echo::track($field);
            $tmpres = array();
            $tmpres['alwaysHidden'] = $field['hidden'];
            $tmpres['class']        = \Gems_Tracker_SurveyModel::CLASS_MAIN_QUESTION;
            $tmpres['group']        = $field['gid'];
            $tmpres['groupName']    = $this->removeMarkup($field['group_name']);
            $tmpres['hasConditon']  = $field['hasConditon'];
            $tmpres['type']         = $field['type'];
            $tmpres['title']        = $field['title'];
            if (array_key_exists('equation', $field)) {
                $tmpres['equation'] = $field['equation'];
            }

            $oldQid = isset($oldfld['qid']) ? $oldfld['qid'] : 0;
            if ($oldQid !== $field['qid']) {
                $tmpres['question'] = $this->removeMarkup($field['question']);
            }

            // Juggle the labels for sub-questions etc..
            if (isset($field['sq_question'])) {
                if (isset($field['sq_question1'])) {
                    $field['sq_question'] = sprintf($this->translate->_('%s: %s'), $field['sq_question'], $field['sq_question1']);
                }
                if (! isset($tmpres['question'])) {
                    $tmpres['question'] = $this->removeMarkup($field['sq_question']);
                } else {
                    $tmpres['answers'] = array(''); // Empty array prevents "n/a" display

                    // Add non answered question for grouping
                    $result[$field['title']] = $tmpres;

                    // "Next" question
                    $tmpres['question'] = $this->removeMarkup($field['sq_question']);
                }
                $tmpres['class'] = \Gems_Tracker_SurveyModel::CLASS_SUB_QUESTION;
            }
            $tmpres['answers'] = $this->_getPossibleAnswers($field);

            // Title does not have to be unique. So if a title is used
            // twice we only use it for the first result.
            if (isset($field['code']) && (! isset($result[$field['code']]))) {
                $name = $field['code'];
            }
            $result[$name] = $tmpres;

            if (isset($field['sgq'])) {
                $result[$name]['id'] = $field['sgq'];
            }

            $oldfld = $field;
        }
        // \MUtil_Echo::track($result);

        return $result;
    }

    /**
     * Returns an array containing fieldname => label for dropdown list etc..
     *
     * @param string $forType Optional type filter
     * @return array fieldname => label
     */
    public function getQuestionList($forType = false)
    {
        $map     = $this->_getMap();
        $results = array();

        $question = null;
        foreach ($map as $name => $field) {

            // Always need the last field
            if (isset($field['question'])) {
                $question = $this->removeMarkup($field['question']);
            }

            // Optional type check
            if ((! $forType) || ($field['type'] == $forType)) {
                // Juggle the labels for sub-questions etc..
                if (isset($field['sq_question1'])) {
                    $squestion = sprintf($this->translate->_('%s: %s'), $this->removeMarkup($field['sq_question']), $this->removeMarkup($field['sq_question1']));
                } elseif (isset($field['sq_question'])) {
                    $squestion = $this->removeMarkup($field['sq_question']);
                } else {
                    $squestion = null;
                }

                // Title does not have to be unique. So if a title is used
                // twice we only use it for the first result.
                if (isset($field['code']) && (! isset($results[$field['code']]))) {
                    $name = $field['code'];
                }

                if ($question && $squestion) {
                    $results[$name] = sprintf($this->translate->_('%s - %s'), $question, $squestion);;

                } elseif ($question) {
                    $results[$name] = $question;

                } elseif ($squestion) {
                    $results[$name] = sprintf($this->translate->_('- %s'), $squestion);
                } elseif (isset($field['question']) && $field['title']) {
                    // When question is empty, but we have a title
                    $results[$name] = $field['title'];
                }
            }
        }

        return $results;
    }

    /**
     * Function to cast numbers as float, but leave null intact
     * @param  The number to cast to float
     * @return float
     */
    public function handleFloat($value)
    {
        return is_null($value) ? null : (float)$value;
    }

    protected function loadTableMetaData()
    {
        try {
            $tableName = $this->_getSurveyTableName();
            $table = new \Zend_DB_Table(array('name' => $tableName, 'db' => $this->lsDb));
            $info = $table->info();
        } catch (Exception $exc) {
            $info = array('metadata'=>array());
        }

        $this->tableMetaData = $info['metadata'];

        return $this->tableMetaData;
    }

    /**
     * Changes the keys of the values array to the more readable titles
     * also available in LimeSurvey
     *
     * @param array $values
     */
    public function mapKeysToTitles(array $values)
    {
        $map = array_flip($this->_getTitlesMap());

        $result = array();
        foreach ($values as $key => $value) {
            if (isset($map[$key])) {
                $result[$map[$key]] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        // \MUtil_Echo::track($result);

        return $result;
    }


    /**
     * Changes the keys of the values array to the more readable titles
     * also available in LimeSurvey
     *
     * @param array $values
     */
    public function mapTitlesToKeys(array $values)
    {
        $titlesMap = $this->_getTitlesMap();

        $result = array();
        foreach ($values as $key => $value) {
            if (isset($titlesMap[$key])) {
                $result[$titlesMap[$key]] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        // \MUtil_Echo::track($result);

        return $result;
    }

    /**
     * Removes all markup from input
     *
     * @param string $text Input possibly containing html
     * @return string
     */
    public function removeMarkup($text)
    {
        return trim(\MUtil_String::beforeChars(\MUtil_Html::removeMarkup($text, 'b|i|u|em|strong'), '{'));
    }
}
