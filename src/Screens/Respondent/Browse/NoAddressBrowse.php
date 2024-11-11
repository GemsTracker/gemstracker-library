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
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:15:48 PM
 */
class NoAddressBrowse extends BrowseScreenAbstract
{
    /**
     * @inheritDoc
     */
    public function getAutofilterSnippets(): array
    {
        return ['Respondent\\RespondentNoAddressTableSnippet'];
    }

    /**
     *
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('Browse without address');
    }
}
