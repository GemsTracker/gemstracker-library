<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\Condition\ConditionLoader;
use Gems\Db\ResultFetcher;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class ConditionModel extends GemsJoinModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
        protected readonly ConditionLoader $conditionLoader,
        protected readonly ResultFetcher $resultFetcher
    ) {
        parent::__construct('gems__conditions', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gcon');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @param boolean $addCount Add a count in rounds column
     */
    public function applyBrowseSettings($addCount = true): self
    {
        $yesNo = $this->translatedUtil->getYesNo();

        $types = $this->conditionLoader->getConditionTypes();
        reset($types);
        $default = key($types);
        $this->metaModel->set('gcon_type', [
            'label' => $this->_('Type'),
            'description' => $this->_('Determines where the condition can be applied.'),
            'multiOptions' => $types,
            'default' => $default,
            'autoSubmit' => true
        ]);

        $conditionsClasses = [];
        if ($addCount) { // Are we in a browse mode
            foreach ($types as $type => $val) {
                $conditionsClasses += $this->conditionLoader->listConditionsForType($type);
            }
        }
        $this->metaModel->set('gcon_class', [
            'label' => $this->_('Condition'),
            'multiOptions' => $conditionsClasses,
            'autoSubmit' => true
        ]);

        $this->metaModel->set('gcon_name', [
            'label' => $this->_('Name'),
        ]);
        $this->metaModel->set('gcon_active', [
            'label' => $this->_('Active'),
            'type' => new ActivatingYesNoType($yesNo, 'row_class'),
        ]);

        $this->addColumn("CASE WHEN gcon_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        if ($addCount) {
            $this->addColumn(
                "(SELECT COUNT(gro_id_round) FROM gems__rounds WHERE gcon_id = gro_condition)",
                'usage'
            );
            $this->metaModel->set('usage', [
                'label' => $this->_('Rounds'),
                'description' => $this->_('The number of rounds using this condition.'),
                'elementClass' => 'Exhibitor',
            ]);

            $this->addColumn(
                new Expression(
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
                ),
                'usecondition'
            );
            $this->metaModel->set('usecondition', [
                'label' => $this->_('Conditions'),
                'description' => $this->_('The number of uses of this condition in other conditions.'),
                'elementClass' => 'Exhibitor',
            ]);
        }
        if (!$addCount) {
            $this->metaModel->addDependency('Condition\\TypeDependency');
        }

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     */
    public function applyDetailSettings(): self
    {
        $this->applyBrowseSettings(false);

        $yesNo = $this->translatedUtil->getYesNo();

        $this->metaModel->resetOrder();

        $this->metaModel->set('gcon_type');
        $this->metaModel->set('gcon_class');
        $this->metaModel->set('gcon_name', [
            'description' => $this->_(
                'A name for this condition, will be used to select it when applying the condition.'
            )
        ]);

        $this->metaModel->set('condition_help', [
            'label' => $this->_('Help'),
            'elementClass' => 'Exhibitor',
        ]);

        // Set the order
        $this->metaModel->set('gcon_condition_text1');
        $this->metaModel->set('gcon_condition_text2');
        $this->metaModel->set('gcon_condition_text3');
        $this->metaModel->set('gcon_condition_text4');

        $this->metaModel->set('gcon_active', [
            'label' => $this->_('Active'),
            'multiOptions' => $yesNo,
        ]);

        $this->metaModel->addDependency('Condition\\ClassDependency');

        return $this;
    }

    /**
     * Set those values needed for editing
     */
    public function applyEditSettings(bool $create = false): self
    {
        $this->applyDetailSettings();

        $this->metaModel->set('gcon_type', [
            'default' => ConditionLoader::ROUND_CONDITION,
        ]);

        $this->metaModel->set('gcon_name', [
            'validators[unique]' => new ModelUniqueValidator('gcon_name', 'gcon_type'),
        ]);

        // gcon_id is not needed for some validators
        $this->metaModel->set('gcon_id', [
            'elementClass' => 'Hidden',
        ]);

        $this->metaModel->set('gcon_active', [
            'elementClass' => 'Checkbox',
        ]);

        return $this;
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param array|null $saveTables Array of table names => save mode
     * @return int The number of items deleted
     */
    public function delete($filter = null, array $saveTables = null): int
    {
        $this->resetChanged();
        $conditions = $this->load($filter);

        if ($conditions) {
            foreach ($conditions as $row) {
                if (isset($row['gcon_id'])) {
                    $conditionId = $row['gcon_id'];
                    if ($this->isDeleteable($conditionId)) {
                        $this->resultFetcher
                            ->query('DELETE FROM `gems__conditions` WHERE `gcon_id` = ?', [$conditionId]);
                    } else {
                        $values['gcon_id'] = $conditionId;
                        $values['gcon_active'] = 0;
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
     */
    public function getUsedCount(int $conditionId): int
    {
        if (!$conditionId) {
            return 0;
        }

        $sqlRounds = "SELECT COUNT(gro_id_round) FROM gems__rounds WHERE gro_condition = ?";
        $sqlConditions = "SELECT COUNT(*)
            FROM gems__conditions
            WHERE (gcon_class LIKE '%AndCondition' OR gcon_class LIKE '%OrCondition') AND
                (
                    gcon_condition_text1 = ? OR
                    gcon_condition_text2 = ? OR
                    gcon_condition_text3 = ? OR
                    gcon_condition_text4 = ?
                )";

        return (int)$this->resultFetcher->fetchOne($sqlRounds, [$conditionId])
            + (int)$this->resultFetcher->fetchOne(
                $sqlConditions,
                [$conditionId, $conditionId, $conditionId, $conditionId]
            );
    }

    /**
     * Can this condition be deleted as is?
     *
     * @param int $conditionId
     * @return boolean
     */
    public function isDeleteable($conditionId)
    {
        if (!$conditionId) {
            return true;
        }

        return $this->getUsedCount($conditionId) === 0;
    }
}
