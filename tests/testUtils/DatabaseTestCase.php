<?php

namespace GemsTest\testUtils;

class DatabaseTestCase extends TestCase
{
    use ConfigTrait, ConfigModulesTrait {
        ConfigModulesTrait::getModules insteadof ConfigTrait;
    }

    use LaminasDbTrait;
    use ResultFetcherTrait;
    use DatabaseMigrationsTrait;
    use DatabaseTransactionsTrait;
    use ResultFetcherTrait;
    use SeedTrait;
}