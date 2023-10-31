<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Roel van Meer <roel.van.meer@peercode.nl>
 * @copyright  Copyright (c) 2023 Equipe Zorgbedrijven B.V.
 * @license    New BSD License
 */

namespace Gems\User;

use Gems\Db\ResultFetcher;
use Zalt\Base\TranslatorInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2023 Equipe Zorgbedrijven B.V.
 * @license    New BSD License
 * @since      Class available since version 2.0
 */
class PasswordHistoryChecker
{
    public const DEFAULT_PASSWORD_HISTORY_LENGTH = 5;

    protected array $errors = [];

    protected ?User $user = null;

    protected int $historyLength = self::DEFAULT_PASSWORD_HISTORY_LENGTH;

    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
        protected readonly array $config,
        protected readonly TranslatorInterface $translator,
    ) {
    }

    protected function _addError(string $errorMsg): void
    {
        $this->errors[] = $errorMsg;
    }

    /**
     * Check for password reuse.
     *
     * @param User $user
     * @param string|null $password The password to check.
     * @return string[]|null Array of strings containing warning messages
     */
    public function reportPasswordReuse(\Gems\User\User $user, string|null $password): array|null
    {
        if (!$user->canSetPassword()) {
            return null;
        }

        $this->user = $user;
        $this->errors = [];

        $this->getHistoryLength();

        $previousHashes = $this->getPasswordHistory();
        if (is_null($previousHashes)) {
            return null;
        }

        foreach ($previousHashes as $previousHash) {
            if (password_verify($password, $previousHash)) {
                $this->_addError(sprintf($this->translator->trans('should not be identical to any of the previous %d passwords'), $this->historyLength));
                break;
            }
        }

        return $this->errors;
    }

    /**
     * Get the password history length from the config.
     */
    private function getHistoryLength(): void
    {
        $historyLength = $this->user->getPasswordHistoryLength();

        if (!is_null($historyLength)) {
            $this->historyLength = $historyLength;
        }
    }

    /**
     * Get the list of previous password hashes.
     *
     * @return string[]|null Array of password hashes.
     */
    private function getPasswordHistory(): array|null
    {
        $select = $this->resultFetcher->getSelect('gems__user_password_history');
        $select->columns(['guph_password'])
            ->where(['guph_id_user' => $this->user->getUserLoginId()])
            ->order('guph_set_time DESC')
            ->limit($this->historyLength);

        return $this->resultFetcher->fetchCol($select);
    }
}
