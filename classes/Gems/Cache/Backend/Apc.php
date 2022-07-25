<?php
/**
 * @package    Gems
 * @subpackage Cache\Backend
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Cache\Backend;

/**
 * Backend for APC(u) cache
 * 
 * This backend is capable of deleting the user cache by prefix. This will leave
 * cache for other project untouched. Also instead of ignoring a clean because
 * tags are not supported, we clean all the cache to prevent unexpected behaviour.
 *
 * @package    Gems
 * @subpackage Cache\Backend
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.2
 */
class Apc extends \Zend_Cache_Backend_Apc
{           
    public function __construct(array $options = array())
    {
        $this->_options = $options;
        parent::__construct($options);
    }
    /**
     * Clean some cache records
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => unsupported
     * 'matchingTag'    => unsupported
     * 'notMatchingTag' => unsupported
     * 'matchingAnyTag' => unsupported
     *
     * @param  string $mode clean mode
     * @param  array  $tags array of tags
     * @throws \Zend_Cache_Exception
     * @return boolean true if no problem
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        switch ($mode) {
            case \Zend_Cache::CLEANING_MODE_ALL:
                return $this->clearAllByPrefix();
                break;
            case \Zend_Cache::CLEANING_MODE_OLD:
                // I think we never use this, but just in case... we leave cleaning the old entries to APC
                $this->_log("Zend_Cache_Backend_Apc::clean() : CLEANING_MODE_OLD is unsupported by the Apc backend");
                break;
            case \Zend_Cache::CLEANING_MODE_MATCHING_TAG:
            case \Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
            case \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                $this->_log(self::TAGS_UNSUPPORTED_BY_CLEAN_OF_APC_BACKEND);
                // We want to clean something, but can not so we clean all
                return $this->clearAllByPrefix();
                break;
            default:
                \Zend_Cache::throwException('Invalid mode for clean() method');
                break;
        }
    }
    
    /**
     * Remove the user entries using a prefix, if no prefix set, all entries will be deleted
     * 
     * @return bool
     */
    public function clearAllByPrefix()
    {
        $prefix = $this->getOption('cache_id_prefix');
        $iterator = new \APCIterator('user', "/^$prefix/", APC_ITER_KEY);
        return apc_delete($iterator);
    }

    /**
     * Set the frontend directives
     * Version without using each for 7.2+
     *
     * @param  array $directives Assoc of directives
     * @throws \Zend_Cache_Exception
     * @return void
     */
    public function setDirectives($directives)
    {

        if (!is_array($directives)) \Zend_Cache::throwException('Directives parameter must be an array');

        foreach ($directives as $name => $value) {
            //while (list($name, $value) = each($directives)) {
            if (!is_string($name)) {
                \Zend_Cache::throwException("Incorrect option name : $name");
            }
            $name = strtolower($name);
            if (array_key_exists($name, $this->_directives)) {
                $this->_directives[$name] = $value;
            }
        }

        $this->_loggerSanity();
    }
    
}
