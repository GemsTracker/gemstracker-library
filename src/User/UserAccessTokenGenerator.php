<?php

namespace Gems\User;

use DateTimeImmutable;
use DateInterval;
use Error;
use Exception;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use TypeError;
use Gems\Legacy\CurrentUserRepository;
use Gems\OAuth2\Repository\AccessTokenRepository;
use Gems\OAuth2\Repository\ClientRepository;
use Gems\OAuth2\Repository\ScopeRepository;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;

class UserAccessTokenGenerator
{
    protected const MAX_RANDOM_TOKEN_GENERATION_ATTEMPTS = 10;

    protected readonly array $oauth2Config;

    protected readonly CryptKey $privateKey;



    public function __construct(
        protected readonly ClientRepository $clientRepository,
        protected readonly ScopeRepository $scopeRepository,
        protected readonly AccessTokenRepository $accessTokenRepository,
        protected readonly CurrentUserRepository $currentUserRepository,
        array $config,
    )
    {
        $privateKey = $config['certificates']['private'] ?? '';
        $this->privateKey = new CryptKey($privateKey);
        $this->oauth2Config = $config['oauth2'] ?? [];
    }

    public function getAccessTokenFor(string $clientId, string $loginName, int $organizationId): string
    {
        $client = $this->getClient($clientId);
        $scopes = $this->getScopes();
        $userIdentifier = $this->getUserIdentifier($loginName, $organizationId);

        $accessTokenTTL = $this->getAccessTokenTTl();

        $accessToken = $this->accessTokenRepository->getNewToken($client, $scopes, $userIdentifier);
        $accessToken->setExpiryDateTime((new DateTimeImmutable())->add($accessTokenTTL));
        $accessToken->setPrivateKey($this->privateKey);

        $maxGenerationAttempts = static::MAX_RANDOM_TOKEN_GENERATION_ATTEMPTS;

        while ($maxGenerationAttempts-- > 0) {
            $accessToken->setIdentifier($this->generateUniqueIdentifier());
            try {
                $this->accessTokenRepository->persistNewAccessToken($accessToken);

                return $accessToken;
            } catch (UniqueTokenIdentifierConstraintViolationException $e) {
                if ($maxGenerationAttempts === 0) {
                    throw $e;
                }
            }
        }

        throw new Exception('Access token generation failed');
    }

    public function getAccessTokenForCurrentUser(string $clientId): string
    {
        return $this->getAccessTokenFor($clientId,
            $this->currentUserRepository->getCurrentLoginName(),
            $this->currentUserRepository->getCurrentOrganizationId()
        );
    }


        /**
     * Generate a new unique identifier.
     *
     * @param int $length
     *
     * @throws OAuthServerException
     *
     * @return string
     */
    protected function generateUniqueIdentifier(int $length = 40): string
    {
        try {
            return \bin2hex(\random_bytes($length));
            // @codeCoverageIgnoreStart
        } catch (TypeError $e) {
            throw OAuthServerException::serverError('An unexpected error has occurred', $e);
        } catch (Error $e) {
            throw OAuthServerException::serverError('An unexpected error has occurred', $e);
        } catch (Exception $e) {
            // If you get this message, the CSPRNG failed hard.
            throw OAuthServerException::serverError('Could not generate a random string', $e);
        }
        // @codeCoverageIgnoreEnd
    }

    protected function getAccessTokenTTl(): DateInterval
    {
        return new DateInterval($this->oauth2Config['grants']['password']['token_valid'] ?? 'PT1H');
    }

    protected function getClient(string $clientId): ClientEntityInterface
    {
        /**
         * @var ClientEntityInterface
         */
        return $this->clientRepository->getClientEntity($clientId);
    }

    protected function getScopes(): array
    {
        return [
            $this->scopeRepository->getScopeEntityByIdentifier('all'),
        ];
    }

    protected function getUserIdentifier(string $loginName, int $organizationId): string
    {
        return $loginName . \Gems\OAuth2\Entity\User::ID_SEPARATOR . $organizationId;
    }
}