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

use Gems\Repository\RespondentRepository;
use Gems\User\Embed\DeferredRouteHelper;
use Gems\User\Embed\RedirectAbstract;
use Gems\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;

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
    public function __construct(
        TranslatorInterface $translator,
        protected readonly RespondentRepository $respondentRepository,
    )
    {
        parent::__construct($translator);
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getLabel(): string
    {
        return $this->translator->_('Respondent show page');
    }

    public function getRedirectUrl(
        ServerRequestInterface $request,
        DeferredRouteHelper $routeHelper,
        User $embeddedUser,
        User $deferredUser,
        string $patientId,
        array $organizations,
    ): RedirectResponse|string|null
    {
        $orgId = $deferredUser->getCurrentOrganizationId();
        $patient = $this->respondentRepository->getPatient($patientId, $orgId);

        if ($patient === null) {
            throw new \Gems\Exception($this->translator->trans('Requested patient nr not found in organization'));
        }

        return $routeHelper->getRouteUrl('respondent.show', [
            MetaModelInterface::REQUEST_ID1 => $patientId,
            MetaModelInterface::REQUEST_ID2 => $orgId,
        ], [], $deferredUser->getRole());
    }
}
