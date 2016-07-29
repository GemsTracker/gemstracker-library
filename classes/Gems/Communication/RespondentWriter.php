<?php

/**
 * @package    Gems
 * @subpackage Communication
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Writes respondents to a data source
 *
 * @package    Gems
 * @subpackage Communication
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
interface Gems_Communication_RespondentWriter
{
    /**
     * Writes the respondent, creating a new one or updating the existing record
     *
     * @param  \Gems_Communication_RespondentContainer $respondent
     * @param  int $userId
     * @return boolean True if a new respondent was added, false if one was updated
     * @throws \Gems_Communication_Exception
     */
    public function writeRespondent(\Gems_Communication_RespondentContainer $respondent, &$userId);
}