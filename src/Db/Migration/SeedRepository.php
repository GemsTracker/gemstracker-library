<?php

namespace Gems\Db\Migration;

use Gems\Db\Databases;
use Gems\Db\ResultFetcher;
use Laminas\Db\Sql\Expression;
use MUtil\Translate\Translator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Zalt\Loader\ConstructorProjectOverloader;

class SeedRepository extends MigrationRepositoryAbstract
{
    public function __construct(
        array $config,
        Databases $databases,
        Translator $translator,
        EventDispatcherInterface $eventDispatcher,
        protected readonly ConstructorProjectOverloader $overloader,
    ) {
        parent::__construct($config, $databases, $translator, $eventDispatcher);
    }

    protected $seedFileTypes = [
        'yml',
        'yaml',
        'json',
    ];

    protected function getSeedDataFromFile(SplFileInfo $file): array|null
    {
        $extension = $file->getExtension();


        if ($extension === 'yml' || $extension === 'yaml') {
            return Yaml::parse($file->getContents());
        }
        if ($extension === 'json') {
            return json_decode($file->getContents(), true);
        }

        return null;
    }

    public function getSeedInfo(): array
    {
        $seedsFromFiles = $this->getSeedsFromFiles();
        $seedsFromClasses = $this->getSeedsFromClasses();

        $seedInfo = array_merge($seedsFromFiles,$seedsFromClasses);

        $seedLogs = array_column($this->getLoggedResources('seeds'), null, 'gml_name');

        foreach($seedInfo as $seedKey => $seedRow) {
            $seedInfo[$seedKey]['status'] = null;
            $seedInfo[$seedKey]['executed'] = null;
            $seedInfo[$seedKey]['duration'] = null;
            $seedInfo[$seedKey]['sql'] = null;
            $seedInfo[$seedKey]['comment'] = null;
            if (isset($seedLogs[$seedRow['name']])) {
                $matchingLog = $seedLogs[$seedRow['name']];
                $seedInfo[$seedKey]['status'] = $matchingLog['gml_status'];
                $seedInfo[$seedKey]['executed'] = $matchingLog['gml_created'];
                $seedInfo[$seedKey]['sql'] = $matchingLog['gml_sql'];
                $seedInfo[$seedKey]['comment'] = $matchingLog['gml_comment'];
            }
        }

        return $seedInfo;
    }

    public function getSeedsFromClasses(): array
    {
        $seedClasses = $this->getResourceClasses('seeds');

        print_r($seedClasses);

        $seeds = [];

        foreach($seedClasses as $seedClassInfo) {
            $seedClassName = $seedClassInfo['class'];
            $seedClass = $this->overloader->create($seedClassName);
            $description = null;
            if (!$seedClass instanceof SeedInterface) {
                throw new MigrationException("$seedClassName is not a valid seed class");
            }
            $description = $seedClass->getDescription();
            $data = $seedClass();
            $reflectionClass = new \ReflectionClass($seedClassName);

            $seed = [
                'name' => $seedClassName,
                'type' => 'seed',
                'description' => $description,
                'order' => $this->defaultOrder,
                'data' => $data,
                'lastChanged' => filemtime($reflectionClass->getFileName()),
                'location' => $reflectionClass->getFileName(),
                'db' => $seedClassInfo['db'],
            ];

            $seeds[] = $seed;
        }
        return $seeds;
    }

    public function getSeedsFromFiles()
    {
        $directories = $this->getSeedsDirectories();
        $seeds = [];

        $finder = new Finder();
        $searchNames = array_map(function(string $filename) {
            return "*.$filename";
        }, $this->seedFileTypes);

        foreach($directories as $directory) {
            $files = $finder->files()->name($searchNames)->in($directory['path']);


            foreach ($files as $file) {
                $filenameParts = explode('.', $file->getBaseName('.sql'));
                $name = $filenameParts[0];
                $data = $this->getSeedDataFromFile($file);
                $description = $data['description'] ?? null;
                $seed = [
                    'name' => $filenameParts[0],
                    'type' => 'seed',
                    'description' => $description,
                    'order' => $this->defaultOrder,
                    'data' => $data,
                    'lastChanged' => $file->getMTime(),
                    'location' => $file->getRealPath(),
                    'db' => $directory['db'],
                ];
                if (count($filenameParts) === 2 && is_numeric($filenameParts[1])) {
                    $seed['order'] = (int)$filenameParts[1];
                }
                $seeds[$name] = $seed;
            }
        }

        return $seeds;
    }

    public function getSeedsDirectories(): array
    {
        return $this->getResourceDirectories('seeds');
    }
}