<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

use Gems\User\Mask\MaskRepository;

/**
 * @package    Gems
 * @subpackage Model
 * @since      Class available since version 1.0
 */
trait MaskedModelTrait
{
    /**
     * @var boolean When true the labels of wholly masked items are removed
     */
    protected bool $hideWhollyMasked = false;

    /**
     * @var MaskRepository
     */
    protected $maskRepository;

    public function applyMask(): void
    {
        $this->maskRepository->applyMaskToDataModel($this->getMetaModel(), $this->hideWhollyMasked);
    }
}