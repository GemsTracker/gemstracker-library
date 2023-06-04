<?php

/**
 *
 * @package    Gems
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Project\Organization;

/**
 * Marker interface for Pulse Projects having respondents
 * in only one organization.
 *
 * Multiple organizations can be used to create staff,
 * but all respondents will be stored in this organization.
 *
 * @see \Gems\Project\Organization\MultiOrganizationInterface
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 * @deprecated since 1.7.1: no longer in use
 */
interface SingleOrganizationInterface
{
    public function getRespondentOrganization();
}
