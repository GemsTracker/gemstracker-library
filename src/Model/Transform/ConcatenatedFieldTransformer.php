<?php

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;

class ConcatenatedFieldTransformer extends ConcatenatedFieldFilterTransformer
{
    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false): array
    {
        if ($isPostData) {
            return $data;
        }
        foreach($data as $key=>$row) {
            if (isset($row[$this->concatenatedField]) && !is_array($row[$this->concatenatedField])) {
                $values = explode($this->separator, trim($row[$this->concatenatedField], $this->separator));
                foreach($values as $valueKey => $value) {
                    if (is_numeric($value)) {
                        if (ctype_digit($value) || $value[0] === '-' && ctype_digit(substr($value, 1))) {
                            $values[$valueKey] = (int)$value;
                        }
                    }
                }
                $data[$key][$this->concatenatedField] = $values;
            }
        }
        return $data;
    }

    public function transformRowBeforeSave(MetaModelInterface $model, array $row): array
    {
        if (isset($row[$this->concatenatedField]) && is_array($row[$this->concatenatedField])) {
            $row[$this->concatenatedField] = $this->separator . join($this->separator, $row[$this->concatenatedField]) . $this->separator;
        }

        return $row;
    }
}