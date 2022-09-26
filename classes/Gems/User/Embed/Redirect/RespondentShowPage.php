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
use Mezzio\Helper\UrlHelper;

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
    public function getLabel()
    {
        return $this->_('Respondent show page');
    }

    /**
     * @return array redirect route
     */
    public function getRedirectRoute(\Gems\User\User $embeddedUser, \Gems\User\User $deferredUser, $patientId, $organizations)
    {
        $orgId = $deferredUser->getCurrentOrganizationId();
        
        $deferredUser->setSessionPatientNr($patientId, $orgId);

        return [
            $this->request->getControllerKey()  => 'respondent',
            $this->request->getActionKey()      => 'show',
            \MUtil\Model::REQUEST_ID1           => $patientId,
            \MUtil\Model::REQUEST_ID2           => $orgId,
        ];
    }

    public function getRedirectUrl(
        UrlHelper $urlHelper,
        \Gems\User\User $embeddedUser,
        \Gems\User\User $deferredUser,
        $patientId,
        $organizations,
    ): string {
        $orgId = $deferredUser->getCurrentOrganizationId();

        return $urlHelper->generate('respondent.show', [
            \MUtil\Model::REQUEST_ID1 => $patientId,
            \MUtil\Model::REQUEST_ID2 => $orgId,
        ]);
    }
}
