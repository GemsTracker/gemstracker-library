<?php

namespace Gems\Export\Type;

use DateTimeImmutable;
use DateTimeInterface;
use Gems\Export\ExportSettings\ExcelExportSettings;
use Gems\Export\ExportSettings\ExportSettingsInterface;
use MUtil\Form;
use Zalt\Base\TranslatorInterface;

class StreamingExcelExport extends ExportAbstract
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
    )
    {}

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
            $daysBetween = 25569 - $diff;
        } else {
            $daysBetween = 25569 + $diff;
        }

        $seconds = $date->getTimestamp() - $endDate->getTimestamp();

        return (float)$daysBetween + ($seconds / 86400);
    }

    protected function filterDateFormat(mixed $value, string|null $dateFormat, string|null $storageFormat, ExportSettingsInterface|null $exportSettings): string|null
    {
        if ($exportSettings instanceof ExcelExportSettings && $exportSettings->formatDates) {
            if ($value === null) {
                return null;
            }
            if (!($value instanceof DateTimeInterface) && $storageFormat) {
                $value = DateTimeImmutable::createFromFormat($storageFormat, $value);
            }

            if ($value) {
                return $this->createExcelDate($value);
            }
        }

        return parent::filterDateFormat($value, $dateFormat, $storageFormat, $exportSettings);
    }

    protected function filterHtml(mixed $result): int|string|null
    {
        $result = parent::filterHtml($result);

        if (is_numeric($result)) {
            if (is_int($result)) {
                $result = (int) $result;
            } else {
                $result = (double) $result;
            }
        } else {
            $result = $this->filterCsvInjection((string) $result);
        }

        return $result;
    }

    /**
     * form elements for extra options for this particular export option
     * @param  \MUtil\Form $form Current form to add the form elements
     * @param  array $data current options set in the form
     * @return array Form elements
     */
    public function getFormElements(Form &$form, array &$data): array
    {
        $element = $form->createElement('multiCheckbox', 'format');
        $element->setLabel($this->translator->_('Excel options'))
            ->setMultiOptions(array(
                'formatVariable'=> $this->translator->_('Export labels instead of field names'),
                'formatAnswer'  => $this->translator->_('Format answers'),
                'formatDate'    => $this->translator->_('Format dates as Excel numbers easily convertable to date'),
                'combineFiles'    => $this->translator->_('Combine multiple files to separate sheets in one excel file'),
            ))
            ->setBelongsTo(static::class)
            ->setSeparator('');
        $elements['format'] = $element;

        return $elements;
    }
}