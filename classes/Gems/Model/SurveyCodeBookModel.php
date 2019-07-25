<?php


namespace Gems\Model;


use MUtil\Translate\TranslateableTrait;

class SurveyCodeBookModel extends \Gems_Model_PlaceholderModel
{

    use TranslateableTrait;

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

        $this->set('id', 'label', $this->_('Survey ID'));
        $this->set('title', 'label', $this->_('Question code'));
        $this->set('question', 'label', $this->_('Question'));
        $this->set('answers', 'label', $this->_('Answers'));
        $this->set('answer_codes', 'label', $this->_('Answer codes'));        

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
        $questionInformation = $survey->getQuestionInformation(null);

        $firstItem = reset($questionInformation);
        $this->fieldArray = array_keys($firstItem);

        $data = [];
        foreach($questionInformation as $questionTitle=>$information) {
            $data[$questionTitle]['id'] = $this->surveyId;
            $data[$questionTitle]['title'] = $questionTitle;
            $data[$questionTitle]['question'] = $information['question'];
            $data[$questionTitle]['answers'] = null;
            $data[$questionTitle]['answer_codes'] = null;
            if (is_array($information['answers'])) {
                $firstInfo = reset($information['answers']);
                if (count($information['answers']) === 1 && empty($firstInfo)) {
                    continue;
                }
                $answerIds = array_keys($information['answers']);
                $data[$questionTitle]['answers'] = join('|', $information['answers']);
                $data[$questionTitle]['answer_codes'] = join('|', $answerIds);
            }
        }
        return $data;

    }
}