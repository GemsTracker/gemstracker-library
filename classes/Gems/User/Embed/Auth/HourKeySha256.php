<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed\Auth;

use Gems\User\Embed\EmbeddedAuthAbstract;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 17:16:20
 */
class HourKeySha256 extends EmbeddedAuthAbstract
{
    /**
     * @var bool Show debug code
     */
    protected $debug = false;

    /**
     * Default key to use when no two factor key was set
     *
     * @var string
     */
    protected $defaultKey = 'test';

    /**
     *
     * @var string Algorithm for the PHP hash() function, E.G. sha256
     */
    protected $encryptionAlgorithm = 'sha256';

    /**
     *
     * @var boolean When true, apply base64 to encryption output
     */
    protected $encryptionBase64 = true;

    /**
     *
     * @var boolean True when hash() encryption should return raw output
     */
    protected $encryptionRaw = true;

    /**
     * @var bool Is the supplied hash uppercase? in PHP hash lowercase is always supplied
     */
    protected $encryptionUppercase = false;

    /**
     * Format for date part of key function
     *
     * @var string
     */
    protected $keyTimeFormat = 'YmdH';

    /**
     * The number of time periods on either side of the current that is allowed
     *
     * @var int
     */
    protected $keyTimeValidRange = 1;

    /**
     * Authenticate embedded user
     *
     * @param \Gems_User_User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(\Gems_User_User $user, $secretKey)
    {
        if ($this->encryptionUppercase) {
            $secretKey = strtolower($secretKey);
        }

        return in_array($secretKey, $this->getValidKeys($user));
    }

    /**
     *
     * @param string $key The input type
     * @return string The encrypted result that should be retrieved
     */
    protected function encryptKey($key)
    {
        if ($this->encryptionAlgorithm) {
            $input = hash($this->encryptionAlgorithm, $key, $this->encryptionRaw);
        } else {
            $input = $key;
        }

        if ($this->encryptionBase64) {
            return base64_encode($input);
        }

        return $input;
    }

    /**
     *
     * @param \Gems_User_User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(\Gems_User_User $user)
    {
        $keys = $this->getValidKeys($user);

        if (isset($keys[0])) {
            return $keys[0];
        }
        return \end($keys) ?: 'key';
    }

    /**
     * Return the authentication string for the user
     *
     * @param \Gems_User_User $embeddedUser
     * @return string Preferably containing %s
     */
    protected function getKeysStart(\Gems_User_User $embeddedUser)
    {
        $key = $embeddedUser->getSecretKey() ?: $this->defaultKey;

        if (! \MUtil_String::contains($key, '%s')) {
            $key .= '%s';
        }

        return $key;
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getLabel()
    {
        return sprintf($this->_('Hour valid key with %s'), $this->encryptionAlgorithm);
    }

    /**
     * Generate the \DateInterval constructor
     *
     * @param int $i The "start" interval
     * @return string
     * @throws \Gems_Exception_Coding
     */
    protected function getTimePeriodString($i = 1)
    {
        $timeChar = substr($this->keyTimeFormat, -1);

        switch ($timeChar) {
            case 'o':
            case 'y':
            case 'Y':
                return "P{$i}Y";

            case 'm':
            case 'n':
                return "P{$i}M";

            case 'd':
            case 'j':
                return "P{$i}D";

            case 'H':
            case 'h':
                return "PT{$i}H";

            case 'i':
                return "PT{$i}M";

        }

        throw new \Gems_Exception_Coding("Invalid last keyTimeFormat character '$timeChar' set.");
    }

    /**
     * Return an array of valid key values for this user
     *
     * @param \Gems_User_User $embeddedUser
     * @return array
     */
    public function getValidKeys(\Gems_User_User $embeddedUser)
    {
        $keyStart = $this->getKeysStart($embeddedUser);
        // \MUtil_Echo::track($keyStart);

        if (! \MUtil_String::contains($keyStart, '%s')) {
            return [$keyStart];
        }

        $current = new \DateTime();
        $current->sub(new \DateInterval($this->getTimePeriodString($this->keyTimeValidRange)));
        $addDate = new \DateInterval($this->getTimePeriodString(1));
        $keys    = [];

        for ($i = -$this->keyTimeValidRange; $i <= $this->keyTimeValidRange; $i++) {
            $keys[$i] = $this->encryptKey(sprintf($keyStart, $current->format($this->keyTimeFormat)));
            $current->add($addDate);
        }

        if ($this->debug) {
            \MUtil_Echo::track($keys);
        }
        // \MUtil_Echo::track(hash_algos());

        return $keys;
    }
}