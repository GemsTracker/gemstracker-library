<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed;

use Gems\User\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 16:01:42
 */
interface RedirectInterface extends HelperInterface
{
    /**
     * @return string|null redirect route
     */
    public function getRedirectUrl(
        ServerRequestInterface $request,
        DeferredRouteHelper $routeHelper,
        User $embeddedUser,
        User $deferredUser,
        string $patientId,
        array $organizations,
    ): ?string;
}