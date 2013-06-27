<?php

/**
 * Copyright (c) 2013, MagnaFacta BV
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of MagnaFacta BV nor the
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
 * XmlRa class: pronouce "Ra" as "array" except on 19 september, then it is "ahrrray".
 *
 * @package    MUtil
 * @subpackage XmlRa
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 MagnaFacta BV
 * @license    New BSD License
 * @version    $Id: Ra.php 938 2012-09-11 14:00:57Z matijsdejong $
 */

/**
 * DOMDocument extension for easier xpath queries with namespaces
 *
 * @package    MUtil
 * @subpackage XmlRa
 * @copyright  Copyright (c) 2013 MagnaFacta BV
 * @license    New BSD License
 * @since      Class available since version 1.3
 */
class MUtil_XmlRa_XmlRaDocument extends DOMDocument
{

    /**
     *
     * @var array prefix => namespace uri
     */
    private $_namespaces = array();

    /**
     *
     * @var DOMXPath
     */
    private $_xpath = null;

    /**
     *
     * @param string $version The version number of the document as part of the XML declaration.
     * @param string $encoding The encoding of the document as part of the XML declaration.
     */
    public function __construct($version = '1.0', $encoding = 'UTF-8')
    {
        parent::__construct($version, $encoding);

        // The magic, otherwise node->ownerDocument will return a DOMDocument object.
        parent::registerNodeClass('DOMDocument', get_class($this));
    }

    /**
     * Clean up variables
     */
    public function __destruct()
    {
        unset($this->_xpath, $this->_namespaces);
    }

    /**
     * make sure the $this->_xpath object exists
     */
    private function _xpathInitialize()
    {
        if (null === $this->_xpath) {
            $this->_xpath = new DOMXPath($this);

            if ($this->_namespaces) {
                foreach ($this->_namespaces as $prefix => $namespaceUri) {
                    $this->_xpath->registerNamespace($prefix, $namespaceUri);
                }
            }
        }
    }

    /**
     * Get the namespace prefix for an URI
     *
     * @param string $namespaceUri
     * @return string Or false if not found
     */
    public function getNamespacePrefix($namespaceUri)
    {
        if (!$this->_namespaces) {
            return false;
        }

        return array_search($namespaceUri, $this->_namespaces);
    }

    /**
     * Add a prefix / namespace combi to the document
     *
     * @param string $prefix
     * @param string $namespaceUri
     * @return boolean True if succesfull, false e.g. when namespace exists already
     */
    public function registerNamespace($prefix, $namespaceUri)
    {
        if (isset($this->_namespaces[$prefix])) {
            return $this->_namespaces[$prefix] === $namespaceUri;
        }

        $this->_namespaces[$prefix] = $namespaceUri;

        if (null !== $this->_xpath) {
            return $this->_xpath->registerNamespace($prefix, $namespaceUri);
        }

        return true;
    }

    /**
     * Return a single item or null form the xpath expression.
     *
     * @param string $expression XPath query expression
     * @param DOMNode $contextNode
     * @return null|\DOMNode
     */
    public function xpathEvaluate($expression, DOMNode $contextNode = null)
    {

        $this->_xpathInitialize();

        if (null === $contextNode) {
            $result = $this->_xpath->evaluate($expression);
        } else {
            $result = $this->_xpath->evaluate($expression, $contextNode);
        }

        if ($result instanceof DOMNodeList) {
            if ($result->length) {
                return $result->item(0);
            } else {
                return null;
            }
        } else {
            return $result;
        }
    }

    /**
     * Returns a NodeList containging the result of the XPath query
     *
     * @param string $expression XPath query expression
     * @param DOMNode $contextNode
     * @return DOMNodeList
     */
    public function xpathQuery($expression, DOMNode $contextNode = null)
    {
        $this->_xpathInitialize();

        if (is_null($contextNode)) {
            return $this->_xpath->query($expression);
        } else {
            return $this->_xpath->query($expression, $contextNode);
        }
    }
}
