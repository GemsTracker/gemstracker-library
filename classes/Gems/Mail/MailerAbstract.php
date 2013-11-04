<?php

/**
 * Copyright (c) 2013, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $id MailerAbstract.php
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
abstract class Gems_Mail_MailerAbstract extends MUtil_Registry_TargetAbstract
{
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
    protected $language;
    protected $layout;

    /**
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Project Object
     * @var 
     */
    protected $project;

    /**
     * @var Gems_User_Organization
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
     * After registry load, load the MailFields
     */
    public function afterRegistry()
    {
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
    public function getMailFields($marked=true) {
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
     * @return Gems_User_Organization
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
        if ($template) {
            return $template;
        }

        if ($language !== $this->project->getLocaleDefault()) {
            $language = $this->project->getLocaleDefault();
            $select = $this->db->select();
            $select->from('gems__comm_template_translations')
                   ->where('gctt_id_template = ?', $templateId)
                   ->where('gctt_lang = ?', $language);
            
            $template = $this->db->fetchRow($select);
            if ($template) {
                return $template;
            }
        }
        
        $select = $this->db->select();
        $select->from('gems__comm_template_translations')
               ->where('gctt_id_template = ?', $templateId)
               ->where('gctt_subject <> ""')
               ->where('gctt_body <> ""');
        $template = $this->db->fetchRow($select);
        if ($template) {
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
        $markedMailFields = MUtil_Ra::braceKeys($mailFields, $this->mailFieldMarkers['start'], $this->mailFieldMarkers['end']);
        return $markedMailFields;
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
     * Organization or project Style (as defined in application/configs/email/)
     */
    public function setStyle($style)
    {
        $this->templateStyle = $style;
    }

    /**
     * Set the languange in which the mail should be sent.
     * @param string $language language code
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Set the time when the email should be sent.
     */
    public function setTime()
    {
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
     * set the from field
     */
    public function setFrom($newFrom)
    {
        $this->from = $newFrom;
    }

    /**
     * Set the current Organization as from
     */
    public function setOrganizationFrom() {
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
     * set the base selected template. The actual message could be changed
     * @param integer $templateId Template ID
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
    }

    /**
     * Send the mail
     */
    public function send()
    {
        $mail = $this->loader->getMail();
        
        $mail->setFrom($this->from);
        $mail->addTo($this->to, '', $this->bounceCheck());

        if (isset($this->project->email['bcc'])) {
            $mail->addBcc($this->project->email['bcc']);
        }
        
        $mail->setSubject($this->applyFields($this->subject));

        if ($this->bodyBb) {
            $mail->setBodyBBCode($this->applyFields($this->bodyBb));
        } elseif ($this->bodyHtml) {
            $mail->setBodyHtml($this->applyFields($this->bodyHtml));
        } elseif ($this->bodyText) {
            $mail->setBodyText($this->applyFields($this->bodyText));
        }
        $mail->setTemplateStyle($this->getTemplateStyle());

        $this->beforeMail();

        $mail->send();

        $this->afterMail();
    }
}