<?php

namespace Gems\Task\Export;

use Gems\Export\Db\ModelContainer;
use Gems\Messenger\Message\Export\ModelExportPart;
use MUtil\Task\TaskAbstract;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;

class InitDbExport extends TaskAbstract
{
    protected ModelContainer|null $modelContainer = null;

    protected ProjectOverloader|null $overLoader = null;

    public function execute(string $modelName = null, array $exportOptions = [], array $modelFilter = [], int $rowsPerBatch = 500)
    {
        $batch = $this->getBatch();
        $modelFilter = $batch->getSessionVariable('modelFilter');

        $model = $this->getModel($modelName);
        $totalRows  = $model->loadCount($modelFilter);

        $totalTasks = ceil($totalRows / $rowsPerBatch);

        $batch->getStack()->registerAllowedClass(ModelExportPart::class);

        for ($i = 0; $i < $totalTasks; $i++) {
            $modelExportPart = new ModelExportPart(
                $batch->getId(),
                $modelName,
                $modelFilter,
                $rowsPerBatch,
                $i+1,
            );
            $batch->addTask(DbExportPart::class, $modelExportPart);
        }
    }


    protected function getModel(string $modelName): DataReaderInterface
    {
        if (!$this->modelContainer) {
            $this->modelContainer = $this->overLoader->getContainer()->get(ModelContainer::class);
        }

        return $this->modelContainer->get($modelName);
    }

    public function getRegistryRequests()
    {
        return array_filter(array_keys(get_object_vars($this)), array($this, 'filterRequestNames'));
    }
}