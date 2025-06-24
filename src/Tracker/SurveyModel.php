<?php

namespace Gems\Tracker;

use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Gems\Tracker;
use Gems\Tracker\Model\Transform\AddAnswersTransformer;
use Gems\Tracker\Source\SourceInterface;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\String\Str;

class SurveyModel extends GemsJoinModel
{

    /**
     * Constant containing css classname for main questions
     */
    public const CLASS_MAIN_QUESTION = 'question';

    /**
     * Constant containing css classname for sub-questions
     */
    public const CLASS_SUB_QUESTION  = 'question_sub';

    public function __construct(
        protected readonly Survey $survey,
        protected readonly SourceInterface $source,
        protected readonly Tracker $tracker,
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        bool $savable = true
    ) {

        // $modelName = Str::camel(Str::alphaNum($this->survey->getName(), true));
        $modelName = $this->survey->getName();
        parent::__construct('gems__tokens', $metaModelLoader, $sqlRunner, $translate, $modelName, $savable);

        $this->addTable('gems__respondent2org', [
            'gto_id_respondent'   => 'gr2o_id_user',
            'gto_id_organization' => 'gr2o_id_organization'
        ]);

        $this->addTable('gems__reception_codes', [
            'gto_reception_code' => 'grc_id_reception_code'
        ]);
        $this->addTable('gems__surveys', [
            'gto_id_survey' => 'gsu_id_survey'
        ]);
        $this->addTable('gems__groups', [
            'gsu_id_primary_group' => 'ggp_id_group'
        ]);

        // Add relations
        // Add relation fields
        $this->addLeftTable('gems__track_fields', [
            'gto_id_relationfield' => 'gtf_id_field',
            'gtf_field_type = "relation"',
        ]);
        $this->metaModel->set('forgroup', [
            'column_expression' => new Expression('COALESCE(gems__track_fields.gtf_field_name, ggp_name)'),
        ]);

        // Add relation itself
        $this->addLeftTable('gems__respondent_relations', [
            'gto_id_relation' => 'grr_id',
            'gto_id_respondent' => 'grr_id_respondent'
        ]);

        $this->addColumn(new Expression(
            'CONCAT_WS(" ", gems__respondent_relations.grr_first_name, gems__respondent_relations.grr_last_name)'
        ), 'grr_name');
        $this->addColumn(new Expression(
            'CASE WHEN grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN 1 ELSE 0 END'
        ), 'can_be_taken');
        $this->addColumn(new Expression(
            "CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END"
        ), 'row_class');

        $this->addAnswersToModel();
    }

    /**
     * Does the real work
     *
     * @return void
     */
    protected function addAnswersToModel()
    {
        $transformer = new AddAnswersTransformer($this->survey, $this->source, $this->tracker);
        $this->metaModel->addTransformer($transformer);
    }

    public function getSurvey(): Survey
    {
        return $this->survey;
    }

    public function hasNew(): bool
    {
        return false;
    }
}