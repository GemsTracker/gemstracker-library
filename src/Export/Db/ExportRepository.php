<?php

namespace Gems\Export\Db;

use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Gems\Export\ExportSettings\CsvExportSettings;
use Gems\Export\ExportSettings\ExcelExportSettings;
use Gems\Export\Type\ExportInterface;
use Gems\Html;
use Gems\Messenger\Message\Export\ModelExportPart;
use Zalt\Html\AElement;
use Zalt\Html\ElementInterface;
use Zalt\Html\HtmlInterface;
use Zalt\Html\Sequence;
use Zalt\Late\Late;
use Zalt\Late\LateInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;

class ExportRepository
{
    protected array $modelFilterAttributes = ['multiOptions', 'formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay'];
    public function __construct(
        protected readonly ModelContainer $modelContainer,
        protected ProjectOverloader $projectOverloader,
    )
    {}

    public function getHeaders(ModelExportPart $part): array
    {
        /**
         * @var DataReaderInterface $model
         */
        $model = $this->modelContainer->get($part->modelClassName);
        $metaModel = $model->getMetaModel();
        $labeledColumns = $this->getLabeledColumns($metaModel);



        if (!($part->exportSettings instanceof ExcelExportSettings || $part->exportSettings instanceof CsvExportSettings) || !$part->exportSettings->translateHeaders) {
            return $labeledColumns;
        }

        $columnHeaders = [];
        foreach($labeledColumns as $columnName) {
            $columnHeaders[$columnName] = strip_tags($metaModel->get($columnName, 'label'));
        }

        return $columnHeaders;
    }

    protected function getLabeledColumns(MetaModelInterface $metaModel): array
    {
        if (!$metaModel->hasMeta('labeledColumns')) {
            $orderedCols = $metaModel->getItemsOrdered();

            $results = array();
            foreach ($orderedCols as $name) {
                if ($metaModel->has($name, 'label')) {
                    $results[] = $name;
                }
            }

            $metaModel->setMeta('labeledColumns', $results);
        }

        return $metaModel->getMeta('labeledColumns');
    }
    public function getRowData(ModelExportPart $part): array
    {
        /**
         * @var DataReaderInterface $model
         */
        $model = $this->modelContainer->get($part->modelClassName);
        $data = $model->loadPage($part->part, $part->itemCount, $part->filter);


        $exportClass = $this->projectOverloader->create('Export\\Type\\' . $part->exportType);

        if (!$exportClass instanceof ExportInterface) {
            throw new \RuntimeException('No valid export class could be loaded.');
        }

        foreach($data as $key => $row) {
            $data[$key] = $exportClass->filterRow($model->getMetaModel(), $row, $part->exportSettings);
        }

        return $data;
    }


}