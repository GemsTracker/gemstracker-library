<?php

/**
 *
 * @package    Gems
 * @subpackage User\Mask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Mask;

/**
 *
 * @package    Gems
 * @subpackage User\Mask
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Dec 25, 2016 5:49:13 PM
 */
class NameMasker extends AnyMasker
{
    /**
     *
     * @param string $type Current field data type
     * @return callable Function to perform masking
     */
    public function getMaskFunction($type)
    {
        switch ($type) {
            case 'double':
                return [$this, 'doubleMaskValue'];

            default:
                return parent::getMaskFunction($type);
        }
    }


    /**
     * Mask the value with a short mask
     *
     * @return string
     */
    public function doubleMaskValue()
    {
        return '**** ******';
    }
}
