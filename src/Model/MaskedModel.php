<?php

namespace Gems\Model;

use Gems\User\Mask\MaskRepository;

class MaskedModel extends JoinModel
{
    use MaskedModelTrait;

    public function setMaskRepository(MaskRepository $maskRepository): MaskedModel
    {
        $this->maskRepository = $maskRepository;

        return $this;
    }
}