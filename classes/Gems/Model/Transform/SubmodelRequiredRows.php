<?php

namespace Gems\Model\Transform;

use MUtil\Model\Transform\RequiredRowsTransformer;
use Zalt\Model\MetaModelInterface;

class SubmodelRequiredRows extends RequiredRowsTransformer
{
    public function __construct(protected string $subModelName)
    {}

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false)
    {
        if ($model->has($this->subModelName, 'model')) {
            $subModel = $model->get($this->subModelName, 'model');
            foreach($data as $key => $row) {
                $result = parent::transformLoad($subModel, $row[$this->subModelName], $new, $isPostData);
                $data[$key][$this->subModelName] = $result;
            }
        }
        return $data;
    }
}