<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

/**
 *
 * @package    Gems
 * @subpackage Tracker\Model
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class LogFieldDataModel extends \Gems\Model\JoinModel
{
    /**
     * LogFieldDataModel constructor.
     */
    public function __construct()
    {
        parent::__construct('gems__log_respondent2track2field', 'gems__log_respondent2track2field', 'glrtf', true);
    }

}