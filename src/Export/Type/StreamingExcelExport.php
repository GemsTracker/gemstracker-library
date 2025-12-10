<?php

namespace Gems\Export\Type;

use DateTimeImmutable;
use DateTimeInterface;
use Gems\Export\Db\DataExtractorInterface;
use MUtil\Form;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Zalt\File\File;

class StreamingExcelExport extends CsvExportAbstract implements DownloadableInterface, StreamableInterface, ExportSettingsGeneratorInterface
{
    public const EXTENSION = 'xlsx';

    protected function addRows(WriterInterface $writer, \Iterator $iterator, DataExtractorInterface $extractor): void
    {
        $schema = null;

        $sheetView = new SheetView();
        $sheetView->setFreezeRow(2);
        $style  = new Style();
        $style->setFontBold();

        while ($row = $iterator->current()) {
            $data = $extractor->extractData($row);

            if ($schema !== $row['gfex_schema_name']) {
                $schema     = $row['gfex_schema_name'];
                $autofilter = new AutoFilter(0, 1, count($data), ((int) $row['gfex_row_count']) + 1);

                $sheet = $this->getSheetFor($writer, $this->cleanupSchemaName($schema));
                if ($sheet) {
                    $sheet->setSheetView($sheetView);
                    $sheet->setAutoFilter($autofilter);
                }

                $writer->addRow(Row::fromValues($data, $style));
            } else {
                $writer->addRow(Row::fromValues($data));
            }
            $iterator->next();
        }
    }

    public static function checkMultifileName(array $exportSettings, int $count): ?string
    {
        if (($count > 1) && isset($exportSettings['combineFiles']) && $exportSettings['combineFiles']) {
            $now = new \DateTimeImmutable();

            return 'Combined' . $count . 'Export.' . $now->format('YmdHis') . '.' . self::EXTENSION;
        }
        return null;
    }

    public function cleanupSchemaName(string $schemaname): string
    {
       return trim(substr(str_replace('  ', ' ', str_replace(['\\', '/', '*', '[', ']', '?', ':', '_'], '', $schemaname)), 0, 31));
    }

    /**
     * Create Excel date stamp from DateTime
     *
     * @param \DateTime $date
     * @return float number of days since 1900-01-00
     */
    public function createExcelDate(DateTimeInterface $date): float
    {
        $day = clone $date;
        $endDate = $day->setTime(0, 0, 0);
        $startDate = new DateTimeImmutable('1970-01-01 00:00:00');
        $diff = $endDate->diff($startDate)->format('%a');

        if ($endDate < $startDate) {
            $daysBetween = 25569 - (int)$diff;
        } else {
            $daysBetween = 25569 + (int)$diff;
        }

        $seconds = $date->getTimestamp() - $endDate->getTimestamp();

        return (float)$daysBetween + ($seconds / 86400);
    }

    protected function filterDateFormat(mixed $value, string|null $dateFormat, string|null $storageFormat, array|null $exportSettings): string|null
    {
        if ($exportSettings && isset($exportSettings['formatDates']) && $exportSettings['formatDates'] === true) {
            if ($value === null) {
                return null;
            }
            if (!($value instanceof DateTimeInterface) && $storageFormat) {
                $value = DateTimeImmutable::createFromFormat($storageFormat, $value);
            }

            if ($value) {
                return (string)$this->createExcelDate($value);
            }
        }

        return parent::filterDateFormat($value, $dateFormat, $storageFormat, $exportSettings);
    }

    public function getExportSettings(array $postData): array
    {
        $typeSettings = $this->getTypeExportSettings($postData);

        return [
            'translateHeaders' => isset($typeSettings['format']) && in_array('formatVariable', $typeSettings['format']),
            'translateValues'  => isset($typeSettings['format']) && in_array('formatAnswer', $typeSettings['format']),
            'formatDates'      => isset($typeSettings['format']) && in_array('formatDate', $typeSettings['format']),
            'combineFiles'     => isset($typeSettings['format']) && in_array('combineFiles', $typeSettings['format']),
        ];
    }

    /**
     * @return array Default values in form
     */
    public function getDefaultFormValues(): array
    {
        return ['format'=> ['formatVariable', 'formatAnswer']];
    }

    /**
     * form elements for extra options for this particular export option
     * @param  \MUtil\Form $form Current form to add the form elements
     * @param  array $data current options set in the form
     * @return array Form elements
     */
    public function getFormElements(Form &$form, array &$data, bool $multi = false): array
    {
        $elements = [];
        $element = $form->createElement('multiCheckbox', 'format');
        if ($element instanceof \Zend_Form_Element_MultiCheckbox) {
            $element->setLabel($this->translator->_('Excel options'));
            $options = [
                'formatVariable' => $this->translator->_('Export labels instead of field names'),
                'formatAnswer' => $this->translator->_('Format answers'),
                'formatDate' => $this->translator->_('Format dates as Excel numbers easily convertable to date'),
            ];
            if ($multi) {
                $options['combineFiles'] = $this->translator->_(
                    'Combine multiple files to separate sheets in one excel file');
            }

            $element->setMultiOptions($options);
            $element->setSeparator('<br/>');
            $element->setBelongsTo($this->getName());
            $elements['format'] = $element;
        }

        return $elements;
    }

    protected function getSheetFor(WriterInterface $writer, string $sheetName): ?Sheet
    {
        $count = 0;
        if (! $writer instanceof Writer) {
            return null;
        }

        if (empty($sheetName)) {
            $sheetName = 'Sheet';
        }

        foreach ($writer->getSheets() as $sheet) {
            if ($sheet->getName() === 'Sheet1') {
                $sheet->setName($sheetName);
                return $sheet;
            }
            if ($sheet->getName() === $sheetName) {
                // $count++;
                if (str_ends_with($sheetName, '-' . $count)) {
                    $sheetName = substr($sheetName, 0, -strlen('-' . $count));
                    $end = '-' . ++$count;
                    if (strlen($sheetName) + strlen($end) > 31) {
                        $sheetName = substr($sheetName, 0, -1);
                    }
                    $sheetName = $sheetName . $end;
                } else {
                    $sheetName = substr($sheetName, 0, 29) . '-' . ++$count;
                }
            }
        }
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName($sheetName);
        return $sheet;
    }

    protected function getWriter(array $exportSettings): WriterInterface
    {
//        $options = new Options();
//        $options->SHOULD_FORMAT_DATES = true;
        $writer = new Writer();

        return $writer;
    }
}