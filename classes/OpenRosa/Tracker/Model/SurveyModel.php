<?php

/**
 *
 * @package    OpenRosa
 * @subpackage Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace OpenRosa\Tracker\Model;

/**
 * More correctly a Survey ANSWERS Model as it adds answers to token information
 *
 * @package    OpenRosa
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class SurveyModel extends \Gems_Tracker_SurveyModel
{
    protected $mainTablePrefix = 'orf';

    protected $relatedTablePrefix = 'orfr';

    /**
     * Add a 'submodel' field to the model.
     *
     * You get a nested join where a set of rows is placed in the $name field
     * of each row of the parent model.
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $joins The join fields for the sub model
     * @param string $name Optional 'field' name, otherwise model name is used
     * @return \MUtil_Model_Transform_NestedTransformer The added transformer
     */
    public function addListModel(\MUtil_Model_ModelAbstract $model, array $joins, $name = null)
    {
        if (null === $name) {
            $name = $model->getName();
        }

        $trans = new \MUtil_Model_Transform_NestedTransformer();
        $trans->skipSave = true;
        $trans->addModel($model, $joins);

        $this->addTransformer($trans);
        $this->set($name,
            'model', $model,
            'elementClass', 'FormTable',
            'type', \MUtil_Model::TYPE_CHILD_MODEL
        );

        $bridge = $model->getBridgeFor('table');
        $this->set($name, 'formatFunction', array($bridge, 'displayListTable'));

        return $trans;
    }

    /**
     * Add a 'submodel' field to the model.
     *
     * You get a nested join where a set of rows is placed in the $name field
     * of each row of the parent model.
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $joins The join fields for the sub model
     * @param string $name Optional 'field' name, otherwise model name is used
     * @return \MUtil_Model_Transform_NestedTransformer The added transformer
     */
    public function addModel(\MUtil_Model_ModelAbstract $model, array $joins, $name = null)
    {
        if (null === $name) {
            $name = $model->getName();
        }

        $trans = new \MUtil_Model_Transform_NestedTransformer();
        $trans->skipSave = true;
        $trans->addModel($model, $joins);

        $this->addTransformer($trans);
        $this->set($name,
            'model', $model,
            'elementClass', 'FormTable',
            'type', \MUtil_Model::TYPE_CHILD_MODEL
        );

        $bridge = $model->getBridgeFor('table');
        $this->set($name, 'formatFunction', array($bridge, 'displaySubTable'));

        return $trans;
    }

    /**
     *
     * @return string
     */
    public function getRelatedFieldPrefix()
    {
        return $this->relatedTablePrefix;
    }

    /**
     *
     * @return string
     */
    public function getTableFieldPrefix()
    {
        return $this->mainTablePrefix;
    }

    /**
     *
     * @return string
     */
    public function getTableIdField()
    {
        return $this->mainTablePrefix . '_id';
    }

    /**
     * Helper function that procesess the raw data before a save.
     *
     * @param array $row Row array containing saved (and maybe not saved data)
     * @return array Nested
     */
    public function processBeforeSave(array $row)
    {
        if ($this->getMeta('nested', false)) {
            $nestedNames = $this->getMeta('nestedNames');
            foreach($nestedNames as $nestedName) {
                if (isset($row[$nestedName])) {
                    foreach($row[$nestedName] as $key => $answer) {
                        $a = array_shift($answer);
                        if (empty($a)) {
                            unset($row[$nestedName][$key]);
                        }
                    }
                }
            }
        }

        if (!isset($row['gto_completion_time']) || $row['gto_completion_time']) {
            $row['gto_completion_time'] = new \MUtil_Date;
        }

        $row = parent::processBeforeSave($row);

        return $row;
    }
}