<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage AuthNew\Adapter
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\AuthNew\Adapter;

use Gems\Config\ConfigAccessor;
use Gems\Exception;
use Gems\User\User;
use Laminas\Authentication\Adapter\Ldap as LdapAdapter;

/**
 * @package    Gems
 * @subpackage AuthNew\Adapter
 * @since      Class available since version 1.0
 */
class LdapAuthentication implements AuthenticationAdapterInterface
{
    public function __construct(
        protected readonly ConfigAccessor $configAccessor,
        protected readonly User $user,
        protected readonly string $password)
    {
    }

    public static function fromUser(ConfigAccessor $configAccessor, User $user, string $password): LdapAuthentication
    {
        if ($user->getUserDefinitionClass() == 'LdapUser') {
            return new self($configAccessor, $user, $password);
        }

        throw new Exception('Trying to use non-Ldap user with Ldap');
    }

    private function makeResult(int $code, array $messages = []): AuthenticationResult
    {
        $identity = new GemsTrackerIdentity($this->user->getLoginName(), $this->user->getCurrentOrganizationId());

        return new GemsTrackerAuthenticationResult($code, $identity, $messages, $this->user);
    }

    public function authenticate(): AuthenticationResult
    {
        $adapter = new LdapAdapter();

        $servers = $this->configAccessor->getLdapServers();
        $result  = null;
        foreach ($servers as $server) {
            if (isset($server['requireCert']) && (0 == $server['requireCert'])) {
                \ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, 0);
            }
            unset($server['requireCert']);
            $adapter->setOptions([$server]);

            if (isset($server['accountDomainNameShort'])) {
                $userName = $server['accountDomainNameShort'] . '\\' . $this->user->getLoginName();
            } else {
                $userName = $this->user->getLoginName();
            }

            $adapter->setUsername($userName)
                ->setPassword($this->password);

            try {
                $result = $adapter->authenticate();
                if ($result->isValid()) {
                    return $this->makeResult(AuthenticationResult::SUCCESS);
                }
            } catch (\Exception $e) {
                // Server could not be reached
                error_log($e->getMessage());
            }
        }
        if ($result && !$result->isValid()) {
            return $this->makeResult($result->getCode(), $result->getMessages());
        }

        if (! $servers) {
            return $this->makeResult(AuthenticationResult::FAILURE, ['No LDAP servers were set']);
        }

        return $this->makeResult(AuthenticationResult::FAILURE, ['User or password incorrect']);
    }
}