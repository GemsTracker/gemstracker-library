<?php

namespace Gems\Task\Export;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Export\Db\ModelContainer;
use Gems\Messenger\Message\Export\ModelExportPart;
use MUtil\Task\TaskAbstract;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;

class InitDbExport extends TaskAbstract
{
    protected ModelContainer|null $modelContainer = null;

    protected ProjectOverloader|null $overLoader = null;

    public function execute(
        string $modelName = null,
        array $exportOptions = [],
        array $modelFilter = [],
        array $modelApplyFunctions = [],
        string $exportType = null,
        int $rowsPerBatch = 500
    )
    {
        $batch = $this->getBatch();
        $modelFilter = $batch->getSessionVariable('modelFilter');

        $model = $this->getModel($modelName);
        $totalRows  = $model->loadCount($modelFilter);

        $totalTasks = ceil($totalRows / $rowsPerBatch);

        $batch->getStack()->registerAllowedClass(ModelExportPart::class);

        for ($i = 0; $i < $totalTasks; $i++) {
            $modelExportPart = new ModelExportPart(
                exportId: $batch->getId(),
                filename: $this->getExportFileName($model),
                exportType: $exportType,
                userId: $batch->getVariable(AuthenticationMiddleware::CURRENT_USER_ID_ATTRIBUTE),
                modelClassName: $modelName,
                applyFunctions: $modelApplyFunctions,
                filter: $modelFilter,
                itemCount: $rowsPerBatch,
                part: $i+1,
                totalRows: $totalRows,
            );
            $batch->addTask(DbExportPart::class, $modelExportPart);
        }
    }

    protected function getExportFileName(DataReaderInterface $model)
    {
        $now = new \DateTimeImmutable();
        $extension = '';
        $nameParts = [
            $model->getName(),
            $now->format('Ymdhis'),
            $extension,
        ];

        return join('.', $nameParts);
    }


    protected function getModel(string $modelName): DataReaderInterface
    {
        if (!$this->modelContainer) {
            $this->modelContainer = $this->overLoader->getContainer()->get(ModelContainer::class);
        }

        return $this->modelContainer->get($modelName);
    }
}