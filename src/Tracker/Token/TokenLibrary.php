<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Token;

use Gems\Db\ResultFetcher;
use Gems\Exception\Coding;
use Gems\Tracker;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;

/**
 * Utility functions for token string functions
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class TokenLibrary
{
    protected bool $tokenCaseSensitive;

    protected string $tokenChars;

    protected readonly array $tokenConfig;
    protected string $tokenDisplayFormat;
    protected string $tokenFormat;
    protected string|null $tokenFrom;

    /**
     * The number of days a used token is valid to answer other tokens
     *
     * -1 not at all, this breaks the token-loop
     * 0 default only the same day
     * 1 yesterday's tokens can still be used
     * etc.
     *
     * @var int
     */
    protected int $tokenReuse = 0;
    protected string|null $tokenTo;

    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
        array $config,

    )
    {

        $this->tokenConfig = $config['tokens'] ?? [];
        $this->initSettings();
    }

    public function initSettings(): void
    {
        if (isset($this->tokenConfig['chars'])) {
            $this->tokenChars = $this->tokenConfig['chars'];
        } else {
            throw new Coding('Required config setting "tokens.chars" is missing.');
        }

        if (isset($this->tokenConfig['format'])) {
            $this->tokenFormat = $this->tokenConfig['format'];
            $this->tokenDisplayFormat = str_replace("\t", '\\', str_replace(array('\\\\', '\\'), array("\t", ''), $this->tokenFormat));
        } else {
            throw new Coding('Required config setting "tokens.format" is missing.');
        }

        if (isset($this->tokenConfig['from'])) {
            $this->tokenFrom = $this->tokenConfig['from'];

            if (isset($this->tokenConfig['to'])) {
                $this->tokenTo = $this->tokenConfig['to'];
            } else {
                $this->tokenTo = null;
            }
            if (strlen($this->tokenFrom) != strlen($this->tokenTo)) {
                throw new Coding('config setting "token.from" does not have the same length as argument "token.to".');
            }

        } else {
            $this->tokenFrom = null;
            $this->tokenTo = null;
        }

        if (isset($this->tokenConfig['case'])) {
            $this->tokenCaseSensitive = (bool)$this->tokenConfig['case'];
        } else {
            $this->tokenCaseSensitive = ! ($this->tokenChars === strtolower($this->tokenChars));
        }

        if (isset($this->tokenConfig['reuse'])) {
            $this->tokenReuse = intval($this->tokenConfig['reuse']);
        }
    }

    /**
     * Creates a new token with a new random token ID
     *
     * @param array $tokenData
     * @param int $userId ID of the user who takes the action (for logging)
     * @return string The new token ID
     */
    public function createToken(array $tokenData, int $userId): string
    {
        $current = new Expression('CURRENT_TIMESTAMP');

        $tokenData['gto_changed']    = $current;
        $tokenData['gto_changed_by'] = $userId;
        $tokenData['gto_created']    = $current;
        $tokenData['gto_created_by'] = $userId;

        // Wait till the last nanosecond with creating the token id
        $tokenData['gto_id_token']   = $this->createTokenId();

        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($tokenData, true) . "\n", FILE_APPEND);
        $table = new TableGateway('gems__tokens', $this->resultFetcher->getAdapter());
        $table->insert($tokenData);

        if (Tracker::$verbose) {
            dump($tokenData, 'Created token: ' . $tokenData['gto_id_token']);
        }

        return $tokenData['gto_id_token'];
    }

    /**
     * Generates a random token and checks for uniqueness
     *
     * @return string A non-existing token
     */
    protected function createTokenId(): string
    {
        $max = strlen($this->tokenChars) - 1;
        $len = strlen($this->tokenFormat);

        do {
            $out = '';
            for ($i = 0; $i < $len; $i++) {
                if ('\\' == $this->tokenFormat[$i]) {
                    $i++;
                    $out .= $this->tokenFormat[$i];
                } else {
                    $out .= $this->tokenChars[mt_rand(0, $max)];
                }
            }

        } while ($this->resultFetcher->fetchOne('SELECT gto_id_token FROM gems__tokens WHERE gto_id_token = ?', [$out]));

        return $out;
    }

    /**
     * Removes all unacceptable characters from the input token and inserts any fixed characters left out
     *
     * @param string|null $token
     * @return string|null Reformatted token
     */
    public function filter(string|null $token): string|null
    {
        if (null === $token) {
            return null;
        }

        // Apply replacements
        if ($this->tokenFrom) {
            $token = strtr($token, $this->tokenFrom, $this->tokenTo);
        }
        
        // If not case-sensitive, convert to lowercase
        if (! $this->tokenCaseSensitive) {
            $token = strtolower($token);
        }
        
        // Filter out invalid chars
        $tokenLength   = strlen($token);
        $filteredToken = '';
        
        for ($tokenPos = 0; ($tokenPos < $tokenLength); $tokenPos++) {
            if (str_contains($this->tokenChars, $token[$tokenPos])) {
                $filteredToken .= $token[$tokenPos];
            }
        }

        // Now check against the format for fixed chars
        $formatLength = strlen($this->tokenFormat);
        $tokenLength  = strlen($filteredToken);
        $tokenPos     = 0;
        $resultToken  = '';
        
        for ($formatPos = 0; ($formatPos < $formatLength) && ($tokenPos < $tokenLength); $formatPos++) {
            if ('\\' == $this->tokenFormat[$formatPos]) {
                $formatPos++;
                $resultToken .= $this->tokenFormat[$formatPos];

            } else {                
                $resultToken .= $filteredToken[$tokenPos];
                $tokenPos++;
            }
        }
        
        // Separate extra chars with a space and a question-mark
        if ($tokenPos < $tokenLength) {
            $resultToken .= ' ?' . substr($filteredToken, $tokenPos);
        }

        return $resultToken;
    }

    /**
     *
     * @return bool True if case-sensitive
     */
    public function getCaseSensitive(): bool
    {
        return $this->tokenCaseSensitive;
    }

    /**
     * Display format describing token format
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->tokenDisplayFormat;
    }

    /**
     * The characters that should not occur in a token
     *
     * @see getFrom
     *
     * @return string|null
     */
    public function getFrom(): string|null
    {
        return $this->tokenFrom;
    }

    /**
     *
     * @return int The length a token is allowed to have.
     */
    public function getLength(): int
    {
        return strlen($this->tokenDisplayFormat);
    }

    /**
     * The number of days after completion a token can be used to look up other not completed tokens
     *
     * @return int
     */
    public function getReuse(): int
    {
        return $this->tokenReuse;
    }

    /**
     * The characters that replace characters that should not occur in a token
     *
     * @see getFrom()
     *
     * @return string|null
     */
    public function getTo(): string|null
    {
        return $this->tokenTo;
    }

    /**
     * True if after completion a token can be used to look up other not completed tokens
     *
     * @return bool
     */
    public function hasReuse(): bool
    {
        return $this->tokenReuse >= 0;
    }
}
