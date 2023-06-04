<?php

/**
 * This class adds the -f parameter to the sendmail command, so bounces
 * will be handled correct. Also does a final check on a sender being present.
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
namespace Gems\Mail\Transport;

class SendMail extends \Zend_Mail_Transport_Sendmail {
    public function _sendMail() {
        $from = $this->_mail->getFrom();
        
        if (!empty($from)) {
            $params = sprintf('-f%s', $from);
            $this->parameters = $params;
            
            if (!ini_get('safe_mode')) {
                $old_from = ini_get('sendmail_from');
                ini_set('sendmail_from', $from);
            }
        } else {
            throw new \Gems\Exception('No sender email set!');
        }
                
        parent::_sendMail();
        
        if (isset($old_from)) {
            ini_set('sendmail_from', $old_from);
        }
    }
}
