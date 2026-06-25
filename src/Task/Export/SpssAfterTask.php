<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Task\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Task\Export;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Db\ResultFetcher;
use MUtil\Task\TaskAbstract;

/**
 * @package    Gems
 * @subpackage Task\Export
 * @since      Class available since version 1.0
 */
class SpssAfterTask extends TaskAbstract
{
    public function __construct(
        protected ResultFetcher $resultFetcher
    )
    {
    }

    public function execute()
    {
        $userId = $this->_batch->getVariable(AuthenticationMiddleware::CURRENT_USER_ID_ATTRIBUTE);

        $sql = "SELECT * FROM gems__file_exports WHERE gfex_id_user = ? AND gfex_file_name LIKE '%.dat' AND gfex_export_type = ? AND gfex_order = 0";

        $all = $this->resultFetcher->fetchAll($sql, [$userId, 'Gems\\Export\\Type\\SpssExport']);
        foreach ($all as $row) {
            $output = [];
            $output['gfex_export_id'] = $row['gfex_export_id'] . '-sps';
            if (! $this->resultFetcher->fetchOne("SELECT gfex_export_id FROM gems__file_exports WHERE gfex_export_id = ?", [$output['gfex_export_id']])) {
                $output['gfex_file_name'] = str_replace('.dat', '.sps', $row['gfex_file_name']);

                foreach (['gfex_id_user', 'gfex_schema_name', 'gfex_export_type', 'gfex_export_settings', 'gfex_column_order', 'gfex_data', 'gfex_order', 'gfex_row_count'] as $field) {
                    $output[$field] = $row[$field];
                }
                $this->resultFetcher->insertIntoTable('gems__file_exports', $output);
            }
        }
    }
}