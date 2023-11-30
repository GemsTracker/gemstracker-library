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

use Gems\User\Embed\DeferredRouteHelper;
use Gems\User\Embed\RedirectAbstract;
use Gems\User\User;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Redirect
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 15-Apr-2020 11:53:01
 */
class StaffAccountPage extends RedirectAbstract
{
    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getLabel(): string
    {
        return $this->translator->_('Staff account setup page');
    }

    public function getRedirectUrl(
        DeferredRouteHelper $routeHelper,
        User $embeddedUser,
        User $deferredUser,
        string $patientId,
        array $organizations,
    ): ?string {
        return $routeHelper->getRouteUrl('option.edit');
    }
}