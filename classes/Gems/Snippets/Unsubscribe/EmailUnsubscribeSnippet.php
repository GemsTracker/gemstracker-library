<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Unsubscribe;

use Gems\Snippets\FormSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 19-Mar-2019 14:17:37
 */
class EmailUnsubscribeSnippet extends FormSnippetAbstract
{
    /**
     *
     * @var \Gems_User_Organization
     */
    protected $currentOrganization;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

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
        $this->addMessage($this->_('Your e-mail address has been unsubscribed'));

        $sql = "SELECT gr2o_patient_nr, gr2o_id_organization, gr2o_id_user, gr2o_mailable FROM gems__respondent2org
            WHERE gr2o_email = ? AND gr2o_id_organization = ?";

        $row = $this->db->fetchRow($sql, [$this->formData['email'], $this->currentOrganization->getId()]);
        if ($row) {
            if ($row['gr2o_mailable']) {
                $row['gr2o_mailable'] = 0;
                $row['gr2o_changed'] = new \MUtil_Db_Expr_CurrentTimestamp();
                $row['gr2o_changed_by'] = $row['gr2o_id_user'];

                $where = $this->db->quoteInto("gr2o_patient_nr = ?", $row['gr2o_patient_nr']) . " AND " .
                        $this->db->quoteInto("gr2o_id_organization = ?", $row['gr2o_id_organization']);

                $this->db->update('gems__respondent2org', $row, $where);

                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }

    }
}
