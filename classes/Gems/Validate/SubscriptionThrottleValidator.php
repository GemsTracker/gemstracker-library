<?php

/**
 *
 * @package    Gems
 * @subpackage Validate\SubscriptionThrottleValidator
 * @author     Andries Bezem <abezem@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Validate;

/**
 *
 * @package    Gems
 * @subpackage Validate\SubscriptionThrottleValidator
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.8 Jan 9, 2020 1:05:35 PM
 */
class SubscriptionThrottleValidator extends \MUtil\Registry\TargetAbstract implements \Zend_Validate_Interface
{
    /**
     *
     * @var array Or single string
     */
    protected $_messages;

    /**
     * @var array
     */
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
     * @throws \Zend_Valid_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        if (isset($this->config['survey']['ask'], $this->config['survey']['ask']['askThrottle'])) {
            $throttleSettings = $this->config['survey']['ask']['askThrottle'];

            // Prune the database for (very) old attempts
            $where = $this->db->quoteInto(
                    "gsa_datetime < DATE_SUB(NOW(), INTERVAL ? second) AND gsa_activated = 0",
                    $throttleSettings['period'] * 20)
                    ;
            $this->db->delete('gems__subscription_attempts', $where);

            // Retrieve the number of failed attempts that occurred within the specified window
            $select = $this->db->select();
            $select->from('gems__subscription_attempts', array(
                new \Zend_Db_Expr('COUNT(*) AS attempts'),
                new \Zend_Db_Expr('UNIX_TIMESTAMP(MAX(gsa_datetime)) - UNIX_TIMESTAMP() AS last'),
                ))
                    ->where('gsa_datetime > DATE_SUB(NOW(), INTERVAL ? second)', $throttleSettings['period']);
            $attemptData = $this->db->fetchRow($select);

            $remainingDelay = ($attemptData['last'] + $throttleSettings['delay']);


             // \MUtil\EchoOut\EchoOut::track($throttleSettings, $attemptData, $remainingDelay, $select->getPart(\Zend_Db_Select::WHERE));

            if ($attemptData['attempts'] >= $throttleSettings['threshold'] && $remainingDelay > 0) {
                $this->logger->error("Possible subscription brute force attack, throttling for $remainingDelay seconds");
//                $msg = sprintf("Additional brute force info: url was %s from ip address %s.", $_SERVER['REQUEST_URI'], $this->request->getServer('REMOTE_ADDR'));
//                $this->logger->log($msg, \Zend_Log::ERR);

                $this->_messages = $this->translate->_('The server is currently busy, please wait a while and try again.');

                $this->db->update(
                        'gems__subscription_attempts',
                        ['gsa_activated' => 1],
                        implode(' AND ', $select->getPart(\Zend_Db_Select::WHERE))
                        );

                return false;
            }
        }

        // Insert attempt type and IP address
        $this->db->insert('gems__subscription_attempts',
            array(
                'gsa_type_attempt'   => $this->getRequest()->getActionName(),
                'gsa_ip_address' => $this->getRequest()->getClientIp()
            )
        );
        return true;
    }
}
