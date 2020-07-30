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

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Redirect
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class RespondentShowCreatePage extends RespondentShowPage
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;
    
    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getLabel()
    {
        return $this->_('Respondent show or create a new respondent');
    }

    /**
     * @return array redirect route
     */
    public function getRedirectRoute(\Gems_User_User $embeddedUser, \Gems_User_User $deferredUser, $patientId, $organizations)
    {
        $orgId = $deferredUser->getCurrentOrganizationId();
        
        $respondent = $this->loader->getRespondent($patientId, $orgId);
        
        if ($respondent->exists) {
            return parent::getRedirectRoute($embeddedUser, $deferredUser, $patientId, $organizations);
        }

        $staticSession = \GemsEscort::getInstance()->getStaticSession();
        $staticSession->previousRequestParameters = [
            'gr2o_patient_nr' => $patientId,
            'gr2o_id_organization' => $orgId,
            ];
        $staticSession->previousRequestMode = "POST";
        $staticSession->previousRequestMessage = sprintf(
            $this->_('Respondent %s does not yet exist, please enter the respondent data now!'),
            $patientId
        );
            
            
            
        return [
            $this->request->getControllerKey()  => 'respondent',
            $this->request->getActionKey()      => 'create',
        ];
    }
}