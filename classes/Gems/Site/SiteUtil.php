<?php

namespace Gems\Site;

use Gems\Cache\HelperAdapter;
use Gems\Model\SiteModel;
use Gems\Model\Transform\TranslateDatabaseFields;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Hydrator\ReflectionHydrator;
use MUtil\Model\Transform\TranslateFieldNames;
use Psr\Http\Message\ServerRequestInterface;

class SiteUtil
{
    CONST ORG_SEPARATOR = '|';

    protected HelperAdapter $cache;

    protected string $cacheKey = 'app.sites';

    protected array $config = [];

    protected array $databaseFieldTranslations = [
        'gsi_id' => 'id',
        'gsi_url' => 'url',
        'gsi_organizations' => 'organizations',
        'gsi_style' => 'style',
        'gsi_iso_lang' => 'language',
        'gsi_active' => 'active',
        'gsi_blocked' => 'blocked',
    ];

    protected Adapter $db;

    protected array $sites;

    public function __construct(Adapter $db, HelperAdapter $cache, array $config)
    {
        $this->db = $db;
        $this->cache = $cache;
        if (isset($config['sites'])) {
            $this->config = $config['sites'];
        }
        $this->sites = $this->getSites();
    }

    /**
     * @param $orgId
     * @return string Preferred url for organization
     */
    public function getOrganizationPreferredUrl(int $orgId): ?string
    {
        foreach($this->sites as $site) {
            if ($site->isAllowedForOrganization($orgId)) {
                return $site->getUrl();
            }
        }

        return null;
    }

    protected function getDatabaseSites(): array
    {
        if ($this->cache->hasItem($this->cacheKey)) {
            return $this->cache->getCacheItem($this->cacheKey);
        }

        $model = $this->getSitesModel();
        $sites = $model->load([], ['gsi_order']);

        $this->cache->setCacheItem($this->cacheKey, $sites);
        return $sites;
    }

    public function getSiteFromUrl(string $url): ?SiteUrl
    {
        foreach($this->sites as $site) {
            if ($site->getUrl() === $url) {
                return $site;
            }
        }
        return null;
    }

    public function getSiteByFullUrl(string $url): ?SiteUrl
    {
        foreach($this->sites as $site) {
            if (str_starts_with($url, $site->getUrl())) {
                return $site;
            }
        }
        return null;
    }

    /**
     * @return SiteUrl[]
     */
    protected function getSites(): array
    {
        $hydrator = new ReflectionHydrator();
        $sitesArray = $this->getSitesArray();
        $sites = [];
        foreach($sitesArray as $site) {
            $sites[] = $hydrator->hydrate($site, new SiteUrl());
        }

        return $sites;
    }

    protected function getSitesArray(): array
    {
        if (isset($this->config['allowed'])) {
            return $this->config['allowed'];
        }
        if (isset($config['useDatabase']) && $config['useDatabase'] === true) {
            return $this->getDatabaseSites();
        }

        return [];
    }

    protected function getSitesModel(): \MUtil_Model_ModelAbstract
    {
        $model = new \MUtil_Model_TableModel('gems__sites');
        $ct = new \MUtil_Model_Type_ConcatenatedRow(static::ORG_SEPARATOR, ', ', true);
        $ct->apply($model, 'gsi_organizations');
        $model->addTransformer(new TranslateFieldNames($this->databaseFieldTranslations));

        return $model;
    }

    protected function getUrlFromHost(ServerRequestInterface $request, string $host): string
    {
        $serverParams = $request->getServerParams();
        $protocol = 'https';
        if (isset($serverParams['REQUEST_SCHEME'])) {
            $protocol = $serverParams['REQUEST_SCHEME'];
        }
        return $protocol . '://' . $host;
    }

    public function isLocked(): bool
    {
        if (isset($config['useDatabase'], $config['locked']) && $config['useDatabase'] === true && $config['locked'] === false) {
            return false;
        }
        return true;
    }

    public function isRequestFromAllowedUrl(ServerRequestInterface $request): bool
    {
        $hosts = [];
        $serverParams = $request->getServerParams();

        if (isset($serverParams['HTTP_HOST'])) {
            $hosts[] = $serverParams['HTTP_HOST'];
        }
        if (isset($serverParams['SERVER_NAME'])) {
            $hosts[] = $serverParams['SERVER_NAME'];
        }

        foreach(array_unique($hosts) as $host) {
            $url = $this->getUrlFromHost($request, $host);
            $site = $this->getSiteFromUrl($url);
            if ($site === null) {
                throw new NotAllowedUrlException($url);
            }
            if ($site instanceof SiteUrl && (!$site->isActive() || $site->isBlocked())) {
                throw new NotAllowedUrlException($url);
            }
        }

        if ($request->getMethod() === 'POST') {
            $referrers = [];
            if (isset($serverParams['HTTP_ORIGIN'])) {
                $referrers[] = $serverParams['HTTP_ORIGIN'];
            }
            if (isset($serverParams['HTTP_REFERER'])) {
                $referrers[] = $serverParams['HTTP_REFERER'];
            }
            foreach(array_unique($referrers) as $referrerUrl) {
                $site = $this->getSiteByFullUrl($referrerUrl);
                if ($site === null) {
                    throw new NotAllowedUrlException($url);
                }
                if ($site instanceof SiteUrl && (!$site->isActive() || $site->isBlocked())) {
                    throw new NotAllowedUrlException($url);
                }
            }
        }

        return true;
    }
}