<?php

namespace Gems\Cache\Backend;


class File extends \Zend_Cache_Backend_File
{
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
        
        foreach($directives as $name=>$value) {
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

