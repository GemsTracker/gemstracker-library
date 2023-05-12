<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

use DateTimeImmutable;

/**
 * A simple file based locking mechanism.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class LockFile
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
     * @return ?DateTimeImmutable or null when not locked.
     */
    public function getLockTime(): ?DateTimeImmutable 
    {
        if ($this->isLocked()) {
            $date = new DateTimeImmutable();
            return $date->setTimestamp(filectime($this->lockFileName));
        }
        return null;
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
     * @return \Gems\Util\LockFile (continuation pattern)
     */
    public function lock()
    {
        touch($this->lockFileName);
        return $this;
    }

    /**
     * Switches from lock to unlocked state.
     *
     * @return \Gems\Util\LockFile (continuation pattern)
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
     * @return \Gems\Util\LockFile (continuation pattern)
     */
    public function unlock()
    {
        if ($this->isLocked()) {
            unlink($this->lockFileName);
        }
        return $this;
    }
}
