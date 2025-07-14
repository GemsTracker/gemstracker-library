<?php

namespace Gems\Task\Export;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Export\Db\ModelExportRepository;
use Gems\Export\Db\ModelContainer;
use Gems\Export\Type\ExportSettingsGeneratorInterface;
use Gems\Messenger\Message\Export\ModelExportPart;
use MUtil\Task\TaskAbstract;
use Zalt\File\File;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;

class InitDbExport extends TaskAbstract
{
    protected ProjectOverloader|null $overLoader = null;

    public function execute(
        string|int $modelIdentifier = null,
        array $searchFilter = [],
        array $modelApplyFunctions = [],
        string $exportType = null,
        array $postData = [],
        int $rowsPerBatch = 500
    )
    {
        $batch = $this->getBatch();

        /**
         * @var ModelContainer $modelContainer
         */
        $modelContainer = $batch->getVariable('modelContainer');
        $model = $modelContainer->get($modelIdentifier, $searchFilter, $modelApplyFunctions);

        $exportId = hash('sha256', $model->getName()) . '-' . (new \DateTimeImmutable())->format('YmdHis');

        $currentExportIds = $batch->getVariable('exportIds') ?? [];
        if (!in_array($exportId, $currentExportIds)) {
            $currentExportIds[] = $exportId;
            $batch->setSessionVariable('exportIds', $currentExportIds);
        }

        $modelFilter = $this->getFilterFromPostData($searchFilter, $model->getMetaModel());

        $totalRows  = $model->loadCount($modelFilter);

        $columnOrder = $this->getColumnOrder($model->getMetaModel());

        $totalTasks = ceil($totalRows / $rowsPerBatch);

        $batch->getStack()->registerAllowedClass(ModelExportPart::class);

        $exportSettings = $this->getExportSettings($exportType, $postData);

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
                post: $searchFilter,
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
            File::cleanupName(basename($model->getName())),
            $now->format('YmdHis'),
        ];

        if (defined("$exportTypeClass::EXTENSION")) {
            $nameParts[] = constant("$exportTypeClass::EXTENSION");
        }

        return join('.', $nameParts);
    }

    protected function getExportSettings(string $exportType, array $postData): array
    {
        $type = $this->overLoader->getContainer()->get($exportType);
        if ($type instanceof ExportSettingsGeneratorInterface) {
            return $type->getExportSettings($postData);
        }
        return [];
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
