<?php

namespace Gems\Session;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Zalt\Base\BaseDir;

class PhpSessionPersistenceFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->has('config') ? $container->get('config') : [];

        $session     = isset($config['session']) && is_array($config['session']) ? $config['session'] : [];
        $persistence = isset($session['persistence']) && is_array($session['persistence'])
            ? $session['persistence'] : [];
        $options     = isset($persistence['ext']) && is_array($persistence['ext']) ? $persistence['ext'] : [];

        $persistenceConfig = $config['mezzio-session-php'] ?? [];

        $cookieName     = $persistenceConfig['cookie_name'] ?? ini_get('session.name') ?? 'PHPSESSION';
        $cookieDomain   = $persistenceConfig['cookie_domain'] ?? ini_get('session.cookie_domain');
        $cookiePath     = $persistenceConfig['cookie_path'] ?? BaseDir::getBaseDir(); // ini_get('session.cookie_path');
        $cookieSecure   = $persistenceConfig['cookie_secure'] ?? filter_var(
            ini_get('session.cookie_secure'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        $cookieHttpOnly = $persistenceConfig['cookie_http_only'] ?? filter_var(
            ini_get('session.cookie_httponly'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        $cookieSameSite = $persistenceConfig['cookie_same_site'] ?? ini_get('session.cookie_samesite');
        $cacheLimiter   = $persistenceConfig['cache_limiter'] ?? ini_get('session.cache_limiter');
        $cacheExpire    = $persistenceConfig['cache_expire'] ?? (int) ini_get('session.cache_expire');
        $lastModified   = $persistenceConfig['last_modified'] ?? null;
        $persistent     = $persistenceConfig['persistent'] ?? false;

        return new PhpSessionPersistence(
            ! empty($options['non_locking']),
            ! empty($options['delete_cookie_on_empty_session']),
            $cookieName,
            $cookiePath,
            $cacheLimiter,
            $cacheExpire,
            $lastModified,
            $persistent,
            $cookieDomain,
            $cookieSecure,
            $cookieHttpOnly,
            $cookieSameSite
        );
    }
}