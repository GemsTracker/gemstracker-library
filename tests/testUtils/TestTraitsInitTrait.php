<?php

namespace GemsTest\testUtils;

use Laminas\Db\Adapter\Adapter;

trait TestTraitsInitTrait
{
    protected array $uses;
    protected function setupTestTraits(): void
    {
        $this->uses = array_flip(TraitUtil::getClassTraits(static::class));

        if (isset($this->uses[ContainerTrait::class])) {
            // @phpstan-ignore method.notFound
            $this->initContainer();
            if (isset($this->uses[MezzioTrait::class])) {
                // @phpstan-ignore method.notFound
                $this->initApp();
                if (isset($this->uses[PipelineTrait::class])) {
                    // @phpstan-ignore method.notFound
                    $this->initPipeline();
                }
                if (isset($this->uses[RouteTrait::class])) {
                    // @phpstan-ignore method.notFound
                    $this->initRoutes();
                }
            }
            if (isset($this->uses[MailTestTrait::class])) {
                // @phpstan-ignore method.notFound
                $this->initMailTests();
            }
        }


        if (isset($this->uses[LaminasDbTrait::class])) {
            if (isset($this->uses[ContainerTrait::class])) {
                // @phpstan-ignore property.notFound
                $this->db = $this->container->get(Adapter::class);
            } else {
                // @phpstan-ignore method.notFound
                $this->initDb();
            }

            if (isset($this->uses[ResultFetcherTrait::class])) {
                // @phpstan-ignore method.notFound
                $this->getResultFetcher();
            }

            if (isset($this->uses[DatabaseMigrationsTrait::class])) {
                // @phpstan-ignore method.notFound
                $this->runDatabaseMigrations();
            }

            if (isset($this->uses[DatabaseTransactionsTrait::class])) {
                // @phpstan-ignore method.notFound
                $this->beginDatabaseTransaction();
            }

            if (isset($this->uses[SeedTrait::class])) {
                // @phpstan-ignore method.notFound
                $this->runSeeds();
            }
        }
    }
    protected function tearDownTestTraits(): void
    {
        if (isset($this->uses[DatabaseTransactionsTrait::class])) {
            // @phpstan-ignore method.notFound
            $this->rollbackDatabaseTransaction();
        }
        if (isset($this->uses[DatabaseMigrationsTrait::class])) {
            // @phpstan-ignore method.notFound
            $this->removeDatabases();
        }
    }
}
