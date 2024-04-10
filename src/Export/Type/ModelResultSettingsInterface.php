<?php

namespace Gems\Export\Type;

use Zalt\Model\MetaModelInterface;

interface ModelResultSettingsInterface
{
    public function getResultSettings(array $exportSettings, MetaModelInterface $metaModel): array;
}