<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Survey
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Survey;

/**
 * Shows the questions in a survey in a human readavle manner
 *
 * @package    Gems
 * @subpackage Snippets_Survey
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class SurveyQuestionsSnippet extends \MUtil_Snippets_TableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'answers browser table';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    public $showAnswersLimit      = 5;
    public $showAnswersNone       = 'n/a';
    public $showAnswersNoneEnd     = '</em>';
    public $showAnswersNoneStart   = '<em class="disabled">';
    public $showAnswersRemoved    = '&hellip;';
    public $showAnswersSeparator  = ' <span class="separator">|</span> ';
    // public $showAnswersSeparator  = ' <span class="separator">&bull;</span> ';
    // public $showAnswersSeparator  = ' <span class="separator">&ordm;</span> ';
    // public $showAnswersSeparator  = '<span class="separator">&#9002; &#9001;</span>';
    public $showAnswersSepEnd     = '';
    // public $showAnswersSepEnd     = '<span class="separator">&#9002;</span>';
    public $showAnswersSepStart   = '';
    // public $showAnswersSepStart   = '<span class="separator">&#9001;</span>';
    public $showAnswersTranslated = false;
    public $showAnswerTypeEnd     = '</em>';
    public $showAnswerTypeStart   = '<em>';

    /**
     *
     * @var \Gems_Tracker_Survey
     */
    protected $survey;

    /**
     * Required: the id of the survey to show
     *
     * @var int
     */
    protected $surveyId;

    /**
     * Optional: to load from token
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     * Optional: alternative method for passing surveyId or trackId
     *
     * @var array
     */
    protected $trackData;

    /**
     * Optional, alternative way to get $trackId
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Alternative way to get surveyId: the id of the track whose first active round is shown
     *
     * @var int
     */
    protected $trackId;

    /**
     * Add the columns ot the table
     *
     * This is a default implementation, overrule at will
     *
     * @param \MUtil_Html_TableElement $table
     */
    protected function addColumns(\MUtil_Html_TableElement $table)
    {
        $table->thhrow($this->_('Group'), ['class' => 'group']);
        $tr = $table->thead()->tr();
        $tr->th($this->_('Question code'));
        $tr->th(' ');
        $tr->th($this->_('Question'));
        $tr->th($this->_('Answer options'));
        
        $cond    = \MUtil_Html::create('i', ['class' => 'fa fa-code-fork', 'renderClosingTag' => true]);
        $hidden  = \MUtil_Html::create('i', ['class' => 'fa fa-eye-slash', 'renderClosingTag' => true]);
        $visible = \MUtil_Html::create('i', ['class' => 'fa fa-eye', 'renderClosingTag' => true]);

        $oldGroup = null;
        foreach ($this->data as $key => $row) {
            if ($oldGroup !== $row['groupName']) {
                $table->tdrow(['class' => 'group'])->raw($row['groupName']);
                $oldGroup = $row['groupName'];
            }               
            
            $tr = $table->tr();
            $col2 = $visible;
            if ($row['alwaysHidden']) {
                $tr->appendAttrib('class', 'hideAlwaysQuestion');
                $tr->title = $this->_('Hidden question');
                $col2 = $hidden;
            }
            if ($row['hasConditon']) {
                $tr->appendAttrib('class', 'conditionQuestion');
                $tr->title = $this->_('Conditional question');
                $col2 = $cond;
            }
            $tr->td($row['key'], ['class' => $row['class']]);
            $tr->td($col2, ['class' => 'icon']);
            $tr->td(['class' => $row['class']])->raw($row['question']);
            $tr->td($this->showAnswers($row['answers']));
        }
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $div = \MUtil_Html::create('div');

        if ($this->surveyId && $this->data) {
            $div->h3(sprintf($this->_('Questions in survey %s'), $this->survey->getName()));
            $div->append(parent::getHtmlOutput($view));
        } else {
            $this->addMessage($this->_('Survey not found'));
            if ($this->surveyId) {
                $div->pInfo(sprintf($this->_('Survey %s does not exist.'), $this->surveyId));
            } else {
                $div->pInfo($this->_('Survey not specified.'));
            }
        }
        $item = $this->menu->getCurrentParent();
        if ($item) {
            $div->append($item->toActionLink($this->_('Cancel'), $this->request));
        }

        return $div;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        // Apply translations
        if (! $this->showAnswersTranslated) {
            // Here, not in e.g. __construct as these vars may be set during initiation
            $this->showAnswersNone       = $this->_($this->showAnswersNone);
            $this->showAnswersRemoved    = $this->_($this->showAnswersRemoved);
            $this->showAnswersSeparator  = $this->_($this->showAnswersSeparator);
            $this->showAnswersTranslated = true;
        }

        // Overrule any setting of these values from source
        $this->data = null;
        $this->repeater = null;

        if (! $this->surveyId) {
            if (($this->token instanceof \Gems_Tracker_Token) && $this->token->exists) {
                $this->surveyId = $this->token->getSurveyId();
                
            } elseif ($this->trackData && (! $this->trackId)) {
                // Look up key values from trackData
                if (isset($this->trackData['gsu_id_survey'])) {
                    $this->surveyId = $this->trackData['gsu_id_survey'];
                } elseif (isset($this->trackData['gro_id_survey'])) {
                    $this->surveyId = $this->trackData['gro_id_survey'];
                } elseif (! $this->trackId) {
                    if (isset($this->trackData['gtr_id_track'])) {
                        $this->trackId = $this->trackData['gtr_id_track'];
                    } elseif (isset($this->trackData['gro_id_track'])) {
                        $this->trackId = $this->trackData['gro_id_track'];
                    }
                }
            }

            if ((! $this->trackId) && $this->trackEngine) {
                $this->trackId = $this->trackEngine->getTrackId();
            }

            if ($this->trackId && (! $this->surveyId)) {
                // Use the track ID to get the id of the first active survey
                $this->surveyId = $this->db->fetchOne('SELECT gro_id_survey FROM gems__rounds WHERE gro_active = 1 AND gro_id_track = ? ORDER BY gro_id_order', $this->trackId);
            }
        }
        // \MUtil_Echo::track($this->surveyId, $this->trackId);

        // Get the survey
        if ($this->surveyId && (! $this->survey instanceof \Gems_Tracker_Survey)) {
            $this->survey = $this->loader->getTracker()->getSurvey($this->surveyId);
        }
        // Load the data
        if (($this->survey instanceof \Gems_Tracker_Survey) && $this->survey->exists) {
            $this->data = \MUtil_Ra::addKey($this->survey->getQuestionInformation($this->locale->getLanguage()), 'key');
            //\MUtil_Echo::track($this->data);
        }

        return parent::hasHtmlOutput();
    }

    public function showAnswers($answers)
    {
        if ($answers) {
            if (is_array($answers)) {
                if (count($answers) == 1) {
                    return reset($answers);
                }

                if (count($answers) > $this->showAnswersLimit) {
                    $border_limit = intval($this->showAnswersLimit / 2);
                    $newAnswers = array_slice($answers, 0, $border_limit);
                    $newAnswers[] = $this->showAnswersRemoved;
                    $newAnswers = array_merge($newAnswers, array_slice($answers, -$border_limit));

                    $answers = $newAnswers;
                }
                return \MUtil_Html::raw($this->showAnswersSepStart . implode($this->showAnswersSeparator, $answers) . $this->showAnswersSepEnd);
            } else {
                return \MUtil_Html::raw($this->showAnswerTypeStart . $answers . $this->showAnswerTypeEnd);
            }
        } else {
            return \MUtil_Html::raw($this->showAnswersNoneStart . $this->showAnswersNone . $this->showAnswersNoneEnd);
        }
    }
}
