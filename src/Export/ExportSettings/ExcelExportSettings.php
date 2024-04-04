<?php

namespace Gems\Export\ExportSettings;

class ExcelExportSettings implements ExportSettingsInterface
{
    public function __construct(
        public bool $translateHeaders = true,
        public bool $translateValues = true,
        public bool $formatDates = false,
    )
    {
    }
}