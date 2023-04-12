<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker;

use Gems\Model\JoinModel;
use Gems\Tracker\Model\AddAnswersTransformer;
use Gems\Tracker\Source\SourceInterface;

/**
 * More correctly a Survey ANSWERS Model as it adds answers to token information/
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class SurveyModel extends JoinModel
{
    /**
     * Constant containing css classname for main questions
     */
    const CLASS_MAIN_QUESTION = 'question';

    /**
     * Constant containing css classname for subquestions
     */
    const CLASS_SUB_QUESTION  = 'question_sub';

    /**
     *
     * @var SourceInterface
     */
    protected $source;

    /**
     *
     * @var Survey
     */
    protected $survey;

    /**
     *
     * @param Survey $survey
     * @param SourceInterface $source
     */
    public function __construct(Survey $survey, SourceInterface $source)
    {
        parent::__construct($survey->getName(), 'gems__tokens', 'gto');

        $this->addTable('gems__reception_codes',  array('gto_reception_code' => 'grc_id_reception_code'));
        $this->addTable('gems__surveys',          array('gto_id_survey' => 'gsu_id_survey'));
        $this->addTable('gems__groups',           array('gsu_id_primary_group' => 'ggp_id_group'));

        // Add relations
        // Add relation fields
        $this->addLeftTable('gems__track_fields', ['gto_id_relationfield' => 'gtf_id_field', 'gtf_field_type = "relation"']);
        $this->set('forgroup', 'column_expression', new \Zend_Db_Expr('COALESCE(gems__track_fields.gtf_field_name, ggp_name)'));

        // Add relation itself
        $this->addLeftTable('gems__respondent_relations', array('gto_id_relation' => 'grr_id', 'gto_id_respondent' => 'grr_id_respondent'));

        $this->addColumn(new \Zend_Db_Expr(
                'CONCAT_WS(" ", gems__respondent_relations.grr_first_name, gems__respondent_relations.grr_last_name)'
                ), 'grr_name');
        $this->addColumn(new \Zend_Db_Expr(
                'CASE WHEN grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN 1 ELSE 0 END'
                ), 'can_be_taken');
        $this->addColumn(new \Zend_Db_Expr(
                "CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END"
                ), 'row_class');

        $this->source = $source;
        $this->survey = $survey;
        $this->addAnswersToModel();
    }

    /**
     * Does the real work
     *
     * @return void
     */
    protected function addAnswersToModel()
    {
        $transformer = new AddAnswersTransformer($this->survey, $this->source);
        $this->addTransformer($transformer);
    }

    /**
     *
     * @return \Gems\Tracker\Survey
     */
    public function getSurvey()
    {
        return $this->survey;
    }

    /**
     * True if this model allows the creation of new model items.
     *
     * @return boolean
     */
    public function hasNew(): bool
    {
        return false;
    }
}
