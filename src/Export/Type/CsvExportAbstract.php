<?php

namespace Gems\Export\Type;

use Iterator;
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

    protected function addRows(WriterInterface $writer, Iterator $iterator, DataExtractorInterface $extractor): void
    {
        while ($row = $iterator->current()) {
            $data = $extractor->extractData($row);
            $writer->addRow(Row::fromValues($data));
            $iterator->next();
        }
    }

    public function downloadFile(
        Iterator $iterator,
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
        $typeSettings = $this->getTypeExportSettings($postData);

        return [
            'translateHeaders' => isset($typeSettings['format']) && in_array('formatVariable', $typeSettings['format']),
            'translateValues' => isset($typeSettings['format']) && in_array('formatAnswer', $typeSettings['format']),
            'showHeaders' => isset($typeSettings['format']) && in_array('addHeader', $typeSettings['format']),
            'delimiter' => isset($typeSettings['delimiter']) ? CsvDelimiter::from($typeSettings['delimiter']) : CsvDelimiter::SEMICOLON,
        ];
    }

    public function getDefaultFormValues(): array
    {
        return ['format'=> ['addHeader', 'formatVariable', 'formatAnswer'], 'delimiter' => ';'];
    }

    public function getFormElements(Form &$form, array &$data, bool $multi = false): array
    {
        $elements = [];
        $element = $form->createElement('multiCheckbox', 'format');
        if ($element instanceof \Zend_Form_Element_MultiCheckbox) {
            $element->setLabel($this->translator->_('CSV options'));
            $element->setMultiOptions([
                    'addHeader' => $this->translator->_('Add headers with column names'),
                    'formatVariable' => $this->translator->_('Export labels instead of field names'),
                    'formatAnswer' => $this->translator->_('Format answers')
            ]);
            $element->setBelongsTo($this->getName());
            $element->setSeparator('<br/>');
            $elements['format'] = $element;

        }

        $delimiterOptions = array_combine($this->delimiterOptions, $this->delimiterOptions);

        $element = $form->createElement('select', 'delimiter');
        if ($element instanceof \Zend_Form_Element_Select) {
            $element->setLabel($this->translator->_('Delimiter'));
            $element->setMultiOptions($delimiterOptions)
                ->setBelongsTo($this->getName());
            $elements['delimiter'] = $element;
        }

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
        Iterator $iterator,
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