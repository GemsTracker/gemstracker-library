<?php

namespace Gems\Model;

use Gems\User\Mask\MaskRepository;

class MaskedModel extends JoinModel
{
    /**
     * @var boolean When true the labels of wholly masked items are removed
     */
    protected bool $hideWhollyMasked = false;

    /**
     * @var MaskRepository
     */
    protected $maskRepository;

    public function applyMask(): MaskedModel
    {
        $this->maskRepository->applyMaskToDataModel($this, $this->hideWhollyMasked);

        return $this;
    }

    public function setMaskRepository(MaskRepository $maskRepository): MaskedModel
    {
        $this->maskRepository = $maskRepository;

        return $this;
    }
}