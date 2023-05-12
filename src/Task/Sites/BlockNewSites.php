<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Sites
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Task\Sites;

/**
 *
 * @package    Gems
 * @subpackage Task\Sites
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class BlockNewSites extends \MUtil\Task\TaskAbstract
{
    /**
     * @var \Gems\Util
     */
    protected $util;
    
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $lock = $this->util->getSites()->getSiteLock();
        if (! $lock->isLocked()) {
            $lock->lock();
        }

        $this->getBatch()->addMessage(sprintf($this->_('Automatic new site registration has been blocked since %s.'), $lock->getLockTime()));
    }
}