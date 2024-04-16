<?php

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

use Zalt\Model\Data\DataReaderInterface;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.3 Feb 15, 2018 12:35:15 PM
 */
interface ProcessModelInterface
{
    /**
     * Allow changes to the model
     *
     * @param DataReaderInterface $model
     * @return DataReaderInterface
     */
    public function processModel(DataReaderInterface $model);
}
