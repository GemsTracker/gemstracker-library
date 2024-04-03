<?php

namespace Gems\Export\Type;

use Zalt\Model\MetaModelInterface;

interface ExportInterface
{
    function filterRow(MetaModelInterface $metaModel, array $row, bool $translateValues = false): array;
}