<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * A simple file based locking mechanism.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class Gems_Util_LockFile
{
    /**
     *
     * @var string
     */
    protected $lockFileName;

    /**
     *
     * @param string $lockFileName The name of the lockfile
     */
    public function __construct($lockFileName)
    {
        $this->lockFileName = $lockFileName;
    }

    /**
     * Last time the lock was set.
     *
     * @return \MUtil_Date or null when not locked.
     */
    public function getLockTime()
    {
        if ($this->isLocked()) {
            return new \MUtil_Date(filectime($this->lockFileName));
        }
    }

    /**
     * Returns true if this lock exists.
     *
     * @return boolean
     */
    public function isLocked()
    {
        return file_exists($this->lockFileName);
    }

    /**
     * Lock this file and updates lock time.
     *
     * @return \Gems_Util_LockFile (continuation pattern)
     */
    public function lock()
    {
        touch($this->lockFileName);
        return $this;
    }

    /**
     * Switches from lock to unlocked state.
     *
     * @return \Gems_Util_LockFile (continuation pattern)
     */
    public function reverse()
    {
        if ($this->isLocked()) {
            $this->unlock();
        } else {
            $this->lock();
        }
        return $this;
    }

    /**
     * Unlocks this lock file  by deleting it
     *
     * @return \Gems_Util_LockFile (continuation pattern)
     */
    public function unlock()
    {
        if ($this->isLocked()) {
            unlink($this->lockFileName);
        }
        return $this;
    }
}
