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
use Gems\User\User;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Redirect
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class RespondentShowCreatePage extends RespondentShowPage
{

    public function __construct(
        TranslatorInterface $translator,
        protected RespondentRepository $respondentRepository,
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
        return $this->translator->_('Respondent show or create a new respondent');
    }

    public function getRedirectUrl(
        DeferredRouteHelper $routeHelper,
        User $embeddedUser,
        User $deferredUser,
        $patientId,
        $organizations,
    ): ?string {

        $orgId = $deferredUser->getCurrentOrganizationId();
        $respondent = $this->respondentRepository->getRespondent($patientId, $orgId);

        if ($respondent->exists) {
            return parent::getRedirectUrl($routeHelper, $embeddedUser, $deferredUser, $patientId, $organizations);
        }

        // Add Message ?
        /*sprintf(
            $this->_('Respondent %s does not yet exist, please enter the respondent data now!'),
            $patientId
        );*/

        return $routeHelper->getRouteUrl('respondent.create');
    }
}