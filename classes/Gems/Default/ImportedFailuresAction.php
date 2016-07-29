<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Default_ImportedFailuresAction extends \Gems_Default_FileActionAbstract
{
    /**
     * Should the action look recursively through the files
     *
     * @var boolean
     */
    public $recursive = true;

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Files failed at import');
    }

    /**
     * Return the path of the directory
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return string
     */
    public function getPath($detailed, $action)
    {
        return $this->loader->getImportLoader()->getFailureDirectory();
    }
}
