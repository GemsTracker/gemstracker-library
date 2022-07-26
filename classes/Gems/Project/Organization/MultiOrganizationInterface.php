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
 * in only multiple organization.
 *
 * Use this interface only when the respondents are stored
 * in multiple organizations. Multiple organizations can also
 * used for staff in
 * \Gems\Project\Organization\SingleOrganizationInterface
 * projects.
 *
 * @see \Gems\Project\Organization\SingleOrganizationInterface
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 * @deprecated since 1.7.1: is default setup
 */
interface MultiOrganizationInterface
{
}
