<?php

declare(strict_types=1);

namespace Gems\Cache;

use Doctrine\ORM\EntityManagerInterface;
use Gems\Config\AutoConfigurator;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ApplicationCacheRepository
{
    public function __construct(
        protected ContainerInterface $container,
    ) {}
    public function clearCache(): bool
    {
        /** @var CacheItemPoolInterface $cache */
        $cache = $this->container->get(CacheItemPoolInterface::class);
        return $cache->clear();
    }

    public function clearAutoConfigCache(): bool
    {
        /** @var AutoConfigurator $autoconfigurator */
        $autoConfigurator = $this->container->get(AutoConfigurator::class);
        $autoconfigCachefile = $autoConfigurator->getAutoconfigFilename();

        $filesystem = new Filesystem();
        if (!$filesystem->exists($autoconfigCachefile)) {
            return false;
        }

        $filesystem->remove($autoconfigCachefile);
        return true;
    }

    public function clearConfigCache(): bool
    {
        $config = $this->container->get('config');
        $configCacheFile = $config['config_cache_path'] ?? null;

        if (!$configCacheFile) {
            return false;
        }

        $filesystem = new Filesystem();
        if (!$filesystem->exists($configCacheFile)) {
            return false;
        }
        $filesystem->remove($configCacheFile);
        return true;
    }

    public function clearTranslationCache(): bool
    {
        $config = $this->container->get('config');
        $translationCacheDir = $config['translations']['cacheDir'] ?? null;

        if (!$translationCacheDir) {
            return false;
        }

        $filesystem = new Filesystem();
        if (!$filesystem->exists($translationCacheDir)) {
            return false;
        }
        $filesystem->remove($translationCacheDir);
        return true;
    }

    public function clearDoctrineCache(): bool
    {
        $entityManager = $this->container->get(EntityManagerInterface::class);

        $entityManager->getConfiguration()->getMetadataCache()->clear();
        $entityManager->getConfiguration()->getQueryCache()->clear();
        $entityManager->getConfiguration()->getResultCache()->clear();
        return true;
    }
}