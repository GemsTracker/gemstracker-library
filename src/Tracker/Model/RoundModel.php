<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use Gems\Db\ResultFetcher;
use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Laminas\Db\Sql\Where;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 21-apr-2015 13:43:07
 */
class RoundModel extends GemsJoinModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly ResultFetcher $resultFetcher,
    ) {
        parent::__construct('gems__rounds', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gro');
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
        $this->metaModel->trackUsage();
        $rows = $this->load($filter, null, ['gro_id_round', 'gro_active']);

        if ($rows) {
            foreach ($rows as $row) {
                if (isset($row['gro_id_round'])) {
                    $roundId = $row['gro_id_round'];
                    if ($this->isDeleteable($roundId)) {
                        // Delete the round before anyone starts using it
                        $this->resultFetcher->deleteFromTable(
                            'gems__tokens',
                            (new Where())->equalTo('gto_id_round', $roundId)
                        );
                        // First break the self referencing foreign key.
                        $this->resultFetcher->updateTable(
                            'gems__rounds',
                            ['gro_valid_for_id' => null],
                            (new Where())->equalTo('gro_id_round', $roundId)
                        );
                        // Then delete the round itself.
                        $this->resultFetcher->deleteFromTable(
                            'gems__rounds',
                            (new Where())->equalTo('gro_id_round', $roundId)
                        );
                    } else {
                        $values['gro_id_round'] = $roundId;
                        $values['gro_active']   = 0;
                        $this->save($values);
                    }
                    $this->addChanged();
                }
            }
        }
        return $this->getChanged();
    }

    public function setDefaultTrackId(int $trackId): void
    {
        $this->metaModel->set('gro_id_track', ['default' => $trackId]);
    }

    public function setDefaultOrder(int $trackId): void
    {
        $newOrder = $this->resultFetcher->fetchOne(
            "SELECT MAX(gro_id_order) FROM gems__rounds WHERE gro_id_track = ?",
            [$trackId]
        );

        if ($newOrder) {
            $this->metaModel->set('gro_id_order', ['default' => $newOrder + 10]);
        } else {
            $this->metaModel->set('gro_valid_after_source', ['default' => 'rtr']);
        }
    }

    /**
     * Get the number of times a round is used in other rounds
     *
     * @param int $roundId
     * @return int
     */
    public function getRefCount($roundId)
    {
        if (! $roundId) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM gems__rounds 
                    WHERE gro_id_round != ? AND (
                        (gro_valid_after_id = ? AND gro_valid_after_source IN ('tok', 'ans')) OR 
                        (gro_valid_for_id = ? AND gro_valid_for_source IN ('tok', 'ans'))
                        )";
        return $this->resultFetcher->fetchOne($sql, [$roundId, $roundId, $roundId]);
    }

    /**
     * Get the number of times someone started answering this round.
     *
     * @param int $roundId
     * @return int
     */
    public function getStartCount($roundId)
    {
        if (! $roundId) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM gems__tokens WHERE gto_id_round = ? AND gto_start_time IS NOT NULL";
        return $this->resultFetcher->fetchOne($sql, [$roundId]);
    }

    /**
     * Can this round be deleted as is?
     *
     * @param int $roundId
     */
    public function isDeleteable($roundId): bool
    {
        if (! $roundId) {
            return true;
        }
        return ! ($this->getRefCount($roundId) + $this->getStartCount($roundId));
    }
}
