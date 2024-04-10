<?php

namespace Gems\Export\Type;


use Gems\Export\Db\DataExtractorInterface;
use Gems\Export\ExportSettings\CsvDelimiter;
use MUtil\Form;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\WriterInterface;

class CsvExportAbstract extends ExportAbstract implements ExportSettingsGeneratorInterface
{
    public const EXTENSION = 'csv';

    public array $delimiterOptions = [',', ';'];

    protected function addRows(WriterInterface $writer, iterable $iterator, DataExtractorInterface $extractor): void
    {
        while ($row = $iterator->current()) {
            $data = $extractor->extractData($row);
            $writer->addRow(Row::fromValues($data));
            $iterator->next();
        }
    }

    public function downloadFile(
        iterable $iterator,
        DataExtractorInterface $extractor,
        string $exportId,
        string $fileName,
        array $exportSettings,
    ): array {
        $tempFileName = $this->tempExportDir . $exportId . '.' . static::EXTENSION;

        $writer = $this->getWriter($exportSettings);
        $writer->openToFile($tempFileName);
        $this->addRows($writer, $iterator, $extractor);
        $writer->close();

        return [$tempFileName => $fileName];
    }

    public function getExportSettings(array $postData): array
    {
        return [
            'translateHeaders' => isset($postData['formatVariable']) ? (bool) $postData['formatVariable'] : true,
            'translateValues' => isset($postData['formatAnswer']) ? (bool) $postData['formatAnswer'] : true,
            'showHeaders' => isset($postData['addHeader']) ? (bool) $postData['addHeader'] : true,
            'delimiter' => isset($postData['delimiter']) ? CsvDelimiter::from($postData['delimiter']) : CsvDelimiter::SEMICOLON,
        ];
    }

    public function getDefaultFormValues(): array
    {
        return ['format'=> ['addHeader', 'formatVariable', 'formatAnswer'], 'delimiter' => ';'];
    }

    public function getFormElements(Form &$form, array &$data): array
    {
        $element = $form->createElement('multiCheckbox', 'format');
        $element->setLabel($this->translator->_('CSV options'))
            ->setMultiOptions(array(
                'addHeader' => $this->translator->_('Add headers with column names'),
                'formatVariable' => $this->translator->_('Export labels instead of field names'),
                'formatAnswer' => $this->translator->_('Format answers')
            ))
            ->setBelongsTo($this->getName())
            ->setSeparator(' ');
        $elements['format'] = $element;

        $delimiterOptions = array_combine($this->delimiterOptions, $this->delimiterOptions);

        $element = $form->createElement('select', 'delimiter');
        $element->setLabel($this->translator->_('Delimiter'))
            ->setMultiOptions($delimiterOptions)
            ->setBelongsTo($this->getName());
        $elements['delimiter'] = $element;

        return $elements;
    }

    protected function getWriter(array $exportSettings): WriterInterface
    {
        $options = new Options();
        if (isset($exportSettings['delimiter']) && in_array($exportSettings['delimiter'], $this->delimiterOptions)) {
            $options->FIELD_DELIMITER = $exportSettings['delimiter'];
        }
        return new Writer($options);
    }

    public function streamResult(
        iterable $iterator,
        DataExtractorInterface $extractor,
        string $fileName,
        array $exportSettings,
    ): void
    {
        $writer = $this->getWriter($exportSettings);
        $writer->openToBrowser($fileName);
        $this->addRows($writer, $iterator, $extractor);
        $writer->close();
    }
}