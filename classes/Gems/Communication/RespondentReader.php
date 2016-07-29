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
 * Reads respondents from a data source
 *
 * @package    Gems
 * @subpackage Communication
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
interface Gems_Communication_RespondentReader
{
    /**
     * Read a single respondent
     * @param  int $id
     * @return \Gems_Communication_RespondentContainer
     * @throws \Gems_Communication_Exception
     */
    public function getRespondentById($id);
}