<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Redirect
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed\Redirect;

use Gems\User\Embed\RedirectAbstract;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Redirect
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 15-Apr-2020 11:49:00
 */
class RespondentSearchPage extends RedirectAbstract
{
    /**
     * @var string|null The name of the initial route page
     */
    protected ?string $routeName = 'respondent.index';

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getLabel(): string
    {
        return $this->translator->_('Respondent search page');
    }
}