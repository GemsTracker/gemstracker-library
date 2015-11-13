<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
