<?php

namespace Gems\Task\Export;

use Gems\Export\Db\DbExportRepository;
use Gems\Messenger\Message\Export\ModelExportPart;
use MUtil\Task\TaskAbstract;
use Zalt\Loader\ProjectOverloader;

class DbExportPart extends TaskAbstract
{
    protected ProjectOverloader|null $overLoader = null;

    public function execute(ModelExportPart|array|null $exportPart = null)
    {
        /**
         * @var DbExportRepository $dbExportRepository
         */
        $dbExportRepository = $this->overLoader->getContainer()->get(DbExportRepository::class);
        if (is_array($exportPart)) {
            $exportPart = new ModelExportPart(
                $exportPart['exportId'],
                $exportPart['modelClassName'],
                $exportPart['filter'],
                $exportPart['itemCount'],
                $exportPart['part'],
                $exportPart['translateHeaders'],
                $exportPart['translateValues'],
            );
        }
        $dbExportRepository->insertDbPart($exportPart);
    }
}