<?php


/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * A fieldmap object adds LS source code knowledge and interpretation to the database data
 * about a survey. This enables the code to work with the survey object.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Source_SurveyNotFoundException extends \Gems_Exception
{
    /**
     *
     * @param String $msg The message
     * @param int $code the HttpResponseCode for this exception
     * @param \Exception $previous
     * @param string $info Optional extra information on the exception
     */
    public function __construct($msg = '', $code = 200, \Exception $previous = null, $info = null)
    {
        parent::__construct($msg, $code, $previous);

        if ($info) {
            $this->setInfo($info);
        }
    }
}
