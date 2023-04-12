<?php

namespace Gems\Config;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class AutoConfigurator
{
    protected ?Finder $finder = null;

    protected $autoconfigConfig = [];

    protected ?string $fileHash = null;

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

            $className = str_replace(
                '\\\\',
                '\\',
                ('Gems\\' . str_replace(
                        '/',
                        '\\',
                        $file->getRelativePath()
                    ) . '\\' . $file->getFilenameWithoutExtension())
            );
            try {
                $fileReflector = new ReflectionClass($className);
                $this->checkFileForAutoconfiguration($fileReflector);
            } catch (ReflectionException $e) {
                //echo $e->getMessage();
            }
        }

        if (count($this->autoconfigConfig)) {
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

    protected function getAutoconfigFilename(): string
    {
        return $this->config['autoconfig']['cache_path'];
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    protected function getFilePaths(): array
    {
        $rootDir = $this->config['rootDir'] ?? '';
        $moduleConfigProviders = require($rootDir.'/config/modules.php');
        $filePaths = [];

        foreach($moduleConfigProviders as $configProvider) {
            $reflector = new ReflectionClass($configProvider);
            $path = dirname($reflector->getFileName());
            if (!in_array($path, $filePaths)) {
                $filePaths[] = $path;
            }
        }
        return $filePaths;
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