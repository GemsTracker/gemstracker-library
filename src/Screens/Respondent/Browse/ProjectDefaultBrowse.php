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
     * @inheritDoc
     */
    public function getAutofilterParameters(): array
    {
        return [
            'useColumns' => true,
            ];
    }

    /**
     *
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('(default project specific table display)');
    }

    /**
     *
     * @inheritDoc
     */
    public function getStartSnippets(): array|bool
    {
        return false;
    }
}
