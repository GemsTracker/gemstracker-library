<?php

/**
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

use MUtil\Translate\TranslateableTrait;

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
abstract class Gems_Mail_MailerAbstract extends \MUtil_Registry_TargetAbstract
{
    use TranslateableTrait;

    /**
     * @var string  Message body in BBcode
     */
    protected $bodyBb;

    /**
     * @var string  Message body in Html
     */
    protected $bodyHtml;

    /**
     * @var string  Message body in Plain Text
     */
    protected $bodyText;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Collection of the different available mailfields
     * @var array
     */
    protected $mailFields;

    /**
     * The mailfields with marked keys
     * @var array
     */
    protected $markedMailFields;

    /**
     * Flash messages
     * @var array
     */
    protected $messages;

    /**
     * @var string  Email From field
     */
    protected $from;

    /**
     * @var string  Email From name field
     */
    protected $fromName;

    /**
     * @var \Zend_Mail
     */
    protected $mail;

    /**
     * @var string
     */
    protected $language;

    protected $layout;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Project Object
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Gems_User_Organization
     */
    protected $organization;

    /**
     * @var integer     Organization ID
     */
    protected $organizationId;

    /**
     * @var string      Subject of the Message
     */
    protected $subject;

    /**
     *
     * @var string      filename of the html template
     */
	protected $templateStyle;

    protected $time;

    /**
     * Collection of all the recipients of the mail
     * @var array
     */
    protected $to = array();

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * The start and end markers for the mailfields
     * @var array
     */
    protected $mailFieldMarkers = array(
                                    'start' => '{',
                                    'end' => '}'
                                    );


    /**
     * Add Mailfields to the existing mailfields
     * @param array $mailfields
     */
    protected function addMailFields($mailfields)
    {
        $this->mailFields = array_merge($mailfields, $this->mailFields);
    }

    /**
     * Add a To field
     */
    public function addTo($newTo, $newToName = '')
    {
        if (is_array($newTo)) {
            $this->to  = array_merge($newTo, $this->to);
        } else {
            if (empty($newToName)) {
                $this->to[] = $newTo;
            } else {
                $this->to[$newToName] = $newTo;
            }
        }
    }

    /**
     * After registry load, load the MailFields
     */
    public function afterRegistry()
    {
        $this->initTranslateable();
        $this->loadOrganization();
        $this->loadMailFields();
    }

    /**
     * After the mail has been processed by the mailer
     */
    protected function afterMail()
    { }

    /**
     * Before the mail is processed by the mailer
     */
    protected function beforeMail()
    { }

    /**
     * Returns true if the "email.bounce" setting exists in the project
     * configuration and is true
     * @return boolean
     */
    public function bounceCheck()
    {
        return $this->project->getEmailBounce();
    }

    /**
     * Return the mailFields
     *
     * @param  boolean $marked      Return the mailfields with their markers
     * @return Array                List of the mailfields and their values
     */
    public function getMailFields($marked=true)
    {
        if ($marked) {
            if (! $this->markedMailFields) {
                $this->markedMailFields = $this->markMailFields($this->mailFields);
            }
            return $this->markedMailFields;
        } else {
            return $this->mailFields;
        }
    }


    /**
     * Add Flash Message
     * @param string $message
     */
    public function addMessage($message)
    {
        $this->messages[] = $message;
    }


    //
    //
    /**
     * Replace the mailkeys with the mailfields in given text
     * @param  string $text     The text to apply the replacment to
     * @return string           The text with replaced mailfields
     */
    public function applyFields($text)
    {
        $mailKeys = array_keys($this->getMailFields());

        return str_replace($mailKeys, $this->mailFields, $text);
    }

    /**
     * Get the prefered template language
     * @return string language code
     */
    public function getLanguage()
    {
        if ($this->project->getEmailMultiLanguage() && $this->language) {
            return $this->language;
        } else {
            return $this->project->getLocaleDefault();
        }
    }

    public function getMail()
    {
        if (!$this->mail) {
            $this->mail = $this->loader->getMail();
        }
        return $this->mail;
    }

    /**
     * Get Flash message
     * @return Array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get the organization in relation to the current mailtarget
     * @return \Gems_User_Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Get specific data set in the mailer
     * @return Array
     */
    public function getPresetTargetData()
    {
        $targetData = array();
        if ($this->to) {
            $fullDisplay = array();
            foreach($this->to as $name=>$address) {
                $fullTo = '';
                if (!is_numeric($name)) {
                    $fullTo .= $name . ' ';
                }
                $fullTo .= '<' . $address . '>';
                $fullDisplay[] = $fullTo;
            }
            $targetData['to'] = join(', ', $fullDisplay);
        }
        if ($this->language) {
            $allLanguages = $this->util->getLocalized()->getLanguages();
            $targetData['prefered_language'] = $allLanguages[$this->language];
        }

        return $targetData;
    }

    /**
     * Get the correct Mail Template Style
     * @return string
     */
    protected function getTemplateStyle()
    {
        if ($this->templateStyle) {
            return $this->templateStyle;
        } else {
            return $this->organization->getStyle();
        }
    }

    /**
     * get a specific template.
     * If the selected translation exists, get that translation
     * Else if the selected translation isn't the default, select the default translation
     * Else select the first not empty translation
     * Else return false
     * @param integer  $templateId Template ID
     */
    public function getTemplate($templateId, $language=null)
    {
        if (!$language) {
            $language = $this->getLanguage();
        }
        $select = $this->db->select();
        $select->from('gems__comm_template_translations')
               ->where('gctt_id_template = ?', $templateId)
               ->where('gctt_lang = ?', $language);

        $template = $this->db->fetchRow($select);
        if ($template && !empty($template['gctt_subject'])) {
            return $template;
        }

        if ($language !== $this->project->getLocaleDefault()) {
            $language = $this->project->getLocaleDefault();
            $select = $this->db->select();
            $select->from('gems__comm_template_translations')
                   ->where('gctt_id_template = ?', $templateId)
                   ->where('gctt_lang = ?', $language);

            $template = $this->db->fetchRow($select);
            if ($template && !empty($template['gctt_subject'])) {
                return $template;
            }
        }

        $select = $this->db->select();
        $select->from('gems__comm_template_translations')
               ->where('gctt_id_template = ?', $templateId)
               ->where('gctt_subject <> ""')
               ->where('gctt_body <> ""');
        $template = $this->db->fetchRow($select);
        if ($template && !empty($template['gctt_subject'])) {
            return $template;
        } else {
            return false;
        }
    }

    /**
     * Initialize the mailfields
     */
    protected function loadMailFields()
    {
        if ($this->organization) {
            $this->mailFields = array_merge($this->project->getMailFields(), $this->organization->getMailFields());
        } else {
            $this->mailFields = $this->project->getMailFields();
        }
    }

    /**
     * Load the organization from the given organization id
     */
    protected function loadOrganization()
    {
        if (!$this->organizationId) {
            $this->loadOrganizationId();
        }
        $this->organization = $this->loader->getOrganization($this->organizationId);
    }

    /**
     * Function to get the current organization id.
     */
    protected function loadOrganizationId()
    { }

    /**
     * Add specified markers to the mailfield keys
     * @param  Array $mailfields
     * @return Array            The marked mailfields
     */
    public function markMailFields($mailFields)
    {
        $markedMailFields = \MUtil_Ra::braceKeys($mailFields, $this->mailFieldMarkers['start'], $this->mailFieldMarkers['end']);
        return $markedMailFields;
    }

    /**
     * Send the mail
     */
    public function send()
    {
        $mail = $this->getMail();

        $mail->setFrom($this->from, $this->fromName);
        $mail->addTo($this->to, '', $this->bounceCheck());

        if (isset($this->project->email['bcc'])) {
            $mail->addBcc($this->project->email['bcc']);
        }

        $mail->setSubject($this->applyFields($this->subject));

        $mail->setTemplateStyle($this->getTemplateStyle());

        if ($this->bodyBb) {
            $mail->setBodyBBCode($this->applyFields($this->bodyBb));
        } elseif ($this->bodyHtml) {
            $mail->setBodyHtml($this->applyFields($this->bodyHtml));
        } elseif ($this->bodyText) {
            $mail->setBodyText($this->applyFields($this->bodyText));
        }

        $this->beforeMail();

        $mail->send();

        $this->afterMail();
    }

    public function setBody($message, $renderer = 'Bbcode')
    {
        if ($renderer == 'Bbcode') {
            $this->bodyBb = $message;
        } elseif ($renderer == 'Html') {
            $this->bodyHtml = $message;
        } elseif ($renderer == 'Text') {
            $this->bodyText = $message;
        }
    }


    /**
     * set the from field
     */
    public function setFrom($newFrom, $newFromName = null)
    {
        $this->from = $newFrom;
        $this->fromName = $newFromName;
        $this->mailFields['from'] = $this->mailFields['reply_to'] = $newFrom;
    }

    /**
     * Set the language in which the mail should be sent.
     * @param string $language language code
     * @param false $reloadFields
     */
    public function setLanguage($language, $reloadFields = false)
    {
        $this->language = $language;
        if ($reloadFields) {
            $this->loadMailFields();
            $this->markedMailFields = null;
        }
    }

    /**
     * Organization or project Style (as defined in application/configs/email/)
     */
    public function setStyle($style)
    {
        $this->templateStyle = $style;
    }

    /**
     * Set the time when the email should be sent.
     */
    public function setTime()
    {
    }

    /**
     * Set the current Organization as from
     */
    public function setOrganizationFrom()
    {
        $this->from = $this->organization->getEmail();
    }

    /**
     * set the subject of the mail
     */
    public function setSubject($newSubject)
    {
        $this->subject = $newSubject;
    }

    /**
     * set a specific template as subject/body
     * @param integer  $templateId Template ID
     */
    public function setTemplate($templateId)
    {
        if ($template = $this->getTemplate($templateId)) {
            $this->subject = $template['gctt_subject'];
            $this->bodyBb = $template['gctt_body'];
            $this->setTemplateId($templateId);
        }
    }

    /**
     * Use the Mail template code to select and set the template
     * @param string mail
     */
    public function setTemplateByCode($templateCode)
    {
        $select = $this->loader->getModels()->getCommTemplateModel()->getSelect();
        $select->where('gct_code = ?', $templateCode);

        $template = $this->db->fetchRow($select);
        if ($template) {
            $this->setTemplate($template['gct_id_template']);
            return true;
        } else {
            return false;
        }
    }

    /**
     * set the base selected template. The actual message could be changed
     * @param integer $templateId Template ID
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
    }


    /**
     * set the To field, overwriting earlier settings completely
     * @param [type] $to [description]
     */
    public function setTo($newTo, $newToName = '')
    {
        if (is_array($newTo)) {
            $this->to  = $newTo;
        } else {
            $this->to = array();
            if (empty($newToName)) {
                $this->to[] = $newTo;
            } else {
                $this->to[$newToName] = $newTo;
            }
        }
    }
}
