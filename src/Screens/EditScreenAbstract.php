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
abstract class EditScreenAbstract implements ConsentInterface
{

    public function __construct(
        protected readonly TranslatorInterface $translator,
    )
    {}

    /**
     *
     * @return array Of snippets or false to use original for consent editing
     */
    public function getConsentSnippets(): array|bool
    {
        return false;
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getConsentParameters(): array
    {
        return $this->getParameters();
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getCreateParameters(): array
    {
        return $this->getParameters();
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getEditParameters(): array
    {
        return $this->getParameters();
    }

    /**
     *
     * @return array Default added parameters
     */
    protected function getParameters(): array
    {
        return [];
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getSnippets(): array|bool
    {
        return false;
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    // public function getScreenLabel();
}
