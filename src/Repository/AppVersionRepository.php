<?php

declare(strict_types=1);

namespace Gems\Repository;

use Gems\Cache\HelperAdapter;
use Gems\Db\CachedResultFetcher;
use Gems\Db\ResultFetcher;
use Gems\Versions;

/**
 * Provide access to the app versions table.
 * Note that if you have a null cache adapter, we'll query the database twice.
 */
class AppVersionRepository
{
    protected array $cacheTags = [
        'appVersions',
    ];
    protected string $currentVersionCacheKey = 'currentAppVersion';
    protected string $versionsCacheKey = 'appVersions';
    protected string $currentVersion = '';

    public function __construct(
        protected readonly Versions $versions,
        protected readonly HelperAdapter $cache,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly CachedResultFetcher $cachedResultFetcher,
    ) {
        $this->currentVersion = $this->versions->getProjectVersion();
    }

    public function getAll(): array
    {
        if ($this->needToPopulate()) {
            $this->populate();
        }
        return $this->cachedResultFetcher->fetchCol(
            $this->versionsCacheKey,
            'SELECT gav_app_version FROM gems__app_versions ORDER BY gav_app_version DESC',
            null,
            $this->cacheTags
        );
    }

    /**
     * We need to update the gems__app_versions table every time the app version changes.
     */
    protected function needToPopulate(): bool
    {
        return $this->cache->getCacheItem($this->currentVersionCacheKey) != $this->currentVersion;
    }

    /**
     * Populate the gems__app_versions table.
     * When the table is empty, it will be filled with the distinct app versions
     * from the gems__log_activity table. On subsequent runs, we only check if the
     * current app version is in the table, and if not, we add it.
     */
    private function populate(): void
    {
        $versions = $this->resultFetcher->fetchCol('SELECT gav_app_version FROM gems__app_versions ORDER BY gav_app_version DESC');
        if (empty($versions)) {
            // Should only happen once.
            $sql = "SELECT DISTINCT gla_app_version
                FROM gems__log_activity
                WHERE gla_app_version IS NOT NULL
                ORDER BY gla_app_version ASC";
            $loggedVersions = $this->resultFetcher->fetchCol($sql);
            foreach ($loggedVersions as $version) {
                $this->resultFetcher->insertIntoTable('gems__app_versions', ['gav_app_version' => $version]);
            }
            $versions = $loggedVersions;
        }
        if (!in_array($this->currentVersion, $versions)) {
            $this->resultFetcher->insertIntoTable('gems__app_versions', ['gav_app_version' => $this->currentVersion]);
        }
        // Set the current app version in the cache.
        $this->cache->setCacheItem($this->currentVersionCacheKey, $this->currentVersion);
        // Ensure we get fresh results.
        $this->cachedResultFetcher->invalidateTags($this->cacheTags);
    }
}
