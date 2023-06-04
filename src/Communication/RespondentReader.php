<?php

/**
 * @package    Gems
 * @subpackage Communication
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Communication;

/**
 * Reads respondents from a data source
 *
 * @package    Gems
 * @subpackage Communication
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
interface RespondentReader
{
    /**
     * Read a single respondent
     * @param  int $id
     * @return \Gems\Communication\RespondentContainer
     * @throws \Gems\Communication\Exception
     */
    public function getRespondentById($id);
}