<?php


namespace Gems\Model;


use MUtil\Translate\TranslateableTrait;

class SurveyCodeBookModel extends \Gems_Model_PlaceholderModel
{

    use TranslateableTrait;
    
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    protected $data;

    /**
     * @var array List of field columns
     */
    protected $fieldArray;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Locale
     */
    public $locale;

    protected $surveyId;

    /**
     * @var \Gems_Tracker
     */
    public $tracker;

    public function __construct($surveyId)
    {
        $this->surveyId = $surveyId;
    }

    public function afterRegistry()
    {
        if (! $this->tracker instanceof \Gems_Tracker) {
            $this->tracker = $this->loader->getTracker();
        }

        $this->data = $this->getData($this->surveyId);
        $survey = $this->tracker->getSurvey($this->surveyId);
        $name = $this->cleanupName($survey->getName()) . '-code-book';

        parent::__construct($name, $this->fieldArray, $this->data);

        $this->initTranslateable();
        $this->resetOrder();
        $this->set('id', 'label', $this->_('Survey ID'));
        $this->set('title', 'label', $this->_('Question code'));
        $this->set('question', 'label', $this->_('Question'));
        $this->set('answer_codes', 'label', $this->_('Answer codes'));
        $this->set('answers', 'label', $this->_('Answers'));

        parent::afterRegistry();
    }

    /**
     * Clean a proposed filename up so it can be used correctly as a filename
     * @param  string $filename Proposed filename
     * @return string           filtered filename
     */
    protected function cleanupName($filename)
    {
        $filename = str_replace(array('/', '\\', ':', ' '), '_', $filename);
        // Remove dot if it starts with one
        $filename = trim($filename, '.');

        return \MUtil_File::cleanupName($filename);
    }

    public function getData($surveyId)
    {
        $survey = $this->tracker->getSurvey($surveyId);
        // Use interface language
        $locale = $this->currentUser->getLocale();
        $questionInformation = $survey->getQuestionInformation($locale);
        if (empty($questionInformation)) {
            // Inactive / deleted survey?
            $this->fieldArray = [];
            return array();
        }
        $firstItem = reset($questionInformation);
        $this->fieldArray = array_keys($firstItem);

        $data = [];
        foreach ($questionInformation as $questionTitle => $information) {
            $answers     = $information['answers'];
            $answerCodes = '';
            if (is_array($answers)) {
                $answerCodes = join('|', array_keys($answers));
                $answers     = join('|', $answers);
                if (empty($answers)) {
                    $answerCodes = '';
                }
            }
            
            if (array_key_exists('equation', $information)) {
                // If there is an equation, we don't have answers
                $answers = $information['equation'];
                $answerCodes = $this->_('Equation');                
            }

            $data[$questionTitle] = [
                'id'           => $this->surveyId,
                'title'        => $questionTitle,
                'question'     => $information['question'],
                'answers'      => $answers,
                'answer_codes' => $answerCodes
            ];
        }
        return $data;
    }
}