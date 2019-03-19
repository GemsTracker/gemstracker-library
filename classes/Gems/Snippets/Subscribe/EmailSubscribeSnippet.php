<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Subscribe;

use Gems\Snippets\FormSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 12:35:38
 */
class EmailSubscribeSnippet extends FormSnippetAbstract
{
    /**
     *
     * @var \Gems_User_Organization
     */
    protected $currentOrganization;

    /**
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
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var callable
     */
    protected $patientNrGenerator;

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        // Veld inlognaam
        $element = $form->createElement('text', 'email');
        $element->setLabel($this->_('Your E-Mail address'))
                ->setAttrib('size', 30)
                ->setRequired(true)
                ->addValidator('SimpleEmail');

        $form->addElement($element);

        return $element;
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData()
    {
        $this->addMessage($this->_('You have been subscribed succesfully.'));

        $sql = "SELECT gr2o_id_user FROM gems__respondent2org
            WHERE gr2o_email = ? AND gr2o_id_organization = ?";

        $userId = $this->db->fetchOne($sql, [$this->formData['email'], $this->currentOrganization->getId()]);

        $model = $this->loader->getModels()->createRespondentModel();

        $values['grs_iso_lang']         = $this->locale->getLanguage();
        $values['gr2o_id_organization'] = $this->currentOrganization->getId();
        $values['gr2o_email']           = $this->formData['email'];
        $values['gr2o_mailable']        = 1;
        $values['gr2o_comments']        = $this->_('Created by subscription');
        $values['gr2o_opened_by']       = $this->currentUser->getUserId();

        if ($userId) {
            $values['grs_id_user'] = $userId;
            $values['gr2o_id_user'] = $userId;
        } else {
            $func = $this->patientNrGenerator;
            $values['gr2o_patient_nr']      = $func();
        }
        // \MUtil_Echo::track($values);

        $model->save($values);

        return $model->getChanged();
    }
}
