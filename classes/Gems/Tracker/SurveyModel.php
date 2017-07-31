<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Tracker\Model\AddAnswersTransformer;

/**
 * More correctly a Survey ANSWERS Model as it adds answers to token information/
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_SurveyModel extends \Gems_Model_JoinModel
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
     * @var \Gems_Tracker_Source_SourceInterface
     */
    protected $source;

    /**
     *
     * @var \Gems_Tracker_Survey
     */
    protected $survey;

    /**
     *
     * @param \Gems_Tracker_Survey $survey
     * @param \Gems_Tracker_Source_SourceInterface $source
     */
    public function __construct(\Gems_Tracker_Survey $survey, \Gems_Tracker_Source_SourceInterface $source)
    {
        parent::__construct($survey->getName(), 'gems__tokens', 'gto');

        $this->addTable('gems__reception_codes',  array('gto_reception_code' => 'grc_id_reception_code'));

        // Add relations
        // Add relation fields
        $this->addLeftTable('gems__track_fields',         array('gto_id_relationfield' => 'gtf_id_field', 'gtf_field_type = "relation"'));
        // Add relation itself
        $this->addLeftTable('gems__respondent_relations', array('gto_id_relation' => 'grr_id', 'gto_id_respondent' => 'grr_id_respondent'));

        $this->addColumn(new Zend_Db_Expr(
                'CONCAT_WS(" ", gems__respondent_relations.grr_first_name, gems__respondent_relations.grr_last_name)'
                ), 'grr_name');
        $this->addColumn(new Zend_Db_Expr(
                'CASE WHEN grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN 1 ELSE 0 END'
                ), 'can_be_taken');
        $this->addColumn(new Zend_Db_Expr(
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
     * @return \Gems_Tracker_Survey
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
    public function hasNew()
    {
        return false;
    }
}
