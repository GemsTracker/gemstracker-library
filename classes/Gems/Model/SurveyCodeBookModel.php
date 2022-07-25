<?php


/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model;

use MUtil\Translate\TranslateableTrait;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2021 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.9,1
 */
class SurveyCodeBookModel extends \Gems\Model\PlaceholderModel
{

    use TranslateableTrait;
    
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * @var array List of field columns
     */
    protected $fieldArray;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * @var int 
     */
    protected $surveyId;

    /**
     * @var \Gems\Tracker
     */
    public $tracker;

    /**
     * SurveyCodeBookModel constructor.
     *
     * @param int $surveyId
     */
    public function __construct($surveyId)
    {
        $this->surveyId = $surveyId;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        if (! $this->tracker instanceof \Gems\Tracker) {
            $this->tracker = $this->loader->getTracker();
        }

        $data   = $this->getData($this->surveyId);
        $survey = $this->tracker->getSurvey($this->surveyId);
        $name   = $this->cleanupName($survey->getName()) . '-code-book';

        // We only know the name now!
        parent::__construct($name, [], $data);

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

        return \MUtil\File::cleanupName($filename);
    }

    /**
     * @param int $surveyId
     * @return array
     */
    public function getData($surveyId)
    {
        $survey              = $this->tracker->getSurvey($surveyId);
        $questionInformation = $survey->getQuestionInformation($this->locale);
        if (empty($questionInformation)) {
            // Inactive / deleted survey?
            return [];
        }
        
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
                $answers     = $information['equation'];
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