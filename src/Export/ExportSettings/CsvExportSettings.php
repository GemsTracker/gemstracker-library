<?php

namespace Gems\Export\ExportSettings;

class CsvExportSettings implements ExportSettingsInterface
{
    public function __construct(
        public bool $translateHeaders = true,
        public bool $translateValues = true,
        public bool $showHeaders = true,
        public CsvDelimiter $delimiter = CsvDelimiter::SEMICOLON,
    )
    {
    }
}