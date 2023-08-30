<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Tracker\Model\Dependency;

use Gems\Tracker;
use Gems\Util\Translated;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @package    Gems
 * @subpackage Tracker\Model\Dependency
 * @since      Class available since version 1.0
 */
class SurveyMaintenanceDependency extends \Zalt\Model\Dependency\DependencyAbstract
{
    protected array $_defaultEffects = ['multiOptions', 'filename'];

    protected array $_dependentOn = ['gsu_id_survey'];

    protected array $_effecteds = ['gsu_result_field', 'gsu_survey_pdf'];

    public function __construct(
        TranslatorInterface $translate,
        protected readonly Tracker $tracker,
        protected readonly Translated $translatedUtil,
    )
    {
        parent::__construct($translate);
    }


    /**
     * @inheritDoc
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $output = [];

//        dump($context);
        if (isset($context['gsu_id_survey'])) {
            $surveyId = $context['gsu_id_survey'];
            $survey = $this->tracker->getSurvey(intval($surveyId));
            $output['gsu_result_field']['multiOptions'] = $this->translatedUtil->getEmptyDropdownArray() +
                    $survey->getQuestionList(null);

            $output['gsu_survey_pdf']['filename'] = $surveyId;
        }

        return $output;
    }
}