<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Edit
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Edit;

use Gems\Screens\EditScreenAbstract;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Edit
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 20, 2017 3:52:09 PM
 */
class ProjectDefaultEdit extends EditScreenAbstract
{
    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('(default project respondent edit)');
    }
}
