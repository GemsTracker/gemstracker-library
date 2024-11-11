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

use Zalt\Html\HtmlInterface;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 17, 2017 5:07:17 PM
 */
interface ScreenInterface
{
    /**
     *
     * @return HtmlInterface|string Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getScreenLabel(): HtmlInterface|string;
}
