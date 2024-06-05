<?php

namespace GemsTest\testUtils;

trait TestTraitsInitTrait
{
    protected array $uses;
    protected function setupTestTraits(): void
    {
        $this->uses = array_flip(TraitUtil::getClassTraits(static::class));

        if (isset($this->uses[ContainerTrait::class])) {
            $this->initContainer();
            if (isset($this->uses[MezzioTrait::class])) {
                $this->initApp();
                if (isset($this->uses[PipelineTrait::class])) {
                    $this->initPipeline();
                }
                if (isset($this->uses[RouteTrait::class])) {
                    $this->initRoutes();
                }
            }
        }


        if (isset($this->uses[LaminasDbTrait::class])) {
            $this->initDb();
            if (isset($this->uses[ResultFetcherTrait::class])) {
                $this->getResultFetcher();
            }

            if (isset($this->uses[DatabaseMigrationsTrait::class])) {
                $this->runDatabaseMigrations();
            }

            if (isset($this->uses[DatabaseTransactionsTrait::class])) {
                $this->beginDatabaseTransaction();
            }

            if (isset($this->uses[SeedTrait::class])) {
                $this->runSeeds();
            }
        }
    }
    protected function tearDownTestTraits(): void
    {
        if (isset($this->uses[DatabaseTransactionsTrait::class])) {
            $this->rollbackDatabaseTransaction();
        }
        if (isset($this->uses[DatabaseMigrationsTrait::class])) {
            $this->removeDatabases();
        }
    }
}