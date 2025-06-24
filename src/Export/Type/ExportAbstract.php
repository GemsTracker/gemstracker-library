<?php

namespace Gems\Export\Type;

use DateTimeInterface;
use Gems\Html;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\AElement;
use Zalt\Html\ElementInterface;
use Zalt\Html\HtmlInterface;
use Zalt\Html\Sequence;
use Zalt\Late\Late;
use Zalt\Late\LateInterface;
use Zalt\Model\MetaModelInterface;

abstract class ExportAbstract implements ExportInterface
{
    protected array $modelFilterAttributes = ['multiOptions', 'formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay'];

    private string|null $name = null;

    protected string $tempExportDir;

    public function __construct(
        protected readonly TranslatorInterface $translator,
        readonly array $config,
    )
    {
        $this->tempExportDir = $config['export']['tempExportDir'] ?? 'data/export/';
    }

    /**
     * Single point for mitigating csv injection vulnerabilities
     *
     * https://www.owasp.org/index.php/CSV_Injection
     *
     * @param string|null $input
     * @return string
     */
    protected function filterCsvInjection(string|null $input): string
    {
        // Try to prevent csv injection
        $dangers = ['=', '+', '-', '@'];

        // Trim leading spaces for our test
        $trimmed = trim($input ?? '');

        if (strlen($trimmed)>1 && in_array($trimmed[0], $dangers)) {
            return "'" . $input;
        }  else {
            return $input;
        }
    }

    protected function filterDateFormat(mixed $value, string|null $dateFormat, string|null $storageFormat, array|null $exportSettings): string|null
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format($dateFormat);
        }

        return null;
    }

    protected function filterHtml(mixed $result): string|int|null|float
    {
        if ($result instanceof ElementInterface && !($result instanceof Sequence)) {
            if ($result instanceof AElement && property_exists($result, 'href')) {
                $result = $result->href;
            } elseif ($result->count() > 0) {
                $result = $result[0];
            }
        }

        if (is_object($result)) {
            // If it is Lazy, execute it
            if ($result instanceof LateInterface) {
                $result = Late::rise($result);
            }

            // If it is Html, render it
            if ($result instanceof HtmlInterface) {
                $result = $result->render();
            }
        }

        return $result;
    }

    protected function filterFormatFunction(mixed $value, callable $functionName): mixed
    {
        if (is_string($functionName) && method_exists($this, $functionName)) {
            return call_user_func(array($this, $functionName), $value);
        } else {
            return call_user_func($functionName, $value);
        }
    }

    protected function filterItemDisplay(mixed $value, callable|object|string $functionName): mixed
    {
        $result = $value;
        if (is_callable($functionName)) {
            $result = call_user_func($functionName, $value);
        } elseif (is_object($functionName)) {
            if (($functionName instanceof ElementInterface) || method_exists($functionName, 'append')) {
                $object = clone $functionName;
                $result = $object->append($value);
            }
        } elseif (is_string($functionName)) {
            // Assume it is a html tag when a string
            $result = Html::create($functionName, $value);
        }

        return $result;
    }

    protected function filterMultiOptions(int|string|array|null $result, array $multiOptions, array|null $exportSettings): mixed
    {
        if ($multiOptions) {
            if ($exportSettings !== null && isset($exportSettings['translateValues']) && $exportSettings['translateValues'] === false) {
                return $result;
            }

            // Take care of nested multi options
            $options = [];
            foreach ($multiOptions as $key => $value) {
                if (is_array($value)) {
                    $options = array_merge($options, $value);
                } else {
                    $options[$key] = $value;
                }
            }

            /*
             *  Sometimes a field is an array and will be formatted later on using the
             *  formatFunction -> handle each element in the array.
             */
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    if (array_key_exists($value, $options)) {
                        $result[$key] = $options[$value];
                    }
                }
            } else {
                if (array_key_exists($result, $options)) {
                    $result = $options[$result];
                }
            }
        }

        return $result;
    }

    /**
     * Filter the data in a row so that correct values are being used
     * @param MetaModelInterface $metaModel
     * @param  array $row a row in the model
     * @param array|null $exportSettings
     * @return array The filtered row
     */
    public function filterRow(MetaModelInterface $metaModel, array $row, array|null $exportSettings): array
    {
        $exportRow = [];
        foreach ($row as $columnName => $result) {
            if (!is_null($metaModel->get($columnName, 'label'))) {
                $options = $metaModel->get($columnName, $this->modelFilterAttributes);


                foreach ($options as $optionName => $optionValue) {
                    switch ($optionName) {
                        case 'dateFormat':
                            // if there is a formatFunction skip the date formatting
                            if (array_key_exists('formatFunction', $options)) {
                                continue 2;
                            }

                            $result = $this->filterDateFormat($result, $optionValue, $metaModel->get($columnName, 'storageFormat'), $exportSettings);

                            break;
                        case 'formatFunction':
                            $result = $this->filterFormatFunction($result, $optionValue);

                            break;
                        case 'itemDisplay':
                            $result = $this->filterItemDisplay($result, $optionValue);

                            break;
                        case 'multiOptions':
                            $result = $this->filterMultiOptions($result, $optionValue, $exportSettings);

                            break;
                        default:
                            break;
                    }
                }

                if ($result instanceof DateTimeInterface) {
                    $result = $this->filterDateFormat($result, 'Y-m-d H:i:s', $columnName, $exportSettings);
                }

                $result = $this->filterHtml($result);

                $exportRow[$columnName] = $result;
            }
        }
        return $exportRow;
    }

    public function getTypeExportSettings(array $postData): array
    {
        $exportTypeName = basename(str_replace('\\', '/', static::class));
        if (!isset($postData[$exportTypeName])) {
            return [];
        }
        return $postData[$exportTypeName];
    }

    public function getHelpInfo(): array
    {
        return [];
    }

    public function getName(): string
    {
        if (!$this->name) {
            $this->name = (new \ReflectionClass($this))->getShortName();
        }
        return $this->name;
    }
}