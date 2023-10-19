<?php

namespace Gems\Model\Transform;

use Gems\User\Mask\MaskRepository;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class MaskTransformer extends ModelTransformerAbstract
{
    public function __construct(
        protected readonly MaskRepository $maskRepository,
    )
    {
    }

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $data[$key] = $this->maskRepository->applyMaskToRow($row);
        }

        return $data;
    }
}