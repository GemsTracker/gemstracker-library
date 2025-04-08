<?php

namespace Gems\Task\Export;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Export\Db\ModelExportRepository;
use Gems\Export\Db\ModelContainer;
use Gems\Export\ExportSettings\ExportSettingsInterface;
use Gems\Export\Type\ExportSettingsGeneratorInterface;
use Gems\Export\Type\StreamingExcelExport;
use Gems\Messenger\Message\Export\ModelExportPart;
use MUtil\Task\TaskAbstract;
use Zalt\Loader\Exception\LoadException;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;

class InitDbExport extends TaskAbstract
{
    protected ModelContainer|null $modelContainer = null;

    protected ProjectOverloader|null $overLoader = null;

    public function execute(
        string $modelName = null,
        array $postData = [],
        array $modelFilter = [],
        array $modelApplyFunctions = [],
        string $exportType = null,
        int $rowsPerBatch = 500
    )
    {
        $batch = $this->getBatch();
        $modelFilter = $batch->getVariable('searchFilter') ?? [];

        $exportId = $batch->getId() . (new \DateTimeImmutable())->format('YmdHis');
        $batch->setSessionVariable('exportId', $exportId);

        $model = $batch->getVariable('model') ?? $this->getModel($modelName);

        $postData['model'] = $model;
        $totalRows  = $model->loadCount($modelFilter);

        $columnOrder = $this->getColumnOrder($model->getMetaModel());

        $totalTasks = ceil($totalRows / $rowsPerBatch);

        $exportSettings = $this->getExportOptions($exportType, $postData);
        $exportSettings['sourceModel'] = $modelName;
        $exportSettings['applyFunctions'] = $modelApplyFunctions;

        $batch->getStack()->registerAllowedClass(ModelExportPart::class);

        for ($i = 0; $i < $totalTasks; $i++) {
            $modelExportPart = new ModelExportPart(
                exportId: $exportId,
                filename: $this->getExportFileName($model, $exportType),
                exportType: $exportType,
                userId: $batch->getVariable(AuthenticationMiddleware::CURRENT_USER_ID_ATTRIBUTE),
                applyFunctions: $modelApplyFunctions,
                columnOrder: $columnOrder,
                filter: $modelFilter,
                itemCount: $rowsPerBatch,
                part: $i+1,
                totalRows: $totalRows,
                exportSettings: $exportSettings,
            );
            $batch->addTask(DbExportPart::class, $modelExportPart);
        }
    }

    protected function getColumnOrder(MetaModelInterface $metaModel): array
    {
        /**
         * @var ModelExportRepository $exportRepository
         */
        $exportRepository = $this->overLoader->getContainer()->get(ModelExportRepository::class);
        return $exportRepository->getLabeledColumns($metaModel);
    }

    protected function getExportFileName(DataReaderInterface $model, string $exportTypeClass): string
    {
        $now = new \DateTimeImmutable();
        $nameParts = [
            $model->getName(),
            $now->format('Ymdhis'),
        ];

        if (defined("$exportTypeClass::EXTENSION")) {
            $nameParts[] = constant("$exportTypeClass::EXTENSION");
        }

        return join('.', $nameParts);
    }

    protected function getExportOptions(string $exportType, array $postData): array|null
    {
        $type = $this->overLoader->getContainer()->get($exportType);
        if ($type instanceof ExportSettingsGeneratorInterface) {
            return $type->getExportSettings($postData);
        }
        return null;
    }

    protected function getModel(string $modelName): DataReaderInterface
    {
        if (!$this->modelContainer) {
            $this->modelContainer = $this->overLoader->getContainer()->get(ModelContainer::class);
        }

        return $this->modelContainer->get($modelName);
    }
}