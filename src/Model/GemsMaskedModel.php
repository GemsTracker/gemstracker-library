<?php

namespace Gems\Model;

use Gems\User\Mask\MaskRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class GemsMaskedModel extends GemsJoinModel
{
    use MaskedModelTrait;

    public function __construct(
        string $tableName,
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        MaskRepository $maskRepository,
        string $modelName = null,
        bool $savable = true,
    ) {
        parent::__construct($tableName, $metaModelLoader, $sqlRunner, $translate, $modelName, $savable);

        $this->setMaskRepository($maskRepository);
    }

    public function setMaskRepository(MaskRepository $maskRepository): GemsMaskedModel
    {
        $this->maskRepository = $maskRepository;

        return $this;
    }
}