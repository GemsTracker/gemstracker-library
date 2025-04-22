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
    protected ProjectOverloader|null $overLoader = null;

    public function execute(
        string|int $modelIdentifier = null,
        array $postData = [],
        array $modelApplyFunctions = [],
        string $exportType = null,
        int $rowsPerBatch = 500
    )
    {
        $batch = $this->getBatch();

        /**
         * @var ModelContainer $modelContainer
         */
        $modelContainer = $batch->getVariable('modelContainer');
        $model = $modelContainer->get($modelIdentifier, $postData, $modelApplyFunctions);

        $exportId = $model->getName() . (new \DateTimeImmutable())->format('YmdHis');

        $currentExportIds = $batch->getVariable('exportIds') ?? [];
        if (!in_array($exportId, $currentExportIds)) {
            $currentExportIds[] = $exportId;
            $batch->setSessionVariable('exportIds', $currentExportIds);
        }

        $modelFilter = $this->getFilterFromPostData($postData, $model->getMetaModel());

        $totalRows  = $model->loadCount($modelFilter);

        $columnOrder = $this->getColumnOrder($model->getMetaModel());

        $totalTasks = ceil($totalRows / $rowsPerBatch);

        $batch->getStack()->registerAllowedClass(ModelExportPart::class);

        for ($i = 0; $i < $totalTasks; $i++) {
            $modelExportPart = new ModelExportPart(
                exportId: $exportId,
                filename: $this->getExportFileName($model, $exportType),
                exportType: $exportType,
                userId: $batch->getVariable(AuthenticationMiddleware::CURRENT_USER_ID_ATTRIBUTE),
                modelIdentifier: $modelIdentifier,
                applyFunctions: $modelApplyFunctions,
                columnOrder: $columnOrder,
                filter: $modelFilter,
                post: $postData,
                itemCount: $rowsPerBatch,
                part: $i+1,
                totalRows: $totalRows,
                exportSettings: [],
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
            $now->format('YmdHis'),
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

    protected function getFilterFromPostData(array $filter, MetaModelInterface $metaModel): array
    {
        // Change key filters to field name filters
        $keys = $metaModel->getKeys();
        foreach ($keys as $key => $field) {
            if (isset($filter[$key]) && $key !== $field) {
                $filter[$field] = $filter[$key];
                unset($filter[$key]);
            }
        }

        foreach ($filter as $field => $value) {
            if (! (is_int($field) || $metaModel->has($field))) {
                unset($filter[$field]);
            }
        }
        return $filter;
    }
}