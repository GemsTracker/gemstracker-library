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
 *
 * @package    MUtil
 * @subpackage XmlRa
 * @copyright  Copyright (c) 2013 MagnaFacta BV
 * @license    New BSD License
 * @since      Class available since version 1.3
 */
class MUtil_XmlRa implements IteratorAggregate, ArrayAccess, Countable
{

    const XMLRA_ANY = '#';
    const XMLRA_ATTR = '@';

    /**
     * In effect cache of getFirstElement()
     *
     * @var DOMElement
     */
    private $_first;

    // Core variables used by all functions.
    private $next_name;
    private $nodes;
    private $root_node;
    private $search_going = true; // Cache of searchNext()

    public function __call($name, $arguments)
    {
        if ($this->isForChildren()) {
            $n = $this->root_node;
        } else {
            $n = $this->_getFirstElement();
        }


        if ($n) {
            if (self::isXPath($name)) {
                return self::createFromXPath($n, $name)->toString();
            } else {
                if ($n->hasChildNodes()) {
                    $nl = $n->childNodes;
                    for ($i = 0; $i < $nl->length; $i++) {
                        if ($nl->item($i)->localName == $name) {
                            return $nl->item($i)->textContent;
                        }
                    }
                }
            }
        }
    }

    /**
     * Do not use this constructor directly, use loadFile() and loadString()
     * instead.
     *
     *
     *
     * @param array $nodes optionally empty array
     * @param DOMNode $rootNode
     * @param type $next_name
     */
    public function __construct(array $nodes, DOMNode $rootNode = null, $next_name)
    {
        $this->nodes = $nodes;
        $this->next_name = $next_name;
        $this->root_node = $rootNode;
    }

    public function __destruct()
    {
        unset($this->nodes, $this->next_name, $this->root_node, $this->_first, $this->search_going);
    }

    public function __get($name)
    {
        if ($this->isForChildren()) {
            $n = $this->root_node;
        } else {
            $n = $this->_getFirstElement();
        }

        if ($n) {
            if (self::isXPath($name)) {
                return self::createFromXPath($n, $name);
            } else {
                return self::createForName($n, $name);
            }
        }
    }

    public function __isset($name)
    {
        if ($this->isForChildren()) {
            $n = $this->root_node;
        } else {
            $n = $this->_getFirstElement();
        }

        if ($n) {
            $doc = self::getNodeDocument($n);

            $r = $doc->xpathEvaluate($name, $n);

            return !is_null($r);
        }

        return false;
    }

    /* public function __set($name, $value) {
      } */

    public function __toString()
    {
        if ($this->isForChildren()) {
            return self::xmlToString($this->root_node);
        } else {
            $count = $this->count();
            $text = array();

            for ($i = 0; $i < $count; $i++) {
                $text[] = self::xmlToString($this->nodes[$i]);
            }

            return implode("\n", $text);
        }
    }

    /* public function __unset($name) {
      } */

    /**
     * Helper function to restore standard error handling
     */
    private static function _errorInterceptionOff()
    {
        restore_error_handler();
    }

    /**
     * Helper function to intercept errors and report them as an exception.
     */
    private static function _errorInterceptionOn()
    {
        set_error_handler(array(__CLASS__, 'errorCallback'));
    }

    /**
     *
     * @return \DOMElement|null
     */
    private function _getFirstElement()
    {
        if ($this->_first) {
            return $this->_first;
        }

        $i = 0;

        while ($n = $this->getNodeByNumber($i++, true)) {
            if ($n instanceof DOMElement) {
                // echo $n->localName."\n";
                $this->_first = $n;
                return $n;
            }
        }

        return null;
    }

