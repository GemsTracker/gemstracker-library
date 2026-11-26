<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Source;

/**
 * A fieldmap object adds LS source code knowledge and interpretation to the database data
 * about a survey. This enables the code to work with the survey object.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.0
 */
class LimeSurvey7m00FieldMap extends LimeSurvey5m00FieldMap
{
    public const QUESTION_COMMENT_FIELD_SUFFIX = '_Ccomment';
    public const QUESTION_OTHER_FIELD_SUFFIX = '_Cother';
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
