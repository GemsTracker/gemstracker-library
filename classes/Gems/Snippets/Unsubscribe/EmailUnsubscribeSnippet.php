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
     * @var \Gems\User\Organization
     */
    protected $currentOrganization;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Since this forms acts as if it was successful when a valid e-mail address was
     * entered we need to store the real state for logging purposes here.
     *
     * @var boolean
     */
    protected $realChange = false;

    /**
     *
     * @var string Optional project specific after unsubscribe message.
     */
    protected $unsubscribedMessage;

    /**
     * @var int The value to assign while unsubscribing
     */
    protected $unsubscribedValue = 0;

    /**
     *
     * @var array of arrays, either null or respondent id and org id
     */
    protected $userData = [0 => []];

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
//        \MUtil\EchoOut\EchoOut::track('EmailUnsubscribeSnippet');
        // Veld inlognaam
        $element = $form->createElement('text', 'email');
        $element->setLabel($this->_('Your E-Mail address'))
                ->setAttrib('size', 30)
                ->setRequired(true)
                ->addValidator('SimpleEmail')
                ->addValidator($this->loader->getSubscriptionThrottleValidator());

        $form->addElement($element);

        return $element;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        // Csrf may be set by project setting in parent
        $this->useCsrf = false;
    }
    
    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        // \MUtil\EchoOut\EchoOut::track($this->getMessenger()->getCurrentMessages(), $this->formData, $this->userData, $this->realChange);

        foreach ($this->userData as $userData) {
            $this->accesslog->logEntry(
                    $this->request,
                    $this->request->getControllerName() . '.' . $this->request->getActionName(),
                    $this->realChange,
                    $this->getMessenger()->getCurrentMessages(),
                    $this->formData + $userData,
                    isset($userData['gr2o_id_user']) ? $userData['gr2o_id_user'] : 0,
                    true);
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData()
    {
        $this->addMessage($this->unsubscribedMessage ?:
                $this->_('If your E-email address is known, you have been unsubscribed.'));

        $sql = "SELECT gr2o_patient_nr, gr2o_id_organization, gr2o_id_user, gr2o_mailable FROM gems__respondent2org
            WHERE gr2o_email = ? AND gr2o_id_organization = ?";

        $rows = $this->db->fetchAll($sql, [$this->formData['email'], $this->currentOrganization->getId()]);
        // \MUtil\EchoOut\EchoOut::track($rows);
        foreach ($rows as $id => $row) {
            // Save respondent & ord id
            $this->userData[$id]['gr2o_id_user']         = $row['gr2o_id_user'];
            $this->userData[$id]['gr2o_id_organization'] = $this->currentOrganization->getId();

            if ($row['gr2o_mailable']) {
                $row['gr2o_mailable'] = $this->unsubscribedValue;
                $row['gr2o_changed'] = new \MUtil\Db\Expr\CurrentTimestamp();
                $row['gr2o_changed_by'] = $row['gr2o_id_user'];

                $where = $this->db->quoteInto("gr2o_patient_nr = ?", $row['gr2o_patient_nr']) . " AND " .
                    $this->db->quoteInto("gr2o_id_organization = ?", $row['gr2o_id_organization']) . " AND " .
                    $this->db->quoteInto("gr2o_mailable > ?", $this->unsubscribedValue);

                $this->db->update('gems__respondent2org', $row, $where);

                // Signal something has actually changed for logging purposes
                $this->realChange = true;
            }
        }

        // Always act like something was saved when
        return 1;
    }
}
