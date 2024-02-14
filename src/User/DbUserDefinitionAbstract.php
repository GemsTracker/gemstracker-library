<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User;

use Gems\Db\ResultFetcher;
use Gems\Model\MetaModelLoader;
use Gems\Project\ProjectSettings;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Db\Exception\RuntimeException;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;

/**
 * A standard, database stored user as of version 1.5.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class DbUserDefinitionAbstract extends UserDefinitionAbstract
{
    /**
     * If passwords also need to be checked with the old hash method
     * @var bool
     */
    public bool $checkOldHashes = true;

    /**
     *
     * @var string The hash algorithm used for password_hash
     */
    protected string $hashAlgorithm = PASSWORD_DEFAULT;

    /**
     * The time period in hours a reset key is valid for this definition.
     *
     * @var int
     */
    protected int $hoursResetKeyIsValid = 24;

    public function __construct(
        protected ResultFetcher $resultFetcher,
        protected MetaModelLoader $metaModelLoader,
        protected ProjectSettings $projectSettings,
    )
    {
    }

    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition when no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param User|null $user Optional, the user whose password might change
     * @return bool
     */
    public function canResetPassword(User|null $user = null): bool
    {
        if ($user) {
            // Depends on the user.
            if ($user->hasEmailAddress() && $user->canSetPassword()) {
                $email = $user->getEmailAddress();
                return !empty($email);
            }
        }
        return true;
    }

    /**
     * Return true if the two factor can be set.
     *
     * @return bool
     */
    public function canSaveTwoFactorKey(): bool
    {
        return true;
    }

   /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition when no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param User|null $user Optional, the user whose password might change
     * @return bool
     */
    public function canSetPassword(User|null $user = null): bool
    {
        return true;
    }

    /**
     * Checks if the current users hashed password uses the current hash algorithm.
     * If not it rehashes and saves the current password
     * @param  User  $user     Current logged in user
     * @param  string          $password Raw password
     * @return bool          password has been rehashed
     */
    public function checkRehash(User $user, string $password): bool
    {
        $model = $this->metaModelLoader->createTableModel('gems__user_passwords');
        $row   = $model->loadFirst(['gup_id_user' => $user->getUserLoginId()]);

        if ($row && password_needs_rehash($row['gup_password'], $this->getHashAlgorithm(), $this->getHashOptions())) {
            $data['gup_id_user']  = $user->getUserLoginId();
            $data['gup_password'] = $this->hashPassword($password);

            $this->metaModelLoader->setChangeFields($model->getMetaModel(), 'gup');
            $model->save($data);

            return true;
        }

        return false;
    }

    public function createResetKey(): string
    {
        return $this->projectSettings->getValueHash(random_bytes(64), 'sha256');
    }

    /**
     * Returns an initialized \Laminas\Authentication\Adapter\AdapterInterface
     *
     * @param User $user
     * @param string $password
     * @return AdapterInterface
     */
    public function getAuthAdapter(User $user, string $password): AdapterInterface
    {
        $credentialValidationCallback = $this->getCredentialValidationCallback();

        $adapter = new CallbackCheckAdapter($this->resultFetcher->getAdapter(), 'gems__user_passwords', 'gul_login', 'gup_password', $credentialValidationCallback);

        $select = $adapter->getDbSelect();
        $select->join('gems__user_logins', 'gup_id_user = gul_id_user', array())
               ->where('gul_can_login = 1')
               ->where(array('gul_id_organization ' => $user->getBaseOrganizationId()));

        $adapter->setIdentity($user->getLoginName())
                ->setCredential($password);

        return $adapter;
    }

    /**
     * get the credential validation callback function for the callback check adapter
     * @return callable Function
     */
    public function getCredentialValidationCallback(): callable
    {
        return function($dbCredential, $requestCredential) {
            if (password_verify($requestCredential, $dbCredential)) {
                return true;
            }

            return false;
        };
    }

    /**
     * Get the current password hash algorithm
     * Currently defaults to BCRYPT
     *
     * @return string
     */
    public function getHashAlgorithm(): string
    {
        return $this->hashAlgorithm;
    }

    /**
     * Get the current hash options
     * Default:
     * cost => 10  // higher numbers will make the hash slower but stronger.
     *
     * @return array Current password hash options
     */
    public function getHashOptions(): array
    {
        return [];
    }

    /**
     * Return a password reset key
     *
     * @param User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(User $user): string
    {
        $model = $this->metaModelLoader->createTableModel('gems__user_passwords');
        $this->metaModelLoader->setChangeFields($model->getMetaModel(), 'gup');

        $data['gup_id_user'] = $user->getUserLoginId();
        $filter = $data;

        if ((0 == ($this->hoursResetKeyIsValid % 24))) {
            $filter[] = 'ADDDATE(gup_reset_requested, ' . intval($this->hoursResetKeyIsValid / 24) . ') >= CURRENT_TIMESTAMP';
        } else {
            $filter[] = 'DATE_ADD(gup_reset_requested, INTERVAL ' . $this->hoursResetKeyIsValid . ' HOUR) >= CURRENT_TIMESTAMP';
        }
        $row = $model->loadFirst($filter);
        if ($row && $row['gup_reset_key']) {
            // Keep using the key.
            $data['gup_reset_key'] = $row['gup_reset_key'];
        } else {
            $data['gup_reset_key'] = $this->createResetKey();
        }
        $data['gup_reset_requested'] = 'CURRENT_TIMESTAMP';

        // Loop for case when hash is not unique
        while (true) {
            try {
                $model->save($data);

                return $data['gup_reset_key'];

            } catch (RuntimeException $zde) {
                $data['gup_reset_key'] = $this->createResetKey();
            }
        }
    }

    /**
     * Returns a user object, that may be empty if the user is unknown.
     *
     * @param string $loginName
     * @param int $organizationId
     * @return array Of data to fill the user with.
     */
    public function getUserData(string $loginName, int $organizationId): array
    {
        $select = $this->getUserSelect($loginName, $organizationId);
        $result = null;
        try {
            $result = $this->resultFetcher->fetchRow($select);

        } catch (RuntimeException $e) {

        }

        /*
         * Handle the case that we have a login record, but no matching userdata (ie. user is inactive)
         * if you want some kind of auto-register you should change this
         */
        if ($result == false) {
            $result = NoLoginDefinition::getNoLoginDataFor($loginName, $organization);
        }

        return $result;
    }

    /**
     * A select used by subclasses to add fields to the select.
     *
     * @param string $loginName
     * @param int $organizationId
     * @return Select
     */
    abstract protected function getUserSelect(string $loginName, int $organizationId): Select;

    /**
     * Allow overruling of password hashing.
     *
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, $this->getHashAlgorithm(), $this->getHashOptions());
    }

    /**
     * Return true if the user has a password.
     *
     * @param User $user The user to check
     * @return bool
     */
    public function hasPassword(User $user): bool
    {
        $sql = "SELECT CASE WHEN gup_password IS NULL THEN 0 ELSE 1 END FROM gems__user_passwords WHERE gup_id_user = ?";

        return (bool) $this->resultFetcher->fetchOne($sql, [$user->getUserLoginId()]);
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param User $user The user whose password to change
     * @param string|null $password
     * @return self (continuation pattern)
     */
    public function setPassword(User $user, string|null $password): self
    {
        $data['gup_id_user']         = $user->getUserLoginId();
        $data['gup_reset_key']       = null;
        $data['gup_reset_requested'] = null;
        $data['gup_reset_required']  = 0;
        if (null === $password) {
            // Passwords may be emptied.
            $data['gup_password'] = null;
        } else {
            $data['gup_password'] = $this->hashPassword($password);
        }
        $data['gup_last_pwd_change'] = new Expression('CURRENT_TIMESTAMP');

        $model = $this->metaModelLoader->createTableModel('gems__user_passwords');
        $this->metaModelLoader->setChangeFields($model->getMetaModel(), 'gup');

        $model->save($data);

        return $this;
    }

    /**
     * Update the password history.
     *
     * @param \Gems\User\User $user The user whose password history to change
     * @param string $password
     * @return self (continuation pattern)
     */
    public function updatePasswordHistory(User $user, string $password): self
    {
        $historyLength = $user->getPasswordHistoryLength();
        if (is_null($historyLength)) {
            $historyLength = PasswordHistoryChecker::DEFAULT_PASSWORD_HISTORY_LENGTH;
        }

        // First we clean up.
        $cleanupOffset = $historyLength > 0 ? $historyLength -1 : 0;
        $select = $this->resultFetcher->getSelect('gems__user_password_history');
        $select->columns(['guph_id'])
            ->where(['guph_id_user' => $user->getUserLoginId()])
            ->order('guph_set_time DESC')
            ->limit(10)
            ->offset($cleanupOffset);
        $removeIds = $this->resultFetcher->fetchCol($select);
        if ($removeIds) {
            $where = new Where();
            $where->in('guph_id', $removeIds);
            $this->resultFetcher->deleteFromTable('gems__user_password_history', $where);
        }

        // Nothing to add if we don't need to keep track of the password history.
        if ($historyLength == 0) {
            return $this;
        }

        // Add the password to the history.
        $data = [
            'guph_id_user' => $user->getUserLoginId(),
            'guph_password' => $this->hashPassword($password),
        ];

        $this->resultFetcher->insertIntoTable('gems__user_password_history', $data);

        return $this;
    }

    /**
     *
     * @param User $user The user whose key to set
     * @param string $newKey
     * @return self
     */
    public function setTwoFactorKey(User $user, string $newKey): self
    {
        $data['gul_id_user']        = $user->getUserLoginId();
        $data['gul_two_factor_key'] = $newKey;

        $model = $this->metaModelLoader->createTableModel('gems__user_logins');
        $this->metaModelLoader->setChangeFields($model->getMetaModel(), 'gul');

        $model->save($data);

        return $this;
    }

    /**
     *
     * @param User $user The user whose session key to set
     * @param string $newKey
     * @return $this
     */
    public function setSessionKey(User $user, string $newKey): static
    {
        $data['gul_id_user'] = $user->getUserLoginId();
        $data['gul_session_key'] = $newKey;
        $data['gul_changed_by'] = $user->getUserId();

        $model = $this->metaModelLoader->createTableModel('gems__user_logins');
        $this->metaModelLoader->setChangeFields($model->getMetaModel(), 'gul');

        $model->save($data);

        return $this;
    }
}
