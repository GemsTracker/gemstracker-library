<?php

namespace Gems\Model;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\NestedTransformer;

class OneToManyTransformer extends NestedTransformer
{
    /**
     * Do not return field info, as it is not relevant for the parent model
     *
     * @param MetaModelInterface $model
     * @return array
     */
    public function getFieldInfo(MetaModelInterface $model)
    {
        return [];
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param MetaModelInterface $model Parent model
     * @param DataReaderInterface $sub Sub model
     * @param array $data The nested data rows
     * @param array $join The join array
     * @param string $name Name of sub model
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     */
    protected function transformLoadSubModel(MetaModelInterface $model, DataReaderInterface $sub, array &$data, array $join, $name, $new, $isPostData)
    {
        $newRow = [];
        if ($new) {
            $newRow = $sub->loadAllNew();
        }

        $joinFilter = [];
        $joinIndex = [];
        foreach ($data as $key => $row) {
            // E.g. if loaded from a post
            if (isset($row[$name])) {
                $processedRow = $sub->getMetaModel()->processAfterLoad($row[$name], $new, $isPostData);
                $data[$key][$name] = $processedRow;
                continue;
            }

            if ($new) {
                $data[$key][$name] = $newRow;
                continue;
            }

            $joinKeyParts = [];
            foreach ($join as $parent => $child) {
                if (isset($row[$parent])) {
                    $joinFilter[$child][] = $row[$parent];
                    $joinKeyParts[] = $row[$parent];
                }
            }
            if (count($joinKeyParts)) {
                $joinIndex[join('::', $joinKeyParts)] = $key;
            } else {
                $data[$key][$name] = $newRow;
            }
        }

        if (count($joinFilter)) {
            $rows = $sub->load($joinFilter);

            foreach($rows as $row) {
                $joinKeyParts = [];
                foreach ($join as $child) {
                    $joinKeyParts[] = $row[$child];
                }
                $joinKey = join('::', $joinKeyParts);
                if (isset($joinIndex[$joinKey])) {
                    $data[$joinIndex[$joinKey]][$name][] = $row;
                }
            }
        }

        return $data;
    }
}