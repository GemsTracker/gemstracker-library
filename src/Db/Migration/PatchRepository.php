<?php

namespace Gems\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\RunPatchMigrationEvent;
use Laminas\Db\Adapter\Adapter;
use MUtil\Parser\Sql\WordsParser;
use MUtil\Translate\Translator;
use PHPUnit\Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Zalt\Loader\ConstructorProjectOverloader;

class PatchRepository extends MigrationRepositoryAbstract
{

    protected string $modelName = 'databasePatchesModel';
    public function __construct(
        array $config,
        Databases $databases,
        Translator $translator,
        EventDispatcherInterface $eventDispatcher,
        protected readonly ConstructorProjectOverloader $overloader,
    ) {
        parent::__construct($config, $databases, $translator, $eventDispatcher);
    }

    public function getPatchesFromClasses(): array
    {
        $patchClasses = $this->getResourceClasses('patches');

        $patches = [];

        foreach($patchClasses as $patchInfo) {
            $patchClassName = $patchInfo['class'];
            $patchClass = $this->overloader->create($patchClassName);
            $description = null;
            if (!$patchClass instanceof PatchInterface) {
                throw new MigrationException("$patchClassName is not a valid patch class");
            }
            $description = $patchClass->getDescription();
            $order = $patchClass->getOrder() ?? $this->defaultOrder;
            $data = $patchClass();
            $reflectionClass = new \ReflectionClass($patchClassName);

            $patch = [
                'name' => $patchClassName,
                'module' => $patchInfo['module'] ?? 'gems',
                'type' => 'patch',
                'description' => $description,
                'order' => $order,
                'data' => $data,
                'sql' => $data,
                'lastChanged' => filemtime($reflectionClass->getFileName()),
                'location' => $reflectionClass->getFileName(),
                'db' => $patchInfo['db'],
            ];

            $patches[$patchClassName] = $patch;
        }
        return $patches;
    }

    public function getPatchesDirectories(): array
    {
        return $this->getResourceDirectories('patches');
    }

    public function getPatchesFromFiles(): array
    {
        $patchesDirectories = $this->getPatchesDirectories();
        $patches = [];

        foreach($patchesDirectories as $patchesDirectory) {
            $finder = new Finder();
            $files = $finder->files()->name('*.sql')->in($patchesDirectory['path']);

            foreach ($files as $file) {
                $filenameParts = explode('.', $file->getFilenameWithoutExtension());
                $order = $this->defaultOrder;
                $name = $filenameParts[0];
                if (count($filenameParts) > 1) {
                    $order = (int)$filenameParts[0];
                    $name = $filenameParts[1];
                }

                $fileContent = $file->getContents();
                $firstRow = substr($fileContent, 0, strpos($fileContent, "\n"));
                $description = null;
                if (str_starts_with($firstRow, '--')) {
                    $description = trim(substr($firstRow, 2));
                }

                $sql = WordsParser::splitStatements($fileContent, false);

                $patch = [
                    'name' => $name,
                    'module' => $patchesDirectory['module'] ?? 'gems',
                    'type' => 'patch',
                    'description' => $description,
                    'order' => $order,
                    'data' => $fileContent,
                    'sql' => $sql,
                    'lastChanged' => $file->getMTime(),
                    'location' => $file->getRealPath(),
                    'db' => $patchesDirectory['db'],
                ];

                $patches[$name] = $patch;
            }
        }

        return $patches;
    }

    public function getInfo(): array
    {
        $patchesFromFiles = $this->getPatchesFromFiles();

        $patchesFromClasses = $this->getPatchesFromClasses();

        $patchesInfo = array_merge($patchesFromFiles, $patchesFromClasses);

        $patchesLog = array_column($this->getLoggedResources('patch'), null, 'gml_name');

        foreach($patchesInfo as $patchKey => $patchRow) {
            $patchesInfo[$patchKey]['status'] = 'new';
            $patchesInfo[$patchKey]['executed'] = null;
            $patchesInfo[$patchKey]['duration'] = null;
            $patchesInfo[$patchKey]['comment'] = null;
            if (isset($patchesLog[$patchRow['name']])) {
                $matchingLog = $patchesLog[$patchRow['name']];
                $patchesInfo[$patchKey]['status'] = $matchingLog['gml_status'];
                $patchesInfo[$patchKey]['executed'] = $matchingLog['gml_created'];
                $patchesInfo[$patchKey]['sql'] = $matchingLog['gml_sql'];
                $patchesInfo[$patchKey]['comment'] = $matchingLog['gml_comment'];
            }
        }

        return $patchesInfo;
    }
    public function runPatch(array $patchInfo): void
    {
        if (!isset($patchInfo['db'], $patchInfo['sql']) || empty($patchInfo['sql']) || !array($patchInfo['sql'])) {
            throw new \Exception('Not enough info to run patch');
        }
        $adapter = $this->databases->getDatabase($patchInfo['db']);
        if (!$adapter instanceof Adapter) {
            throw new \Exception('Not enough info to run seed');
        }

        $resultFetcher = new ResultFetcher($adapter);

        $start = microtime(true);

        try {
            foreach($patchInfo['sql'] as $sqlQuery) {
                $resultFetcher->query($sqlQuery);
            }

            $event = new RunPatchMigrationEvent(
                'patch',
                1,
                $patchInfo['module'],
                $patchInfo['name'],
                'sucess',
                join("\n", $patchInfo['sql']),
                null,
                $start,
                microtime(true),
            );

        } catch(Exception $e) {
            $event = new RunPatchMigrationEvent(
                'patch',
                1,
                $patchInfo['module'],
                $patchInfo['name'],
                'error',
                join("\n", $patchInfo['sql']),
                $e->getMessage(),
                $start,
                microtime(true),
            );
        }
        $this->eventDispatcher->dispatch($event);
    }
}