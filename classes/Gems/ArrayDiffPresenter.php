<?php

namespace Gems;

class ArrayDiffPresenter
{
    private readonly int $totalDepth;

    public function __construct(
        private readonly array $array1,
        private readonly array $array2,
        private readonly string $name1,
        private readonly string $name2,
    ) {
        $this->totalDepth = max($this->calculateDepth($this->array1), $this->calculateDepth($this->array2));
    }

    private function calculateDepth(array $array)
    {
        $recursiveDepth = 0;
        foreach ($array as $value) {
            if (is_array($value)) {
                $recursiveDepth = max($recursiveDepth, $this->calculateDepth($value)) - 1;
            }
        }

        return $recursiveDepth + (array_is_list($array) ? 1 : 2);
    }

    public function format(): string
    {
        $output = '<table class="adp-table">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th colspan="' . $this->totalDepth . '">' . self::escape($this->name1) . '</th>';
        $output .= '<th class="adp-separator"></th>';
        $output .= '<th colspan="' . $this->totalDepth . '">' . self::escape($this->name2) . '</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';

        $output .= $this->formatArrays($this->array1, $this->array2, 0);

        $output .= '</tbody>';
        $output .= '</table>';

        return $output;
    }

    private function formatArrays(array $array1, array $array2, int $depth): string
    {
        if ($array1 !== [] && $array2 !== [] && array_is_list($array1) !== array_is_list($array2)) {
            throw new \Exception('Invalid array comparison');
        }

        $list = array_is_list($array1) && array_is_list($array2);
        if ($list) {
            $iterator = $this->listComparingIterator($array1, $array2);
        } else {
            $iterator = $this->associativeComparingIterator($array1, $array2);
        }

        $output = '';
        foreach ($iterator as [$key, $value1, $type1, $value2, $type2]) {
            $valueIsArray = is_array($value1) || is_array($value2);
            if ($valueIsArray) {
                $value1 ??= [];
                $value2 ??= [];
            }
            if ($valueIsArray !== is_array($value1) || $valueIsArray !== is_array($value2)) {
                throw new \Exception('Invalid array comparison');
            }

            if ($valueIsArray) {
                if (!$list) {
                    $output .= '<tr>';
                    if ($depth > 0) {
                        $output .= '<td colspan="' . $depth . '"></td>';
                    }
                    $output .= '<td colspan="' . ($this->totalDepth - $depth) . '" class="' . $type1 . '">' . self::escape($type1 === null ? null : $key) . '</td>';

                    $output .= '<td class="adp-separator"></td>';
                    if ($depth > 0) {
                        $output .= '<td colspan="' . $depth . '"></td>';
                    }
                    $output .= '<td colspan="' . ($this->totalDepth - $depth) . '" class="' . $type2 . '">' . self::escape($type2 === null ? null : $key) . '</td>';
                    $output .= '</tr>';
                }

                $output .= $this->formatArrays($value1, $value2, $depth + 1);
                continue;
            }

            if ($value1 !== null && $value2 !== null && $value1 !== $value2) {
                $type1 = 'adp-delete';
                $type2 = 'adp-insert';
            }

            $output .= '<tr>';
            if ($depth > 0) {
                $output .= '<td colspan="' . $depth . '"></td>';
            }
            if (!$list) {
                $output .= '<td colspan="1" class="' . $type1 . '">' . self::escape($type1 === null ? null : $key) . '</td>';
            }
            $output .= '<td colspan="' . ($this->totalDepth - $depth - ($list ? 0 : 1)) . '" class="' . $type1 . '">' . self::escape($value1) . '</td>';

            $output .= '<td class="adp-separator"></td>';
            if ($depth > 0) {
                $output .= '<td colspan="' . $depth . '"></td>';
            }
            if (!$list) {
                $output .= '<td colspan="1" class="' . $type2 . '">' . self::escape($type2 === null ? null : $key) . '</td>';
            }
            $output .= '<td colspan="' . ($this->totalDepth - $depth - ($list ? 0 : 1)) . '" class="' . $type2 . '">' . self::escape($value2) . '</td>';
            $output .= '</tr>';
        }

        return $output;
    }

    private function listComparingIterator(array $array1, array $array2): \Generator
    {
        $i = 0;
        $j = 0;
        while ($i < count($array1) || $j < count($array2)) {
            if ($i >= count($array1)) {
                yield [null, null, null, $array2[$j], 'adp-insert'];
                $j++;
                continue;
            }

            if ($j >= count($array2)) {
                yield [null, $array1[$i], 'adp-delete', null, null];
                $i++;
                continue;
            }

            if ($array1[$i] === $array2[$j]) {
                yield [null, $array1[$i], 'adp-equal', $array2[$j], 'adp-equal'];
                $i++;
                $j++;
                continue;
            }

            $pos2in1 = array_search($array2[$j], $array1);
            $pos1in2 = array_search($array1[$i], $array2);

            if ($pos1in2 === false) {
                yield [null, $array1[$i], 'adp-delete', null, null];
                $i++;
            }

            if ($pos2in1 === false) {
                yield [null, null, null, $array2[$j], 'adp-insert'];
                $j++;
            }

            if ($pos1in2 === false || $pos2in1 === false) {
                continue;
            }

            if ($pos1in2 > $j) {
                yield [null, $array1[$i], 'adp-move', $array2[$pos1in2], 'adp-move'];
            }

            if ($pos2in1 > $i) {
                yield [null, $array1[$pos2in1], 'adp-move', $array2[$j], 'adp-move'];
            }

            $i++;
            $j++;
        }
    }

    private function associativeComparingIterator(array $array1, array $array2): \Generator
    {
        $keys1 = array_keys($array1);
        $keys2 = array_keys($array2);

        $i = 0;
        $j = 0;
        while ($i < count($keys1) || $j < count($keys2)) {
            if ($i >= count($keys1)) {
                yield [$keys2[$j], null, null, $array2[$keys2[$j]], 'adp-insert'];
                $j++;
                continue;
            }

            if ($j >= count($keys2)) {
                yield [$keys1[$i], $array1[$keys1[$i]], 'adp-delete', null, null];
                $i++;
                continue;
            }

            if ($keys1[$i] === $keys2[$j]) {
                yield [$keys1[$i], $array1[$keys1[$i]], 'adp-match', $array2[$keys2[$j]], 'adp-match'];
                $i++;
                $j++;
                continue;
            }

            $pos2in1 = array_search($keys2[$j], $keys1);
            $pos1in2 = array_search($keys1[$i], $keys2);

            if ($pos1in2 === false) {
                yield [$keys1[$i], $array1[$keys1[$i]], 'adp-delete', null, null];
                $i++;
            }

            if ($pos2in1 === false) {
                yield [$keys2[$j], null, null, $array2[$keys2[$j]], 'adp-insert'];
                $j++;
            }

            if ($pos1in2 === false || $pos2in1 === false) {
                continue;
            }

            if ($pos1in2 > $j) {
                yield [$keys1[$i], $array1[$keys1[$i]], 'adp-move', $array2[$keys1[$i]], 'adp-move'];
            }

            if ($pos2in1 > $i) {
                yield [$keys2[$j], $array1[$keys2[$j]], 'adp-move', $array2[$keys2[$j]], 'adp-move'];
            }

            $i++;
            $j++;
        }
    }

    private static function escape(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
