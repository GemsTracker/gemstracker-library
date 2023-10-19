<?php

namespace Gems\Config;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class AutoConfigurator
{
    protected ?Finder $finder = null;

    protected $autoconfigConfig = [];

    protected ?string $fileHash = null;

    protected ?array $filePaths = null;

    protected ?array $sortedFilePaths = null;

    public function __construct(protected readonly array $config)
    {}

    protected function addChecksumToAutoConfig()
    {
        $this->autoconfigConfig['autoconfig']['checksum'] = $this->getFilesHash();
    }

    public function autoConfigure(bool $recreate = false, bool $assumeFresh = false): array
    {
        if (!$recreate && ($assumeFresh || $this->isFresh())) {
            return $this->config;
        }

        $finder = $this->getFinder();

        foreach($finder as $file) {
            $className = $this->getClassnameFromFile($file);
            if ($className === null) {
                continue;
            }

            try {
                $fileReflector = new ReflectionClass($className);
                $this->checkFileForAutoconfiguration($fileReflector);
            } catch (ReflectionException $e) {
//                file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  $e->getMessage() . "\n", FILE_APPEND);
                //echo $e->getMessage();
            }
        }

        if (count($this->autoconfigConfig)) {
            $this->clearConfigCache();
            $this->addChecksumToAutoConfig();
            $this->writeAutoConfigConfig();
            return $this->getNewConfig();
        }
        return $this->config;
    }

    protected function checkFileForAutoconfiguration(ReflectionClass $reflector): void
    {
        if (isset($this->config['autoconfig'], $this->config['autoconfig']['settings'])) {
            $settings = $this->config['autoconfig']['settings'];
            if (isset($settings['implements'])) {
                foreach($settings['implements'] as $interfaceClass => $targetSettings) {
                    if ($reflector->implementsInterface($interfaceClass) && !$reflector->isAbstract()) {
                        $this->setSettings($reflector, $targetSettings);
                    }
                }
            }
            if (isset($settings['extends'])) {
                foreach($settings['extends'] as $parentClass => $targetSettings) {
                    if ($reflector->isSubclassOf($parentClass) && !$reflector->isAbstract()) {
                        $this->setSettings($reflector, $targetSettings);
                    }
                }
            }
            if (isset($settings['attribute'])) {
                foreach($settings['attribute'] as $attributeClass => $targetSettings) {
                    if ($reflector->getAttributes($attributeClass) && !$reflector->isAbstract() && !$reflector->isInterface() && !$reflector->isTrait()) {
                        $this->setSettings($reflector, $targetSettings);
                    }
                }
            }
        }
    }

    protected function clearConfigCache(): void
    {
        $configCacheFileLocation = $this->config['config_cache_path'] ?? null;
        if ($configCacheFileLocation && file_exists($configCacheFileLocation)) {
            unlink($configCacheFileLocation);
        }
    }

    protected function getAutoconfigFilename(): string
    {
        return $this->config['autoconfig']['cache_path'];
    }

    protected function getClassnameFromFile(SplFileInfo $file): ?string
    {
        $filePathNamespaces = $this->getSortedFilePaths();

        $namespace = null;
        foreach($filePathNamespaces as $fileNameSpace => $filePath) {
            if (str_starts_with($file->getPath(), $filePath)) {
                $namespace = $fileNameSpace;
            }
        }

        if ($namespace !== null) {
            return str_replace(
                '\\\\',
                '\\',
                ($namespace . '\\' . str_replace(
                        '/',
                        '\\',
                        $file->getRelativePath()
                    ) . '\\' . $file->getFilenameWithoutExtension())
            );
        }

        return null;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    protected function getFilePaths(): array
    {
        if (!$this->filePaths) {
            $rootDir = $this->config['rootDir'] ?? '';
            $moduleConfigProviders = require($rootDir . '/config/modules.php');
            $filePaths = [];

            foreach ($moduleConfigProviders as $configProvider) {
                $reflector = new ReflectionClass($configProvider);
                $path = dirname($reflector->getFileName());
                $namespace = $reflector->getNamespaceName();
                if (!in_array($path, $filePaths)) {
                    $filePaths[$namespace] = $path;
                }
            }
            $this->filePaths = $filePaths;
        }
        return $this->filePaths;
    }

    protected function getFinder(): Finder
    {
        if ($this->finder === null) {
            $paths = $this->getFilePaths();
            $this->finder = new Finder();
            $this->finder->files()
                ->in($paths)
                ->name('*.php');
        }

        return $this->finder;
    }

    public function getFilesHash(): string
    {
        if ($this->fileHash === null) {
            $checkValues = [];
            $finder = $this->getFinder();
            foreach ($finder as $file) {
                $checkValues[] = $file->getPath() . $file->getFilenameWithoutExtension() . $file->getMtime();
            }
            $this->fileHash = crc32(join(':', $checkValues));
        }
        return $this->fileHash;
    }

    protected function getNewConfig(): array
    {
        return require $this->config['rootDir'] . '/config/config.php';
    }

    protected function getSortedFilePaths(): ?array
    {
        if (!$this->sortedFilePaths) {
            $filePaths = $this->getFilePaths();
            uksort($filePaths, function($a, $b) {
                return -(strlen($a) - strlen($b));
            });

            $this->sortedFilePaths = $filePaths;
        }
        return $this->sortedFilePaths;
    }

    public function hasChangedFiles(string $currentChecksum): bool
    {
        $newChecksum = $this->getFilesHash();
        if ($newChecksum === $currentChecksum) {
            return false;
        }
        return true;
    }

    public function isFresh(): bool
    {
        if (!isset($this->config['autoconfig'], $this->config['autoconfig']['checksum'])) {
            return false;
        }
        $env = $this->config['app']['env'];
        if ($env !== 'development') {
            return true;
        }
        return !$this->hasChangedFiles($this->config['autoconfig']['checksum']);
    }

    protected function setSettings(ReflectionClass $class, string|array $settings): void
    {
        if (is_string($settings)) {
            if (class_exists($settings)) {
                $settingGenerator = new $settings();
                $newConfig = $settingGenerator($class, $this->config);
                $this->autoconfigConfig = array_merge_recursive($this->autoconfigConfig, $newConfig);
            }
            return;
        }

        if (is_array($settings) && isset($settings['config'])) {
            $configNamespaceArray = explode('.', $settings['config']);

            $result = [];
            foreach(array_reverse($configNamespaceArray) as $namespaceItem) {
                if (!$result) {
                    $result = [$namespaceItem => [$class->getName()]];
                    continue;
                }
                $result = [$namespaceItem => $result];
            }

            $this->autoconfigConfig = array_merge_recursive($this->autoconfigConfig, $result);
        }
    }

    protected function writeAutoConfigConfig(): void
    {
        $filename = $this->getAutoconfigFilename();
        file_put_contents($filename, '<?php return ' . var_export($this->autoconfigConfig, true) . ';');
    }
}