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
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getLabel()
    {
        return $this->_('Respondent search page');
    }

    /**
     * @return array redirect route
     */
    public function getRedirectRoute(\Gems_User_User $embeddedUser, \Gems_User_User $deferredUser, $patientId, $organizations)
    {
        return [
            $this->request->getControllerKey()  => 'respondent',
            $this->request->getActionKey()      => 'index',
            \MUtil_Model::TEXT_FILTER           => $patientId,
            \MUtil_Model::REQUEST_ID2           => $deferredUser->getCurrentOrganizationId(),
        ];
    }
}