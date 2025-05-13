<?php

namespace Gems\Export;

use Gems\Export\Type\ExportInterface;
use Zalt\Loader\ProjectOverloader;

class Export
{

    /**
     * This variable holds all registered export classes, may be changed in derived classes
     *
     * @var array Of classname => description
     */
    protected array $exportClasses =  [
        'StreamingExcelExport' => 'Excel (xlsx)',
        //'RExport' => 'R',
        'SpssExport' => 'SPSS',
        'CsvExport' => 'CSV',
        //'StreamingStataExport' => 'Stata (xml)',
    ];

    protected array $streamingExportClasses = [
        'StreamingExcelExport' => 'Excel (xlsx)',
        'CsvExport' => 'CSV',
    ];

    /**
     * Holds all registered export descriptions, which describe the models that can be exported
     * @var array of classnames of descriptions
     */
    protected array $exportModelSources = [
        'AnswerExportModelSource' => 'Answers',
    ];

    public function __construct(
        protected readonly ProjectOverloader $projectOverloader,
        protected readonly array $config,
    )
    {
    }

    public function getDefaultExportClass(bool $sensitiveData = true): string
    {
        $exportClasses = $this->getExportClasses($sensitiveData);
        reset($exportClasses);
        return key($exportClasses);
    }

    /**
     *
     * @return ExportInterface
     */
    public function getExport(string $type): ExportInterface
    {
        return $this->projectOverloader->create('Export\\Type\\' . $type);
    }

    /**
     * Returns all registered export classes
     *
     * @return string[] Array Of classname => description
     */
    public function getExportClasses(bool $sensitiveData = true): array
    {
        if ($this->streamOnly($sensitiveData)) {
            return $this->streamingExportClasses;
        }
        return $this->exportClasses;
    }

    /**
     * Returns all registered export models
     *
     * @return string[] array Of classnames
     */
    public function getExportModelSources(): array
    {
        return $this->exportModelSources;
    }

    public function getExportTempDir(): string
    {
        return $this->config['rootDir'] . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'export';
    }

    public function streamOnly(bool $sensitiveData = true): bool
    {
        $disableStoringSensitiveData = $this->config['export']['disableStoringSensitiveData'] ?? true;
        return $sensitiveData && $disableStoringSensitiveData;
    }
}
