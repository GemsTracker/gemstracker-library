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
use MUtil\Model;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Redirect
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 02-Apr-2020 19:45:02
 */
class RespondentShowPage extends RedirectAbstract
{
    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getLabel(): string
    {
        return $this->translator->_('Respondent show page');
    }

    public function getRedirectUrl(
        DeferredRouteHelper $routeHelper,
        User $embeddedUser,
        User $deferredUser,
        string $patientId,
        array $organizations,
    ): ?string {
        $orgId = $deferredUser->getCurrentOrganizationId();

        return $routeHelper->getRouteUrl('respondent.show', [
            Model::REQUEST_ID1 => $patientId,
            Model::REQUEST_ID2 => $orgId,
        ]);
    }
}
