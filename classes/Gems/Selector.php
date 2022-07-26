<?php

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

/**
 * Loads "selector tables" i.e. table whose purpose it is to have
 * the user click a cell to select a value.
 *
 * @package    Gems
 * @subpackage Selector
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Selector extends \Gems\Loader\TargetLoaderAbstract
{
    /**
     * Allows sub classes of \Gems\Loader\LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Selector';

    /**
     * Load project specific model or general \Gems model otherwise
     *
     * @return \Gems\Selector\TokenDateSelector
     */
    public function getTokenDateSelector()
    {
        return $this->_loadClass('TokenDateSelector', true);
    }
}
