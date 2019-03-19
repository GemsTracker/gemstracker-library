<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Subscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Subscribe;

use Gems\Screens\SubscribeScreenInterface;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Subscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 11:38:50
 */
class EmailOnlySubscribe extends \MUtil_Translate_TranslateableAbstract implements SubscribeScreenInterface
{
    /**
     * Use currentUser since currentOrganization may have changed by now
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @return string
     */
    public function generatePatientNumber()
    {
        $org    = $this->currentUser->getCurrentOrganization();
        $orgId  = $org->getId();
        $prefix = 'subscr';

        if ($org->getCode()) {
            $codes = explode(' ' , $org->getCode());
            $code  = reset($codes); // Start code with space not to use this option
            if ($code) {
                $prefix = $code;
            }
        }

        $sql  = "SELECT gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?";
        do {
            $number = $prefix . random_int(1000000, 9999999);
            // \MUtil_Echo::track($number);
        } while ($this->db->fetchOne($sql, [$number, $orgId]));

        return $number;
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('Subscribe using e-mail address only');
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getSubscribeParameters()
    {
        return [
            'formTitle' => sprintf(
                    $this->_('Subscribe to surveys for %s'),
                    $this->currentUser->getCurrentOrganization()->getName()
                    ),
            'patientNrGenerator' => [$this, 'generatePatientNumber'],
            'routeAction' => 'subscribe-thanks',
            'saveLabel' => $this->_('Subscribe'),
        ];
    }

    /**
     *
     * @return array Of snippets
     */
    public function getSubscribeSnippets()
    {
        return ['Subscribe\\EmailSubscribeSnippet'];
    }
}