    // ArrayObject implementation
    // void ArrayObject::append ( mixed $newval )
    public function append($newval)
    {
        if ($this->isForChildren()) {
            $n = $this->root_node;
        } elseif ($this->isForName()) {
            $n = $this->_getFirstElement();
        } elseif (size($this->nodes)) {
            $n = $this->nodes[0];
        } else {
            throw new MUtil_XmlRa_XmlRaException('No parent node available to append to.');
        }


        if ($newval instanceof self) {
            // echo 'Appending to: '.$n->localName."\n";

            $count = $newval->count();
            for ($i = 0; $i < $count; $i++) {
                // echo 'Appending: '.$newval->nodes[$i]->localName."\n";
                self::appendNode($n, $newval->nodes[$i]);
            }
        } else {
            throw new MUtil_XmlRa_XmlRaException('Cannot (yet) append a value of a ' . get_class($newval) . ' type.');
        }
    }

    private static function appendNode(DOMNode $parent, DOMNode $newchild)
    {
        if ($parent instanceof DOMElement) {
            if ($newchild instanceof DOMAttr) {
                $a = $parent->setAttribute($newchild->name, $newchild->value);
            } else {
                if (self::isSameDocument($parent, $newchild)) {
                    $parent->appendChild($newchild->cloneNode(true));
                } else {
                    $doc = self::getNodeDocument($parent);
                    $parent->appendChild($doc->importNode($newchild, true));
                }
            }
        } else {
            throw new MUtil_XmlRa_XmlRaException('Cannot (yet) append to parent node of the ' . get_class($parent) . ' type.');
        }
    }

    // ArrayObject implementation
    // int ArrayObject::count ( void )
    public function count()
    {
        $this->searchLast();

        return count($this->nodes);
    }

    private static function createForChildren(DOMNode $rootNode)
    {
        return new self(array(), $rootNode, self::XMLRA_ANY);
    }

    private static function createForName(DOMNode $rootNode, $childName)
    {
        return new self(array(), $rootNode, $childName);
    }

    private static function createFromNodeList(DOMNodeList $nodes)
    {
        $count = $nodes->length;

        if ($count) {
            for ($i = 0; $i < $count; $i++) {
                $narray[] = $nodes->item($i);
            }

            return new self($narray, null, null);
        }
    }

    private static function createFromXPath(DOMNode $node, $xpath)
    {
        $doc = self::getNodeDocument($node);

        return self::createFromNodeList($doc->xpathQuery($xpath, $node));
    }

