<?php

namespace Gems\Export\Type;

use Iterator;
use OpenSpout\Common\Entity\Row;
use ZipArchive;
use Gems\Export\Db\DataExtractorInterface;
use MUtil\Form;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\WriterInterface;
use Zalt\Model\MetaModelInterface;

class SpssExport extends CsvExportAbstract implements DownloadableInterface, ExportSettingsGeneratorInterface, ModelResultSettingsInterface
{
    public const DELIMITER = ',';

    public const EXTENSION = 'dat';

    protected array $columnLengths = [];

    public int $defaultAlphaSize   = 64;

    public int $defaultNumericSize = 5;

    protected array $modelFilterAttributes = ['formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay'];

    protected function addRows(WriterInterface $writer, Iterator $iterator, DataExtractorInterface $extractor): void
    {
        while ($row = $iterator->current()) {
            $data = $extractor->extractData($row);
            $this->findLengths($data);
            $writer->addRow(Row::fromValues($data));
            $iterator->next();
        }
    }

    protected function createSpsFile(string $baseFileName, string $exportId, array $headers, array $exportSettings): array
    {
        $spsFileLocation = $this->tempExportDir . $exportId . '.sps';
        $spsDownloadName = $baseFileName . '.sps';
        $file = fopen($spsFileLocation, 'a');
        $data = $this->createSpsFileData($baseFileName, $headers, $exportSettings);
        fwrite($file, $data);
        fclose($file);

        return [$spsFileLocation => $spsDownloadName];
    }

    protected function createSpsFileData(string $baseFileName, array $headers, array $exportSettings): string
    {
        $datDownloadName = $baseFileName . '.' . static::EXTENSION;
        $data = "SET UNICODE=ON.\n" .
            "SHOW LOCALE.\n" .
            "PRESERVE LOCALE.\n" .
            "SET LOCALE='en_UK'.\n\n" .
            "GET DATA\n" .
            " /TYPE=TXT\n" .
            " /FILE=\"" . $datDownloadName . "\"\n" .
            " /DELCASE=LINE\n" .
            " /DELIMITERS=\"".static::DELIMITER."\"\n" .
            " /QUALIFIER=\"'\"\n" .
            " /ARRANGEMENT=DELIMITED\n" .
            " /FIRSTCASE=1\n" .
            " /IMPORTCASE=ALL\n" .
            " /VARIABLES=";

        $columnTypes = $exportSettings['modelMetaData']['types'] ?? [];
        $columnNames = array_keys($headers);

        $variableTypes = $this->getVariableTypes($columnNames, $columnTypes);
        foreach($variableTypes as $columnName => $type) {
            $data .= "\n " . $columnName . ' ' . $type;
        }
        $data .= ".\nCACHE.\nEXECUTE.\n";
        $data .= "\n*Define variable labels.\n";
        foreach($headers as $columnName => $label) {
            $filteredLabel = "'" . $this->formatString($label) . "'";
            $data .="VARIABLE LABELS " . $this->fixName($columnName) . " " . $filteredLabel . "." . "\n";
        }
        $data .= "\n*Define value labels.\n";

        $columnOptions = $exportSettings['modelMetaData']['multiOptions'] ?? [];
        foreach($columnOptions as $columnName => $multiOptions) {
            $data .= 'VALUE LABELS ' . $this->fixName($columnName);
            foreach ($multiOptions as $option => $label) {
                $filteredLabel = "'" . $this->formatString($label) . "'";
                if ($option !== "") {
                    $columnType = $columnTypes[$columnName] ?? MetaModelInterface::TYPE_STRING;

                    if ($columnType !== MetaModelInterface::TYPE_NUMERIC) {
                        $option = '"' . $option . '"';
                    }
                    $data .= "\n$option $filteredLabel";
                }
            }
            $data .= "\n\n";
        }

        $data .= "RESTORE LOCALE.\n";

        return $data;
    }

