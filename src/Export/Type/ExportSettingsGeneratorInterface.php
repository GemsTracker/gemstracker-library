<?php

namespace Gems\Export\Type;

interface ExportSettingsGeneratorInterface
{
    public function getExportSettings(array $postData): array;
}