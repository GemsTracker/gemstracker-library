<?php

/**
 * The Respondent Relation model
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\Communication\CommunicationRepository;
use Gems\Repository\RespondentRepository;
use Gems\Util\Translated;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class RespondentRelationModel extends JoinModel
{

    /**
     * @var CommunicationRepository
     */
    protected $communicationRepository;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    public function __construct() {
        parent::__construct('respondent_relation', 'gems__respondent_relations', 'grr');

        $this->addTable('gems__respondent2org', ['gr2o_id_user' => 'grr_id_respondent'], null, false);

        $keys = $this->_getKeysFor('gems__respondent2org');
        $keys['rid'] = 'grr_id';
        $this->setKeys($keys);

        // Do not really delete but make inactive so we can always display old relations
        $this->setDeleteValues('grr_active', 0);

        $this->addColumn(
            new \Zend_Db_Expr("CASE WHEN grr_active = 1 THEN '' ELSE 'deleted' END"),
            'row_class'
        );
    }

    public function applyBrowseSettings()
    {
        $this->addFilter(['grr_active'=>1]);
        $this->set('grr_type',
                'label', $this->_('Relation type'), 'description', $this->_('Father, mother, etc.'));
        $this->set('grr_gender', 'label', $this->_('Gender'), 'elementClass', 'radio', 'separator', '', 'multiOptions', $this->translatedUtil->getGenders());
        $this->set('grr_first_name', 'label', $this->_('First name'));
        $this->set('grr_last_name', 'label', $this->_('Last name'));
        $this->set('grr_birthdate', 'label', $this->_('Birthday'), 'dateFormat', 'd-m-Y', 'elementClass', 'Date');
        $this->set('grr_email', 'label', $this->_('E-Mail'));
        $this->set('grr_mailable', 'label', $this->_('May be mailed'), 'multiOptions', $this->communicationRepository->getRespondentMailCodes());
    }

    public function applyDetailSettings()
    {
        $this->applyBrowseSettings();

        $this->set('grr_id_user', [
            'elementClass' => 'Hidden',
        ]);

        $this->set('grr_comments', 'label', $this->_('Comments'), 'elementClass', 'TextArea', 'rows', 4, 'cols', 60);
        $this->set('grr_birthdate', 'jQueryParams', ['defaultDate' => '-30y', 'maxDate' => 0, 'yearRange' => 'c-130:c0']
        );
        $this->set('grr_mailable', 'elementClass', 'radio', 'separator', '');
    }

    /**
     * Return an object for a row of this model
     *
     * @param int $respondentId
     * @param int $relationId
     * @return \Gems\Model\RespondentRelationInstance
     */
    public function getRelation($respondentId, $relationId)
    {
        $filter = [
            'grr_id_respondent' => $respondentId,        // Just a safeguard to make sure we get only relations for this patient
            'grr_id'            => $relationId
        ];

        $data = $this->loadFirst($filter);

        if (!$data) {
            $data = [];
        }

        $relationObject = $this->loader->getInstance('Model\\RespondentRelationInstance', $this, $data);

        return $relationObject;
    }

    /**
     * Get the relations for a given respondentId or patientNr + organizationId combination
     *
     * @param int $respondentId
     * @param string $patientNr
     * @param int $organizationId
     * @return array
     */
    public function getRelationsFor(int $respondentId, ?string $patientNr = null, int $organizationId = null, bool $onlyActive = true)
    {
        static $relationsCache = array();

        if (is_null($respondentId)) {
            $respondentId = $this->respondentRepository->getRespondentId($patientNr, $organizationId);
        }

        if (!array_key_exists($respondentId, $relationsCache)) {
            $relations = array();
            $filter = array('grr_id_respondent'=>$respondentId);
            if ($onlyActive) {
                $filter['grr_active'] = 1;
            }
            $rawRelations = $this->load($filter);
            foreach ($rawRelations as $relation)
            {
                $relations[$relation['grr_id']] = join(' ', array($relation['grr_type'], $relation['grr_first_name'], $relation['grr_last_name']));
            }
            $relationsCache[$respondentId] = $relations;
        }

        return $relationsCache[$respondentId];
    }
}