    public function downloadFile(
        Iterator $iterator,
        DataExtractorInterface $extractor,
        string $exportId,
        string $fileName,
        array $exportSettings
    ): array {


        // Remove extension;
        $baseFileName = join('.', array_slice(explode('.', $fileName), 0, -1));
        $headers = $extractor->extractData($iterator->current());
        $iterator->next();


        $datFile = parent::downloadFile($iterator, $extractor, $exportId, $fileName, $exportSettings);
        $spsFile = $this->createSpsFile($baseFileName, $exportId, $headers, $exportSettings);

        $zipFileLocation = $this->tempExportDir . $exportId . '.zip';
        $zipDownloadName = $baseFileName . '.zip';

        $zipArchive = new ZipArchive();
        $zipArchive->open($zipFileLocation, ZipArchive::CREATE);

        $deleteFiles = [];

        foreach($datFile as $tempName => $newName) {
            $zipArchive->addFile($tempName, $newName);
            $deleteFiles[] = $tempName;
        }
        foreach($spsFile as $tempName => $newName) {
            $zipArchive->addFile($tempName, $newName);
            $deleteFiles[] = $tempName;
        }
        $zipArchive->close();

        foreach($deleteFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        return [$zipFileLocation => $zipDownloadName];
    }

    protected function findLengths(array $row): void
    {
        foreach($row as $columnName => $value) {
            $length = strlen((string) $value);
            if (isset($this->columnLengths[$columnName]) && $this->columnLengths[$columnName] >= $length) {
                continue;
            }
            $this->columnLengths[$columnName] = $length;
        }
    }

    protected function fixName($input)
    {
        if (!preg_match("/^([a-z]|[A-Z])+.*$/", $input)) {
            $input = "q_" . $input;
        }
        $input = str_replace(
            [" ", "-", ":", ";", "!", "/", "\\", "'"],
            ["_", "_hyph_", "_dd_", "_dc_", "_excl_", "_fs_", "_bs_", '_qu_'],
            $input ?? ''
        );
        return $input;
    }

    /**
     * Formatting of strings for SPSS export. Enclose in single quotes and escape single quotes
     * with a single quote
     *
     * Example:
     * This isn't hard to understand
     * ==>
     * 'This isn''t hard to understand'
     *
     * @param string $input
     * @return string
     */
    public function formatString(string $input): string
    {
        if (is_array($input)) {
            $input = join(', ', $input);
        }
        $output = strip_tags($input ?? '');
        $output = str_replace(array("'", "\r", "\n"), array("''", ' ', ' '), $output);
        //$output = "'" . $output . "'";
        return $output;
    }

    public function getDefaultFormValues(): array
    {
        return [];
    }

    public function getFormElements(Form &$form, array &$data): array
    {
        return [];
    }

    public function getExportSettings(array $postData): array
    {
        $settings = [];
        if (isset($postData['model'])) {
            $settings['sourceModel'] = $postData['model'];
        }
        return $settings;
    }

    /**
     * Add the help snippet
     *
     * @return string[]
     */
    public function getHelpInfo(): array
    {
        return [
            $this->translator->_('Export to SPSS'),
            $this->translator->_("Extract all files from the downloaded zip and open the .sps file.\n" .
                "Change line number 8 to include the full path to the .dat file:\n" .
                "    /FILE=\"filename.dat\"  ==>  /FILE=\"c:\\downloads\\filename.dat\"\n" .
                "Choose Run/All and all your data should be visible."
            )
        ];
    }

    public function getResultSettings(array $exportSettings, MetaModelInterface $metaModel): array
    {
        $exportSettings['modelMetaData']['types'] = $metaModel->getCol('type');
        $exportSettings['modelMetaData']['multiOptions'] = $metaModel->getCol('multiOptions');

        return $exportSettings;
    }

    protected function getVariableTypes($columnNames, $columnTypes): array
    {
        $types = [];
        foreach($columnNames as $columnName) {
            $filteredColumnName = $this->fixName($columnName);
            if (!isset($columnTypes[$columnName])) {
                continue;
            }
            $type = match ($columnTypes[$columnName]) {
                MetaModelInterface::TYPE_DATE => 'SDATE10',
                MetaModelInterface::TYPE_DATETIME => 'DATETIME23',
                MetaModelInterface::TYPE_TIME => 'TIME8.0',
                MetaModelInterface::TYPE_NUMERIC => 'F' . ($this->columnLengths[$columnName] ?? $this->defaultNumericSize) . '.' . (($this->columnLengths[$columnName] ?? $this->defaultNumericSize)-1),
                default => 'A' . ($this->columnLengths[$columnName] ?? $this->defaultAlphaSize),
            };

            $types[$filteredColumnName] = $type;
        }
        return $types;
    }

    protected function getWriter(array $exportSettings): WriterInterface
    {
        $options = new Options();
        $options->FIELD_DELIMITER = static::DELIMITER;
        return new Writer($options);
    }
}