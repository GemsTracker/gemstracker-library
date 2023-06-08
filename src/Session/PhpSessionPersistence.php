<?php

namespace Gems\Session;

use Mezzio\Session\Persistence\CacheHeadersGeneratorTrait;
use Mezzio\Session\Persistence\SessionCookieAwareTrait;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PhpSessionPersistence extends \Mezzio\Session\Ext\PhpSessionPersistence
{
    use CacheHeadersGeneratorTrait;
    use SessionCookieAwareTrait;

    public function __construct(
        private readonly bool $nonLocking = false,
        bool $deleteCookieOnEmptySession = false,
        string $cookieName = 'PHPSESSION',
        string $cookiePath = '/',
        string $cacheLimiter = 'nocache',
        int $cacheExpire = 10800,
        ?int $lastModified = null,
        private readonly bool $persistent = false,
        ?string $cookieDomain = null,
        bool $cookieSecure = false,
        bool $cookieHttpOnly = false,
        string $cookieSameSite = 'Lax'
    )
    {
        //parent::__construct($this->nonLocking, $deleteCookieOnEmptySession);
        $this->deleteCookieOnEmptySession = $deleteCookieOnEmptySession;

        $this->cookieName = $cookieName;
        $this->cookiePath = $cookiePath;
        $this->cacheExpire = $cacheExpire;
        $this->cookieSameSite = $cookieSameSite;
        $this->cookieDomain = $cookieDomain;
        $this->cookieSecure = $cookieSecure;
        $this->cookieHttpOnly = $cookieHttpOnly;
        $this->cacheLimiter = $cacheLimiter;
        $this->lastModified = $lastModified;
    }

    public function initializeSessionFromRequest(ServerRequestInterface $request): SessionInterface
    {
        $sessionId = $this->getSessionCookieValueFromRequest($request);
        if ($sessionId) {
            $this->startSession($sessionId, [
                'read_and_close' => $this->nonLocking,
            ]);
        }
        return new Session($_SESSION ?? [], $sessionId);
    }

    public function persistSession(SessionInterface $session, ResponseInterface $response): ResponseInterface
    {
        $id = $session->getId();

        // Regenerate if:
        // - the session is marked as regenerated
        // - the id is empty, but the data has changed (new session)
        if (
            $session->isRegenerated()
            || ($id === '' && $session->hasChanged())
        ) {
            $id = $this->regenerateSession();
        } elseif ($this->nonLocking && $session->hasChanged()) {
            // we reopen the initial session only if there are changes to write
            $this->startSession($id);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = $session->toArray();
            session_write_close();
        }

        // If we do not have an identifier at this point, it means a new
        // session was created, but never written to. In that case, there's
        // no reason to provide a cookie back to the user.
        if ($id === '') {
            return $response;
        }

        // A session that did not change at all does not need to be sent to the browser
        if (! $session->hasChanged()) {
            return $response;
        }

        $response = $this->addSessionCookieHeaderToResponse($response, $id, $session);
        $response = $this->addCacheHeadersToResponse($response);

        return $response;
    }

    public function initializeId(SessionInterface $session): SessionInterface
    {
        $id = $session->getId();
        if ($id === '' || $session->isRegenerated()) {
            $session = new Session($session->toArray(), $this->generateSessionId());
        }

        session_id($session->getId());

        return $session;
    }

    /**
     * @param array $options Additional options to pass to `session_start()`.
     */
    private function startSession(string $id, array $options = []): void
    {
        session_id($id);
        session_start([
                'use_cookies'      => false,
                'use_only_cookies' => true,
                'cache_limiter'    => '',
            ] + $options);
    }

    /**
     * Regenerates the session safely.
     */
    private function regenerateSession(): string
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $id = $this->generateSessionId();
        $this->startSession($id, [
            'use_strict_mode' => false,
        ]);
        return $id;
    }

    /**
     * Generate a session identifier.
     */
    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }
}