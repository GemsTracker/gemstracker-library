<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Export;

/**
 * @package    Gems
 * @subpackage Export
 * @since      Class available since version 1.0
 */
class TextExport extends ExportAbstract
{
    /**
     * Delimiter used for CSV export
     * @var string
     */
    protected $delimiter = "\t";

    /**
     * @var string  Current used file extension
     */
    protected $fileExtension = '.txt';

    protected $textTranslations = [
        "\\" => '\\\\',
        "\t" => '\\t',
        "\n" => '\\n',
        "\r" => '\\r',
    ];

    protected $view;

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->_('Text Export (tabbed)');
    }

    /**
     * @inheritDoc
     */
    protected function addHeader($filename)
    {
        $file = fopen($filename, 'w');
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        fwrite($file, $bom);

        $labels = $this->getLabeledColumns();

        fwrite($file, implode($this->delimiter, array_map([$this, 'toText'], $labels)) . "\n");

        fclose($file);
    }

    /**
     * @inheritDoc
     */
    public function addRow($row, $file)
    {
        $output = [];
        foreach ($this->getLabeledColumns() as $col) {
            if (isset($row[$col])) {
                $output[] = $this->toText($row[$col]);
            } else {
                $output[] = '';
            }
        }
        fwrite($file, implode($this->delimiter, $output) . "\n");
    }

    public function toText($input)
    {
        if ($input instanceof \Zend_Date) {
            return $input->toString(\Zend_Date::ISO_8601);
        }
        if ($input instanceof \MUtil_Html_HtmlInterface) {
            $input = $input->render($this->view);
        }
        if (is_array($input) || $input instanceof \Traversable) {
            $input = implode(':', $input);
        }
        return strtr((string) $input, $this->textTranslations);
    }
}