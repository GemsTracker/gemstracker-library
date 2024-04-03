<?php

namespace Gems\Export\Type;

use DateTimeInterface;
use Gems\Html;
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

    protected function filterDateFormat($value, string|null $dateFormat, string|null $storageFormat): string|null
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format($dateFormat);
        }

        return null;
    }

    protected function filterHtml(mixed $result): string|int|null
    {
        if ($result instanceof ElementInterface && !($result instanceof Sequence)) {
            if ($result instanceof AElement) {
                $href = $result->href;
                $result = $href;
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

    protected function filterMultiOptions(int|string|array|null $result, array $multiOptions): string|int|null
    {
        if ($multiOptions) {
            /*
             *  Sometimes a field is an array and will be formatted later on using the
             *  formatFunction -> handle each element in the array.
             */
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    if (array_key_exists($value, $multiOptions)) {
                        $result[$key] = $multiOptions[$value];
                    }
                }
            } else {
                if (array_key_exists($result, $multiOptions)) {
                    $result = $multiOptions[$result];
                }
            }
        }

        return $result;
    }

    /**
     * Filter the data in a row so that correct values are being used
     * @param MetaModelInterface $metaModel
     * @param  array $row a row in the model
     * @param bool $translateValues should the multiOption values be translated to their display value?
     * @return array The filtered row
     */
    protected function filterRow(MetaModelInterface $metaModel, array $row, bool $translateValues = false): array
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

                            $result = $this->filterDateFormat($result, $optionValue, $metaModel->get($columnName, 'storageFormat'));

                            break;
                        case 'formatFunction':
                            $result = $this->filterFormatFunction($result, $optionValue);

                            break;
                        case 'itemDisplay':
                            $result = $this->filterItemDisplay($result, $optionValue);

                            break;
                        case 'multiOptions':
                            if (!$translateValues) {
                                break;
                            }
                            $result = $this->filterMultiOptions($result, $optionValue);

                            break;
                        default:
                            break;
                    }
                }

                if ($result instanceof DateTimeInterface) {
                    $result = $this->filterDateFormat($result, 'Y-m-d H:i:s', $columnName);
                }

                $result = $this->filterHtml($result);

                $exportRow[$columnName] = $result;
            }
        }
        return $exportRow;
    }
}