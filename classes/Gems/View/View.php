<?php

namespace Gems\View;

class View extends \Zend_View
{
    /**
     * Callback for escaping.
     *
     * @var string
     */
    private $_escape = 'htmlspecialchars';

    public function escape($var)
    {
        if ($var === null) {
            return $var;
        }
        if (in_array($this->_escape, ['htmlspecialchars', 'htmlentities'])) {
            return call_user_func($this->_escape, $var, ENT_COMPAT, $this->_encoding);
        }

        if (1 == func_num_args()) {
            return call_user_func($this->_escape, $var);
        }
        $args = func_get_args();
        return call_user_func_array($this->_escape, $args);
    }

    /**
     * Sets the _escape() callback.
     *
     * @param mixed $spec The callback for _escape() to use.
     * @return \Zend_View_Abstract
     */
    public function setEscape($spec)
    {
        $this->_escape = $spec;
        return $this;
    }
}
