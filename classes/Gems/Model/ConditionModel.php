<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\Conditions;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class ConditionModel extends \Gems\Model\JoinModel
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     *
     * @param string $name
     */
    public function __construct($name = 'conditions')
    {
        parent::__construct($name, 'gems__conditions', 'gcon');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @param boolean $addCount Add a count in rounds column
     * @return \Gems\Model\ConditionModel
     */
    public function applyBrowseSettings($addCount = true)
    {
        $conditions = $this->loader->getConditions();

        $yesNo = $this->util->getTranslated()->getYesNo();

        $types = $conditions->getConditionTypes();
        reset($types);
        $default = key($types);
        $this->set('gcon_type', 'label', $this->_('Type'),
                'description', $this->_('Determines where the condition can be applied.'),
                'multiOptions', $types,
                'default', $default
                );

        $conditionsClasses = [];
        if ($addCount) { // Are we in a browse mode
            foreach ($types as $type => $val) {
                $conditionsClasses += $conditions->listConditionsForType($type);
            }
        }
        $this->set('gcon_class', 'label', $this->_('Condition'),
                'multiOptions', $conditionsClasses
                );

        $this->set('gcon_name', 'label', $this->_('Name'));
        $this->set('gcon_active', 'label', $this->_('Active'),
                'multiOptions', $yesNo
                );

        $this->addColumn("CASE WHEN gcon_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        if ($addCount) {
            $this->addColumn(
                    "(SELECT COUNT(gro_id_round) FROM gems__rounds WHERE gcon_id = gro_condition)",
                    'usage'
                    );
            $this->set('usage', 'label', $this->_('Rounds'),
                    'description', $this->_('The number of rounds using this condition.'),
                    'elementClass', 'Exhibitor'
                    );

            $this->addColumn(new \Zend_Db_Expr(
                "(SELECT COUNT(*)
                    FROM gems__conditions AS other
                    WHERE (gcon_class LIKE '%AndCondition' OR gcon_class LIKE '%OrCondition') AND
                        (
                            gems__conditions.gcon_id = other.gcon_condition_text1 OR
                            gems__conditions.gcon_id = other.gcon_condition_text2 OR
                            gems__conditions.gcon_id = other.gcon_condition_text3 OR
                            gems__conditions.gcon_id = other.gcon_condition_text4
                        )
                )"
                ), 'usecondition');
            $this->set('usecondition', 'label', $this->_('Conditions'),
                'description', $this->_('The number of uses of this condition in other conditions.'),
                'elementClass', 'Exhibitor'
                );
        }
        if (! $addCount) {
            $this->addDependency('Condition\\TypeDependency');
        }

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems\Model\ConditionModel
     */
    public function applyDetailSettings()
    {
        $this->applyBrowseSettings(false);

        $yesNo = $this->util->getTranslated()->getYesNo();

        $this->resetOrder();

        $this->set('gcon_type');
        $this->set('gcon_class');
        $this->set('gcon_name', 'description', $this->_('A name for this condition, will be used to select it when applying the condition.'));

        $this->set('condition_help', 'label', $this->_('Help'), 'elementClass', 'Exhibitor');

        // Set the order
        $this->set('gcon_condition_text1');
        $this->set('gcon_condition_text2');
        $this->set('gcon_condition_text3');
        $this->set('gcon_condition_text4');

        $this->set('gcon_active', 'label', $this->_('Active'),
                'multiOptions', $yesNo
                );

        $this->addDependency('Condition\\ClassDependency');

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return \Gems\Model\ConditionModel
     */
    public function applyEditSettings($create = false)
    {
        $this->applyDetailSettings();

        $this->set('gcon_type', 'default', Conditions::ROUND_CONDITION);

        $this->set('gcon_name', 'validators[unique]', $this->createUniqueValidator(['gcon_name', 'gcon_type'], ['gcon_id']));

        // gcon_id is not needed for some validators
        $this->set('gcon_id',            'elementClass', 'Hidden');

        $this->set('gcon_active',        'elementClass', 'Checkbox');

        return $this;
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param array $saveTables Array of table names => save mode
     * @return int The number of items deleted
     */
    public function delete($filter = true, array $saveTables = null)
    {
        $this->setChanged(0);
        $conditions = $this->load($filter);

        if ($conditions) {
            foreach ($conditions as $row) {
                if (isset($row['gcon_id'])) {
                    $conditionId = $row['gcon_id'];
                    if ($this->isDeleteable($conditionId)) {
                        $this->db->delete('gems__conditions', $this->db->quoteInto('gcon_id = ?', $conditionId));
                    } else {
                        $values['gcon_id'] = $conditionId;
                        $values['gcon_active']   = 0;
                        $this->save($values);
                    }
                    $this->addChanged();
                }
            }
        }

        return $this->getChanged();
    }

    /**
     * Get the number of times someone started answering a round in this track.
     *
     * @param int $conditionId
     * @return int
     */
    public function getUsedCount($conditionId)
    {
        if (! $conditionId) {
            return 0;
        }

        $sqlRounds     = "SELECT COUNT(gro_id_round) FROM gems__rounds WHERE gro_condition = ?";
        $sqlConditions = "SELECT COUNT(*)
            FROM gems__conditions
            WHERE (gcon_class LIKE '%AndCondition' OR gcon_class LIKE '%OrCondition') AND
                (
                    gcon_condition_text1 = ? OR
                    gcon_condition_text2 = ? OR
                    gcon_condition_text3 = ? OR
                    gcon_condition_text4 = ?
                )";

        return (int) $this->db->fetchOne($sqlRounds, $conditionId) + (int) $this->db->fetchOne($sqlConditions, [$conditionId,$conditionId,$conditionId,$conditionId]);
    }

    /**
     * Can this condition be deleted as is?
     *
     * @param int $conditionId
     * @return boolean
     */
    public function isDeleteable($conditionId)
    {
        if (! $conditionId) {
            return true;
        }

        return $this->getUsedCount($conditionId) === 0;
    }

}
