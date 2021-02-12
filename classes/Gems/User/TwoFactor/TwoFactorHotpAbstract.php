<?php

namespace Gems\User\TwoFactor;

abstract class TwoFactorHotpAbstract extends TwoFactorTotpAbstract implements UserOtpInterface
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    protected $userId;

    protected $userOtpCount;

    protected $userOtpRequested;

    /**
     * @var int number of earlier codes that are still valid. 1 is only current
     */
    protected $_verifyDiscrepancy = 1;

    /**
     * Calculate the code, with given secret and point in time.
     *
     * @param string   $secret
     * @param int|null $timeSlice
     *
     * @return string
     */
    public function getCode($secret, $timeSlice = null)
    {
        if ($this->userOtpCount === null) {
            throw new \Gems_Exception('No otp count set');
        }
        $currentOtpCount = $this->userOtpCount;

        return parent::getCode($secret, $currentOtpCount);
    }

    public function getNewCode($secret)
    {
        if ($this->userOtpCount === null) {
            throw new \Gems_Exception('No otp count set');
        }
        $currentOtpCount = $this->userOtpCount;
        $newOtpCount = $currentOtpCount + 1;

        $this->saveNewCount($newOtpCount);

        return parent::getCode($secret, $newOtpCount);
    }

    protected function saveNewCount($count)
    {
        if ($this->userId === null) {
            throw new \Gems_Exception('No user ID set');
        }
        $now = new \MUtil_Date();

        $values = [
            'gul_otp_count' => $count,
            'gul_otp_requested' => \MUtil_Date::format($now, 'yyyy-MM-dd HH:mm:ss'),
        ];

        $this->db->update('gems__user_logins', $values, ['gul_id_user = ?' => $this->userId]);

        $this->userOtpCount = $count;
        $this->userOtpRequested = $now;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setUserOtpCount($count)
    {
        $this->userOtpCount = $count;
    }

    public function setUserOtpRequested(\MUtil_Date $requestedTime = null)
    {
        $this->userOtpRequested = $requestedTime;
    }

    /**
     * Check if the code is correct. This will accept codes starting from $discrepancy*30sec ago to $discrepancy*30sec from now.
     *
     * @param string   $secret
     * @param string   $code
     * @param int      $discrepancy      This is the allowed time drift in 30 second units (8 means 4 minutes before or after)
     * @param int|null $currentTimeSlice time slice if we want use other that time()
     *
     * @return bool
     */
    public function verifyCode($secret, $code, $discrepancy = 0, $currentTimeSlice = null)
    {
        if ($this->userOtpRequested === null) {
            throw new \Gems_Exception('No otp requested datetime set');
        }

        $currentOtpRequested = $this->userOtpRequested;

        $otpValidUntil = $currentOtpRequested->addSecond($this->_codeValidSeconds);

        if ($otpValidUntil->isEarlier(new \MUtil_Date)) {
            return false;
        }

        if ($this->userOtpCount === null) {
            throw new \Gems_Exception('No otp count set');
        }
        $currentOtpCount = $this->userOtpCount;

        return parent::verifyCode($secret, $code, 0, $currentOtpCount);
    }
}
