<?php

namespace Gems\Actions;

use Gems\User\Group;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class ComplianceAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Tracker\\Compliance\\ComplianceTableSnippet';

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Tracker\\Compliance\\ComplianceSearchFormSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = [
        'Tracker\\TokenStatusLegenda',
        'Tracker\\Compliance\\ComplianceLegenda',
        'Generic\\CurrentSiblingsButtonRowSnippet',
        ];

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new \Gems\Model\JoinModel('resptrack' , 'gems__respondent2track');
        $model->addTable('gems__respondent2org', array(
            'gr2t_id_user' => 'gr2o_id_user',
            'gr2t_id_organization' => 'gr2o_id_organization'
            ));
        $model->addTable('gems__respondents', array('gr2o_id_user' => 'grs_id_user'));
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__reception_codes', array('gr2t_reception_code' => 'grc_id_reception_code'));
        $model->addFilter(array('grc_success' => 1));

        $model->resetOrder();
        $model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));
        $model->addColumn(
            "TRIM(CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')))",
            'respondent_name');

        if (! $this->currentUser->isFieldMaskedPartial('respondent_name')) {
            $model->set('respondent_name', 'label', $this->_('Name'));
        }
        $model->set('gr2t_start_date', 'label', $this->_('Start date'));
        $model->set('gr2t_end_date',   'label', $this->_('End date'));

        $filter = $this->getSearchFilter($action !== 'export');
        if (! (isset($filter['gr2t_id_organization']) && $filter['gr2t_id_organization'])) {
            $model->addFilter(array('gr2t_id_organization' => $this->currentUser->getRespondentOrgFilter()));
        }
        if (! (isset($filter['gr2t_id_track']) && $filter['gr2t_id_track'])) {
            $model->setFilter(array('1=0'));
            $this->autofilterParameters['onEmpty'] = $this->_('No track selected...');
            return $model;
        }

        // Add the period filter - if any
        if ($where = \Gems\Snippets\AutosearchFormSnippet::getPeriodFilter($filter, $this->db)) {
            $model->addFilter(array($where));
        }

        $select = $this->db->select();
        $select->from('gems__rounds', array('gro_id_round', 'gro_id_order', 'gro_round_description', 'gro_icon_file'))
                ->joinInner('gems__surveys', 'gro_id_survey = gsu_id_survey', array('gsu_survey_name'))
                ->joinLeft('gems__track_fields', 'gro_id_relationfield = gtf_id_field AND gtf_field_type = "relation"', array())
                ->joinInner('gems__groups', 'gsu_id_primary_group =  ggp_id_group', array())
                ->where('gro_id_track = ?', $filter['gr2t_id_track'])
                ->where('gsu_active = 1')   //Only active surveys
                ->order('gro_id_order');

        $fields['filler'] = new \Zend_Db_Expr('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)');
        $select->columns($fields);

        if (array_key_exists('fillerfilter', $filter)) {
            $select->having('filler = ?', $filter['fillerfilter']);
        }
        $data = $this->db->fetchAll($select);

        if (! $data) {
            return $model;
        }

        $status = $this->util->getTokenData()->getStatusExpression();

        $select = $this->db->select();
        $select->from('gems__tokens', array(
            'gto_id_respondent_track', 'gto_id_round', 'gto_id_token', 'status' => $status, 'gto_result',
            ))->joinInner('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array())
                // ->where('grc_success = 1')
                ->where('gto_id_track = ?', $filter['gr2t_id_track'])
                ->order('grc_success')
                ->order('gto_id_respondent_track')
                ->order('gto_round_order');

        // \MUtil\EchoOut\EchoOut::track($this->db->fetchAll($select));
        $newModel = new \MUtil\Model\SelectModel($select, 'tok');
        $newModel->setKeys(array('gto_id_respondent_track'));

        $transformer = new \MUtil\Model\Transform\CrossTabTransformer();
        $transformer->addCrosstabField('gto_id_round', 'status', 'stat_')
                ->addCrosstabField('gto_id_round', 'gto_id_token', 'tok_')
                ->addCrosstabField('gto_id_round', 'gto_result', 'res_');

        foreach ($data as $row) {
            $name = 'stat_' . $row['gro_id_round'];
            $transformer->set($name, 'label', \MUtil\Lazy::call('substr', $row['gsu_survey_name'], 0, 2),
                    'description', sprintf("%s\n[%s]", $row['gsu_survey_name'], $row['gro_round_description']),
                    'noSort', true,
                    'round', $row['gro_round_description'],
                    'roundIcon', $row['gro_icon_file'],
                    'survey', $row['gsu_survey_name']
                    );
            $transformer->set('tok_' . $row['gro_id_round']);
            $transformer->set('res_' . $row['gro_id_round']);
        }

        $newModel->addTransformer($transformer);
        // \MUtil\EchoOut\EchoOut::track($data);

        $joinTrans = new \MUtil\Model\Transform\JoinTransformer();
        $joinTrans->addModel($newModel, array('gr2t_id_respondent_track' => 'gto_id_respondent_track'));

        $model->resetOrder();
        $model->set('gr2o_patient_nr');
        $model->set('respondent_name');
        $model->set('gr2t_start_date');
        $model->addTransformer($joinTrans);

        // Add masking if needed
        $group = $this->currentUser->getGroup();
        if ($group instanceof Group) {
            $group->applyGroupToModel($model, false);
        }

        return $model;
    }

    /**
     * Get the model for export and have the option to change it before using for export
     * @return
     */
    public function getExportModel()
    {
        $model         = parent::getExportModel();
        $statusColumns = $model->getColNames('label');
        $everyStatus   = $this->util->getTokenData()->getEveryStatus();
        foreach ($statusColumns as $colName) {
            // For the compliance columns, we add the translation for the letter codes and move the decription to the label
            // This way the column shows the full survey name and round description
            if (substr($colName, 0, 5) == 'stat_') {
                $model->set($colName, 'multiOptions', $everyStatus, 'label', $model->get($colName, 'description'));
            }
        }
        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Compliance');
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults()
    {
        if (! isset($this->defaultSearchData['gr2t_id_organization'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $this->defaultSearchData['gr2t_id_organization'] = array_keys($orgs);
        }

        if (!isset($this->defaultSearchData['gr2t_id_track'])) {
            $orgs = $this->currentUser->getRespondentOrganizations();
            $tracks = $this->util->getTrackData()->getTracksForOrgs($orgs);
            if (count($tracks) == 1) {
                $this->defaultSearchData['gr2t_id_track'] = key($tracks);
            }
        }

        return parent::getSearchDefaults();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }
}
