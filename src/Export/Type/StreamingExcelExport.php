<?php

namespace Gems\Export\Type;

use DateTimeImmutable;
use DateTimeInterface;
use MUtil\Form;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

class StreamingExcelExport extends CsvExportAbstract implements DownloadableInterface, StreamableInterface, ExportSettingsGeneratorInterface
{
    public const EXTENSION = 'xlsx';

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

        $output = [];
        if (isset($typeSettings['format'])) {
            foreach ($typeSettings['format'] as $key => $setting) {
                $output[$key] = (bool) $setting;
            }
        }

        return $output;
//        return [
//            'translateHeaders' => isset($typeSettings['format']) && in_array('formatVariable', $typeSettings['format']),
//            'translateValues'  => isset($typeSettings['format']) && in_array('formatAnswer', $typeSettings['format']),
//            'formatDates'      => isset($typeSettings['format']) && in_array('formatDate', $typeSettings['format']),
//            'combineFiles'     => isset($typeSettings['format']) && in_array('combineFiles', $typeSettings['format']),
//        ];
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
//            if ($multi) {
//                $options['combineFiles'] = $this->translator->_(
//                    'Combine multiple files to separate sheets in one excel file');
//            }

            $element->setMultiOptions($options);
            $element->setSeparator('<br/>');
            $element->setBelongsTo($this->getName());
            $elements['format'] = $element;
        }

        return $elements;
    }

    protected function getWriter(array $exportSettings): WriterInterface
    {
//        $sheetView = new SheetView();
//        $sheetView->setFreezeRow(2);

//        $options = new Options();
//        $options->SHOULD_FORMAT_DATES = true;
        $writer = new Writer();
//        $writer->getCurrentSheet()->setSheetView($sheetView);
//
        return $writer;
    }
}