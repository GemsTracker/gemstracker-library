<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Token;

use DateTimeImmutable;
use DateTimeInterface;

use MUtil\Model;

/**
 * Checks whether a token kan be used for the ask/forward loop
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TokenValidator extends \MUtil\Registry\TargetAbstract implements \Zend_Validate_Interface
{
    /**
     *
     * @var array Or single string
     */
    protected $_messages;

    protected $config;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Optional
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var \Gems\Tracker\TrackerInterface
     */
    protected $tracker;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->db instanceof \Zend_Db_Adapter_Abstract &&
                $this->logger instanceof \Psr\Log\LoggerInterface &&
                $this->tracker instanceof \Gems\Tracker\TrackerInterface &&
                $this->translate instanceof \Zend_Translate;
    }

    /**
     * Returns an array of messages that explain why the most recent isValid()
     * call returned false. The array keys are validation failure message identifiers,
     * and the array values are the corresponding human-readable message strings.
     *
     * If isValid() was never called or if the most recent isValid() call
     * returned true, then this method returns an empty array.
     *
     * @return array
     */
    public function getMessages()
    {
        return (array) $this->_messages;
    }

    protected function getRequest()
    {
        if (! $this->request) {
            $this->request = \Zend_Controller_Front::getInstance()->getRequest();
        }

        return $this->request;
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws \Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        if (isset($this->config['survey']['ask'], $this->config['survey']['ask']['askThrottle'])) {
            $throttleSettings = $this->config['survey']['ask']['askThrottle'];
            // Prune the database for (very) old attempts
            $where = $this->db->quoteInto(
                    "gta_datetime < DATE_SUB(NOW(), INTERVAL ? second) AND gta_activated = 0",
                    $throttleSettings['period'] * 20)
                    ;
            $this->db->delete('gems__token_attempts', $where);

            // Retrieve the number of failed attempts that occurred within the specified window
            $select = $this->db->select();
            $select->from('gems__token_attempts', array(
                new \Zend_Db_Expr('COUNT(*) AS attempts'),
                new \Zend_Db_Expr('UNIX_TIMESTAMP(MAX(gta_datetime)) - UNIX_TIMESTAMP() AS last'),
                ))
                    ->where('gta_datetime > DATE_SUB(NOW(), INTERVAL ? second)', $throttleSettings['period']);
            $attemptData = $this->db->fetchRow($select);

            $remainingDelay = ($attemptData['last'] + $throttleSettings['delay']);


            // \MUtil\EchoOut\EchoOut::track($throttleSettings, $attemptData, $remainingDelay, $select->getPart(\Zend_Db_Select::WHERE));
            if ($attemptData['attempts'] > $throttleSettings['threshold'] && $remainingDelay > 0) {
                $this->logger->error("Possible token brute force attack, throttling for $remainingDelay seconds");
//                $msg = sprintf("Additional brute force info: url was %s from ip address %s.", $_SERVER['REQUEST_URI'], $this->request->getServer('REMOTE_ADDR'));
//                $this->logger->error($msg);

                $this->_messages = $this->translate->_('The server is currently busy, please wait a while and try again.');

                $this->db->update(
                        'gems__token_attempts',
                        ['gta_activated' => 1],
                        implode(' AND ', $select->getPart(\Zend_Db_Select::WHERE))
                        );

                return false;
            }
        }

        // The pure token check
        if ($this->isValidToken($value)) {
            return true;
        }

        $max_length = $this->tracker->getTokenLibrary()->getLength();
        $this->db->insert('gems__token_attempts',
            array(
                'gta_id_token'   => substr($value, 0, $max_length),
                'gta_ip_address' => $this->getRequest()->getClientIp()
            )
        );
        return false;
    }

    /**
     * Seperate the incorrect tokens from the right tokens
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isValidToken($value)
    {
        // Make sure the value has the right format
        $value   = $this->tracker->filterToken($value);
        $library = $this->tracker->getTokenLibrary();
        $format  = $library->getFormat();
        $reuse   = $library->hasReuse() ? $library->getReuse() : -1;

        if (strlen($value) !== strlen($format)) {
            $this->_messages = sprintf($this->translate->_('Not a valid token. The format for valid tokens is: %s.'), $format);
            return false;
        }

        $token = $this->tracker->getToken($value);
        if ($token && $token->exists && $token->getReceptionCode()->isSuccess()) {
            $currentDate = new DateTimeImmutable();

            if ($completionTime = $token->getCompletionTime()) {
                // Reuse means a user can use an old token to check for new surveys
                if ($reuse >= 0) {
                    // Oldest date AFTER completiondate. Oldest date is today minus reuse time
                    if ($currentDate->diff($completionTime)->days <= $reuse) {
                        // It is completed and may still be used to look
                        // up other valid tokens.
                        return true;
                    }
                }
                $this->_messages = $this->translate->_('This token is no longer valid.');
                return false;
            }

            $fromDate = $token->getValidFrom();
            if ((null === $fromDate) || ($currentDate->getTimestamp() < $fromDate->getTimestamp())) {
                // Current date is BEFORE from date
                $this->_messages = $this->translate->_('This token cannot (yet) be used.');
                return false;
            }

            if ($untilDate = $token->getValidUntil()) {
                if ($currentDate->getTimestamp() > $untilDate->getTimestamp()) {
                    //Current date is AFTER until date
                    $this->_messages = $this->translate->_('This token is no longer valid.');
                    return false;
                }
            }

            return true;
        } else {
            $this->_messages = $this->translate->_('Unknown token.');
            return false;
        }
    }
}
