<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\User\Embed;

/**
 * An interface for Auth objects that may change the parameters of the login 
 * 
 * @package    Gems
 * @subpackage User\Embed
 * @since      Class available since version 1.9.2
 */
interface UpdatingAuthInterface extends EmbeddedAuthInterface
{
    /**
     * @return array Returns the parameter data embedded in the url  
     */
    public function getEmbeddedParams(); 
}