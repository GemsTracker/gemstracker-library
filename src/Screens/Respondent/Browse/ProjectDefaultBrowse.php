<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Browse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Browse;

use Gems\Screens\BrowseScreenAbstract;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Browse
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 20, 2017 3:49:35 PM
 */
class ProjectDefaultBrowse extends BrowseScreenAbstract
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getAutofilterParameters()
    {
        return [
            'useColumns' => true,
            ];
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('(default project specific table display)');
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getStartSnippets()
    {
        return false;
    }
}
