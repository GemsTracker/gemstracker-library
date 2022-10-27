<?php

namespace Gems\AuthNew\Adapter;

use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class GemsTrackerAuthentication implements AuthenticationAdapterInterface
{
    private function __construct(
        private readonly Adapter $db,
        private ?User $user,
        private readonly string $password,
    ) {
        if ($this->user->getUserDefinitionClass() !== UserLoader::USER_STAFF) {
            $this->user = null;
        }
    }

    public static function fromDetails(
        Adapter $db,
        UserLoader $userLoader,
        int $organizationId,
        string $username,
        string $password,
    ): self {
        $user = $userLoader->getUser(
            $username,
            $organizationId,
        );

        return new self($db, $user, $password);
    }

    public static function fromUser(
        Adapter $db,
        User $user,
        string $password
    ): self {
        return new self($db, $user, $password);
    }

    private function makeResult(int $code, array $messages = []): AuthenticationResult
    {
        $identity = null;
        if ($this->user) {
            $identity = new GemsTrackerIdentity($this->user->getLoginName(), $this->user->getCurrentOrganizationId());
        }

        return new GemsTrackerAuthenticationResult($code, $identity, $messages, $this->user);
    }

    public function authenticate(): AuthenticationResult
    {
        if ($this->user === null) {
            return $this->makeResult(AuthenticationResult::FAILURE);
        }

        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns(['gup_password'])
            ->from('gems__user_passwords')
            ->where(['gup_id_user' => $this->user->getUserLoginId()]);

        $resultSet = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$resultSet->valid()) {
            return $this->makeResult(AuthenticationResult::FAILURE);
        }

        $row = $resultSet->current();

        $passwordHash = $row['gup_password'];

        if (!password_verify($this->password, $passwordHash)) {
            return $this->makeResult(AuthenticationResult::FAILURE);
        }

        return $this->makeResult(AuthenticationResult::SUCCESS);
    }
}
