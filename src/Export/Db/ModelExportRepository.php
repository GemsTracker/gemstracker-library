<?php

namespace Gems\Export\Db;

use Gems\Export\Type\ExportInterface;
use Gems\Messenger\Message\Export\ModelExportPart;
use Psr\Container\ContainerInterface;
use Zalt\Loader\Exception\LoadException;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;

class ModelExportRepository
{
    public function __construct(
        protected ProjectOverloader $projectOverloader,
    )
    {}

    public function getHeaders(ContainerInterface $modelContainer, ModelExportPart $part): array
    {
        $model = $this->getModel($modelContainer, $part);
        $metaModel = $model->getMetaModel();
        $labeledColumns = $this->getLabeledColumns($metaModel);

        if ($part->exportSettings && isset($part->exportSettings['translateHeaders']) && !$part->exportSettings['translateHeaders']) {
            return $labeledColumns;
        }

        $columnHeaders = [];
        foreach($labeledColumns as $columnName) {
            $columnHeaders[$columnName] = strip_tags($metaModel->get($columnName, 'label'));
        }

        return $columnHeaders;
    }

    public function getLabeledColumns(MetaModelInterface $metaModel): array
    {
        if (!$metaModel->hasMeta('labeledColumns')) {
            $orderedCols = $metaModel->getItemsOrdered();

            $results = [];
            foreach ($orderedCols as $name) {
                if ($metaModel->has($name, 'label')) {
                    $results[] = $name;
                }
            }

            $metaModel->setMeta('labeledColumns', $results);
        }

        return $metaModel->getMeta('labeledColumns');
    }
    public function getRowData(ContainerInterface $modelContainer, ModelExportPart $part): array
    {
        $model = $this->getModel($modelContainer, $part);
        $data = $model->loadPage($part->part, $part->itemCount, $part->filter);

        $exportClass = $this->projectOverloader->getContainer()->get($part->exportType);

        if (!$exportClass instanceof ExportInterface) {
            throw new \RuntimeException('No valid export class could be loaded.');
        }

        foreach($data as $key => $row) {
            $data[$key] = $exportClass->filterRow($model->getMetaModel(), $row, $part->exportSettings);
        }
        return $data;
    }

    public function getExportTypeClassName(string $exportType): string|null
    {
        try {
            return $this->projectOverloader->find('Export\\Type\\' . $exportType);
        } catch(LoadException) {
            return null;
        }
    }

    private function getModel(ContainerInterface $modelContainer, ModelExportPart $part): DataReaderInterface
    {
        if ($modelContainer instanceof ModelContainer) {
            $model = $modelContainer->get($part->modelIdentifier, $part->post, $part->applyFunctions);
        } else {
            $model = $modelContainer->get($part->modelIdentifier);
        }

        foreach($part->exportSettings as $exportSetting) {
            if (method_exists($model, $exportSetting)) {
                $model->$exportSetting();
            }
        }

        return $model;
    }
}