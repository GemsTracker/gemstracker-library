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

use Gems\Locale\Locale;
use Gems\Tracker;
use MUtil\Translate\TranslateableTrait;
use OpenSpout\Writer\XLSX\Manager\WorksheetManager;
use Zalt\Base\TranslatorInterface;
use Zalt\String\Str;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2021 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.9,1
 */
class SurveyCodeBookModel extends PlaceholderModel
{

    public function __construct(
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translator,
        protected readonly int $surveyId,
        protected readonly Tracker $tracker,
        protected readonly Locale $locale,
    ) {

        $data   = $this->getData($this->surveyId);
        $survey = $this->tracker->getSurvey($this->surveyId);
        $name   = $this->cleanupName($survey->getName()) . '-code-book';

        parent::__construct($metaModelLoader, $translator, $name, [], $data);

        $this->metaModel->set('id', [
            'label' => $this->translator->_('Survey ID')
        ]);
        $this->metaModel->set('title', [
            'label' => $this->translator->_('Question code'),
        ]);
        $this->metaModel->set('question', [
            'label' => $this->translator->_('Question')
        ]);
        $this->metaModel->set('answer_codes', [
            'label' => $this->translator->_('Answer codes')
        ]);
        $this->metaModel->set('answers', [
            'label' => $this->translator->_('Answers')
        ]);
    }

    /**
     * Clean a proposed filename up so it can be used correctly as a filename
     * @param  string $filename Proposed filename
     * @return string           filtered filename
     */
    protected function cleanupName(string $filename): string
    {
        $filename = trim($filename, '.');

        return Str::snake($filename);
    }

    /**
     * @param int $surveyId
     * @return array
     */
    public function getData(int $surveyId): array
    {
        $survey              = $this->tracker->getSurvey($surveyId);
        $questionInformation = $survey->getQuestionInformation($this->locale->getLanguage());
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
                $answerCodes = $this->translator->_('Equation');
            }

            $data[$questionTitle] = [
                'id'           => $this->surveyId,
                'title'        => $questionTitle,
                'question'     => $information['question'],
                'answers'      => $this->limitCharacters($answers),
                'answer_codes' => $answerCodes
            ];
        }
        return $data;
    }

    protected function limitCharacters(string $value): string
    {
        if (strlen($value > WorksheetManager::MAX_CHARACTERS_PER_CELL)) {
            $value = substr($value, 0, WorksheetManager::MAX_CHARACTERS_PER_CELL - 4) . '...';
        }
        return $value;
    }
}