    private function createNext()
    {
        if ($this->isForName()) {
            $doc = self::getNodeDocument($this->root_node);

            $n = $doc->createElement($this->next_name);

            $this->root_node->appendChild($n);

            $this->nodes[] = $n;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Throws an error as an exception
     *
     * @param int $errno
     * @param string $errstr
     * @throws MUtil_XmlRa_XmlRaException
     */
    public static function errorCallback($errno, $errstr, $errfile, $errline, array $errcontext)
    {
        self::_errorInterceptionOff();

        echo "XML ERROR:\n" . implode("\n", func_get_args()) . "\n";

        throw new MUtil_XmlRa_XmlRaException($errstr, $errno);
    }

    public function getDocument()
    {
        if ($this->root_node) {
            $n = $this->root_node;
        } else {
            $n = $this->_getFirstElement();
        }

        if ($n instanceof MUtil_XmlRa_XmlRaDocument) {
            return $n;
        } else {
            return $n->ownerDocument;
        }
    }

    public function getDOMNode($index = null)
    {
        if ($this->isForChildren() && is_null($index)) {
            return $this->root_node;
        } elseif (is_null($index)) {
            return $this->getNodeByNumber(0, false);
        } else {
            return $this->getNodeByNumber($index, false);
        }
    }

    /**
     * ArrayAccess iterator implementation, returns an iterator over
     * all the node children of this item.
     *
     * @return \MUtil_XmlRa_XmlRaIterator
     */
    public function getIterator()
    {
        return new MUtil_XmlRa_XmlRaIterator($this);
    }

    private function getNode($index, $create)
    {
        $n = null;

        // echo "\nosg ".$index;
        if (is_integer($index)) {
            $n = $this->getNodeByNumber($index, $create);
        } else {
            // Check for simple attribute retrieval
            if ($this->next_name == self::XMLRA_ANY) {
                $first = $this->root_node;
            } else {
                $first = $this->_getFirstElement();
            }

            if ($index[0] == self::XMLRA_ATTR) {
                $attr_name = substr($index, 1);

                if ($first->hasAttribute($attr_name)) {
                    $n = $first->getAttributeNode($attr_name);
                } elseif ($create) {
                    $n = $first->setAttribute($attr_name, null);
                }
            }

            if (!$n) {
                $n = $this->getDocument()->xpathEvaluate($index, $first);
            }
        }

        return $n;
    }

    private function getNodeByNumber($index, $create)
    {
        if (isset($this->nodes[$index])) {
            return $this->nodes[$index];
        }

        while ($this->searchNext()) {
            if (isset($this->nodes[$index])) {
                return $this->nodes[$index];
            }
        }

        if ($create) {
            while ($this->createNext()) {
                if (isset($this->nodes[$index])) {
                    return $this->nodes[$index];
                }
            }
        }

        return null;
    }

    private static function getNodeDocument(DOMNode $node)
    {
        if ($node instanceof DOMDocument) {
            $doc = $node;
        } else {
            $doc = $node->ownerDocument;
        }

        return $doc;
    }

    private function hasName(DOMNode $node)
    {
        // Any next sibling will do
        if ($this->next_name == self::XMLRA_ANY) {
            return true;
        }

        if ($this->next_name && $node->localName) {
            return $node->localName == $this->next_name;
        }

        return false;
    }

    public function isFalse($index = null)
    {
        switch ($this->toString($index)) {
            case 'false':
            case '0':
            case 0:
                return true;
            default:
                return false;
        }
    }

    private function isForChildren()
    {
        return $this->root_node && $this->next_name && ($this->next_name == self::XMLRA_ANY);
    }

    private function isForName()
    {
        return $this->root_node && $this->next_name && ($this->next_name != self::XMLRA_ANY);
    }

    private function isForNodelist()
    {
        return !($this->next_name && $this->root_node);
    }

    private static function isSameDocument(DOMNode $n1, DOMNode $n2)
    {
        return self::getNodeDocument($n1) === self::getNodeDocument($n2);
    }

    public function isTrue($index = null)
    {
        switch ($this->toString($index)) {
            case 'true':
            case '1':
            case 1:
                return true;
            default:
                return false;
        }
    }

    private static function isXPath($path)
    {
        return preg_match('/[^\*\|\/\[\(\.\s@]*[\*\|\/\[\(\.\s@].*/', $path);
    }

    public static function loadFile($filename, $xpath = null)
    {
        self::_errorInterceptionOn();

        $doc = new MUtil_XmlRa_XmlRaDocument();
        $loaded = $doc->load($filename);

        self::_errorInterceptionOff();

        if ($loaded) {

            $ra = self::createForChildren($doc);

            if ($xpath) {
                return $ra[$xpath];
            } else {
                return $ra;
            }
        } else {
            throw new MUtil_XmlRa_XmlRaException('Document: "' . $filename . '" failed to load.');
        }
    }

    public static function loadString($string)
    {
        $doc = new MUtil_XmlRa_XmlRaDocument();
        $doc->loadXML($string);
        return self::createForChildren($doc);
    }

    // ArrayObject implementation
    // bool ArrayObject::offsetExists ( mixed $index )
    public function offsetExists($index)
    {
        // echo "Check: $index\n";
        return !is_null($this->getNode($index, false));
    }

    // ArrayObject implementation
    // mixed ArrayObject::offsetGet ( mixed $index )
    public function offsetGet($index)
    {
        // echo "Get $index\n";
        return $this->returnValue($this->getNode($index, true));
    }

    // ArrayObject implementation
    // void ArrayObject::offsetSet ( mixed $index, mixed $newval )
    public function offsetSet($index, $value)
    {
        if ($value) {
            if ($index) {
                throw new MUtil_XmlRa_XmlRaException('Cannot (yet) set the value of an indexed item.');
            } else {
                $this->append($value);
            }
        }
    }

    // ArrayObject implementation
    // void void ArrayObject::offsetUnset ( mixed $index )
    public function offsetUnset($index)
    {
        throw new MUtil_XmlRa_XmlRaException('Cannot (yet) unset the value of an indexed item.');
    }

    private function returnValue($node)
    {
        if ($node instanceof DOMNode) {
            if ($node instanceof DOMAttr) {
                return $node->value;
            }

            if ($node instanceof DOMCharacterData) {
                return $node->data;
            }

            return self::createForChildren($node);
        }

        return $node;
    }

    /* public function saveToFile($filename) {
      $node = $this->rootNode;

      if ($node instanceof DOMDocument) {
      $doc = $node;
      // $doc->normalizeDocument();
      } else {
      // Make a new DOMDocument, otherwise the namespace
      // declarations may come out wrong.
      $doc = new DOMDocument();
      $doc->appendChild($doc->importNode($node, true));
      }

      return $doc->save($filename);
      } */

    private function searchLast()
    {
        while ($this->searchNext());
    }

    private function searchNext()
    {
        if ($this->search_going) {
            $n = $this->searchNextNode();

            if ($n) {
                $this->nodes[] = $n;
                return true;
            } else {
                $this->search_going = false;
            }
        }

        return false;
    }

    /**
     * Only called from searchNext()
     */
    private function searchNextNode()
    {
        if ($this->isForNodelist()) {
            return null;
        }

        $current = count($this->nodes) - 1;
        // echo "Counted: $current\n";
        // Start seaching for first node
        if ($current < 0) {
            $nl = $this->root_node->childNodes;
            $count = $nl->length;

            for ($i = 0; $i < $count; $i++) {
                if ($this->hasName($nl->item($i))) {
                    // echo "Name: ".$nl->item($i)->localName."\n";
                    return $nl->item($i);
                }
            }

            return null;
        }

        // Searching after first found
        $n = $this->nodes[$current]->nextSibling;

        while ($n && (!$this->hasName($n))) {
            $n = $n->nextSibling;
        }

        return $n;
    }

    /* public function toArray($index = null) {
      if (is_null($index)) {
      $node = $this->rootNode;
      } else {
      $node = $this->_getNode($index);
      }

      if (! is_null($node)) {
      if ($node instanceof DOMElement) {
      return xmlra_to_array($node);
      } else {
      return array();
      }
      }
      } */

    public function toFile($filename)
    {
        return file_put_contents($filename, $this->__toString());
    }

    public function toInt($index = null)
    {
        return intval($this->toString($index));
    }

    public function toString($index = null)
    {
        if ($index) {
            $val = $this->getNode($index, false);

            if ($val) {
                return $val->textContent;
            }
        } elseif ($this->isForChildren()) {
            return $this->root_node->textContent;
        } else {
            return $this->_getFirstElement()->textContent;
        }
    }

    public static function xmlToString(DOMNode $node)
    {
        if ($node instanceof DOMAttr) {
            return $node->name . '="' . htmlspecialchars($node->value, ENT_QUOTES) . '"';
        } else {
            if ($node instanceof DOMDocument) {
                $doc = $node;
                $doc->normalizeDocument();
            } else {
                // Make a new DOMDocument, otherwise the namespace
                // declarations may come out wrong.
                $doc = new DOMDocument();
                $doc->appendChild($doc->importNode($node, true));
            }

            // Adding $doc->documentElement makes sure the
            // <?xml declaration is not included.
            return $doc->saveXML($doc->documentElement);
        }
    }

}
