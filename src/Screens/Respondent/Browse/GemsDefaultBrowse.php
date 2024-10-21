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
 * @since      Class available since version 1.8.2 Jan 20, 2017 3:52:09 PM
 */
class GemsDefaultBrowse extends BrowseScreenAbstract
{
    /**
     * @inheritDoc
     */
    public function getAutofilterSnippets(): array
    {
        return [
            'Gems\\Snippets\\Respondent\\RespondentTableSnippet',
        ];
    }

    /**
     *
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('(default Gems table display)');
    }

    /**
     *
     * @inheritDoc
     */
    public function getStartSnippets(): array
    {
        return [
            'Gems\\Snippets\\Generic\\ContentTitleSnippet',
            'Gems\\Snippets\\Respondent\\RespondentSearchSnippet',
        ];
    }

    /**
     *
     * @inheritDoc
     */
    public function getStopSnippets(): array
    {
        return [];
    }
}
