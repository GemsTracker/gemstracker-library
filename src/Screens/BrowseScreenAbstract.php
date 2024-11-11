<?php

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:07:33 PM
 */
abstract class BrowseScreenAbstract implements BrowseScreenInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
    )
    {}

    /**
     *
     * @return array Added before all other parameters
     */
    public function getAutofilterParameters(): array
    {
        return [
            'columns'    => null,
            'useColumns' => false,
            ];
    }

    /**
     *
     * @return array|bool Array Of snippets or false to use original
     */
    public function getAutofilterSnippets(): array|bool
    {
        return false;
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    // public function getScreenLabel();

    /**
     *
     * @return array|bool Array Of snippets or false to use original
     */
    public function getStartSnippets(): array|bool
    {
        return ['Generic\\ContentTitleSnippet', 'Respondent\\RespondentSearchSnippet'];
    }

    /**
     *
     * @return array|bool Array Of snippets or false to use original
     */
    public function getStopSnippets(): array|bool
    {
        return false;
    }

    /**
     *
     * @return array Array Of snippets or false to use original
     */
    public function getStartStopParameters(): array
    {
        return [];
    }
}
