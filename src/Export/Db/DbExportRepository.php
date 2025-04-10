<?php

namespace Gems\Export\Db;

use Gems\Db\ResultFetcher;
use Gems\Db\UnbufferedResultFetcher;
use Gems\Export\Exception\ExportException;
use Gems\Export\Type\DownloadableInterface;
use Gems\Export\Type\ModelResultSettingsInterface;
use Gems\Export\Type\StreamableInterface;
use Gems\Messenger\Message\Export\ModelExportPart;
use Gems\Response\DownloadResponse;
use Laminas\Db\ResultSet\ResultSetInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zalt\Loader\Exception\LoadException;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;

class DbExportRepository
{
    public const EXPORT_DB = 'gems__file_exports';

    public function __construct(
        protected readonly ModelExportRepository $exportRepository,
        protected readonly ProjectOverloader $loader,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly UnbufferedResultFetcher $unbufferedResultFetcher,
        protected readonly ModelContainer $modelContainer,
    )
    {}
    public function insertDbPart(ContainerInterface $modelContainer, ModelExportPart $part): void
    {
        if ($part->part === 1) {
            $this->addHeaderToExportData($modelContainer, $part);
        }
        $data = $this->exportRepository->getRowData($modelContainer, $part);
        $this->addDataToExportData($modelContainer, $part, $data);
    }

    protected function addDataToExportData(ContainerInterface $modelContainer, ModelExportPart $part, array $data): void
    {
        $i = (($part->part - 1) * $part->itemCount) + 1;
        foreach($data as $row) {
            $this->addRowToExportData($part, $i, $row);
            $i++;
        }
    }

    protected function addHeaderToExportData(ContainerInterface $modelContainer, ModelExportPart $part): void
    {
       if (!$this->hasHeader($part)) {
           return;
       }
       $header = $this->exportRepository->getHeaders($modelContainer, $part);
       $this->addRowToExportData($part, 0, $header);
    }

    protected function addRowToExportData(ModelExportPart $part, int $order, array $row): void
    {
        $data = [
            'gfex_export_id' => $part->exportId,
            'gfex_id_user' => $part->userId,
            'gfex_order' => $order,
            'gfex_data' => json_encode($row),
            'gfex_row_count' => $part->totalRows,
            'gfex_file_name' => $part->filename,
            'gfex_export_type' => $part->exportType,
            'gfex_column_order' => json_encode($part->columnOrder),
        ];

        if (($order === 0 || $order === 1 && !$this->hasHeader($part)) && $part->exportSettings) {
            $data['gfex_export_settings'] = json_encode($part->exportSettings);
        }
        $this->resultFetcher->insertIntoTable(static::EXPORT_DB, $data);
    }

    protected function clearExportData(string $exportId, int $userId): void
    {
        $this->resultFetcher->deleteFromTable(static::EXPORT_DB, ['gfex_export_id' => $exportId, 'gfex_id_user' => $userId]);
    }

    public function exportFile(string $exportId, int $userId, bool $streamOnly = true): ResponseInterface|null
    {
        $firstRow = $this->getExportHeader($exportId, $userId);
        if (!$firstRow) {
            throw new ExportException(sprintf('Export with Id %s not found', $exportId));
        }

        $exportTypeClassName = $firstRow['gfex_export_type'];
        $exportType = $this->loader->getContainer()->get($exportTypeClassName);
        $fileName = $firstRow['gfex_file_name'];
        $exportSettings = [];
        if ($firstRow['gfex_export_settings']) {
            $exportSettings = json_decode($firstRow['gfex_export_settings'], true) ?? [];
        }

        if ($exportType instanceof ModelResultSettingsInterface && isset($exportSettings['sourceModel'])) {
            $applyFunctions = $exportSettings['applyFunctions'] ?? [];
            $model = $this->modelContainer->get($exportSettings['sourceModel'], $applyFunctions);
            $exportSettings = $exportType->getResultSettings($exportSettings, $model->getMetaModel());
        }
        $extractor = new DbExportExtractor();

        $exportResult = $this->getExportResult($exportId, $userId);

        if ($exportType instanceof StreamableInterface) {
            $exportType->streamResult($exportResult, $extractor, $fileName, $exportSettings);
            $this->clearExportData($exportId, $userId);
            return null;
        }

        if ($exportType instanceof DownloadableInterface && !$streamOnly) {
            $result = $exportType->downloadFile($exportResult, $extractor, $exportId, $fileName, $exportSettings);
            $tempFileName = key($result);
            $newFileName = reset($result);
            $response = new DownloadResponse($tempFileName, $newFileName);
            $this->clearExportData($exportId, $userId);
            return $response->deleteFileAfterSend();
        }

        throw new ExportException(sprintf('No valid download option available in type %s', $exportTypeClassName));
    }

    protected function getExportHeader(string $exportId, int $userId): array|null
    {
        $select = $this->resultFetcher->getSelect('gems__file_exports')
            ->columns(['gfex_data', 'gfex_file_name', 'gfex_export_type', 'gfex_export_settings', 'gfex_column_order'])
            ->where([
                'gfex_export_id' => $exportId,
                'gfex_id_user' => $userId,
                'gfex_order' => 0,
            ])
            ->order('gfex_order');
        return $this->resultFetcher->fetchRow($select);
    }


    public function getExportResult(string $exportId, int $userId): ResultSetInterface|null
    {
        $select = $this->unbufferedResultFetcher->getSelect('gems__file_exports')
            ->columns(['gfex_data', 'gfex_file_name', 'gfex_export_type', 'gfex_export_settings', 'gfex_column_order'])
            ->where([
                'gfex_export_id' => $exportId,
                'gfex_id_user' => $userId,
            ])
            ->order('gfex_order');

        return $this->unbufferedResultFetcher->query($select);
    }

    protected function getExportTypeClassName(string $exportType): string|null
    {
        try {
            return $this->loader->find('Export\\Type\\' . $exportType);
        } catch(LoadException) {
            return null;
        }
    }

    protected function hasHeader(ModelExportPart $part): bool
    {
        if ($part->exportSettings && isset($part->exportSettings['addHeader']) && $part->exportSettings['addHeader'] === false) {
            return false;
        }
        return true;
    }
}