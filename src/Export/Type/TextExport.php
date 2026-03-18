<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Export\Type;

use Gems\Export\Db\DataExtractorInterface;
use Iterator;
use MUtil\Form;

/**
 * @package    Gems
 * @subpackage Export
 * @since      Class available since version 1.0
 */
class TextExport extends ExportAbstract implements DownloadableInterface, ExportSettingsGeneratorInterface
{
    public const EXTENSION = 'txt';

    /**
     * Delimiter used for text export
     * @var string
     */
    protected $delimiter = "\t";

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.txt';

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'TextExport';
    }

    public function downloadFile(Iterator $iterator, DataExtractorInterface $extractor, string $exportId, string $fileName, array $exportSettings): array
    {
        $tempFileName = $this->tempExportDir . $exportId . '.' . static::EXTENSION;

        $file = fopen($tempFileName, 'w');
        $bom  = pack("CCC", 0xef, 0xbb, 0xbf);
        fwrite($file, $bom);

        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($exportSettings, true) . "\n", FILE_APPEND);

        while ($row = $iterator->current()) {
            $data = $extractor->extractData($row);

            $this->writeRow($file, $data);

            $iterator->next();
        }

        fclose($file);

        return [$tempFileName => $fileName];
    }

    public function getDefaultFormValues(): array
    {
        return ['format'=> ['formatVariable' => 0, 'formatAnswer' => 0]];
    }

    public function getExportSettings(array $postData): array
    {
        $typeSettings = $this->getTypeExportSettings($postData);

        return [
            'translateHeaders' => isset($typeSettings['format']) && in_array('formatVariable', $typeSettings['format']),
            'translateValues'  => isset($typeSettings['format']) && in_array('formatAnswer', $typeSettings['format']),
       ];
    }

    public function getFormElements(Form &$form, array &$data, bool $multi = false): array
    {
        $elements = [];
        $element = $form->createElement('multiCheckbox', 'format');
        if ($element instanceof \Zend_Form_Element_MultiCheckbox) {
            $element->setLabel($this->translator->_('Text options'));
            $element->setMultiOptions([
                'formatVariable' => $this->translator->_('Export labels instead of field names'),
                'formatAnswer' => $this->translator->_('Format answers')
            ]);
            $element->setBelongsTo($this->getName());

            $element->setSeparator('<br/>');
            $elements['format'] = $element;
        }

        return $elements;
    }

    protected function writeRow($file, array $row)
    {
        fwrite($file, implode($this->delimiter, str_replace($this->delimiter, ' ', $row)) . "\r\n");
    }
}