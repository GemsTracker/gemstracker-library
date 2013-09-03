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
 * XmlRa was inspired by SimpleXml, but extends it enabling the
 * use of full XPath statements to retrieve information.
 *
 * XmlRa also handles namespaces correctly, where DimpleXml chokes
 * on them.
 *
 * @package    MUtil
 * @subpackage XmlRa
 * @copyright  Copyright (c) 2013 MagnaFacta BV
 * @license    New BSD License
 * @since      Class available since version 1.3
 */
class MUtil_XmlRa implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * Marker for search anything
     */
    const XMLRA_ANY = '#';

    /**
     * Marker for attribute xpath statements
     */
    const XMLRA_ATTR = '@';

    /**
     * In effect cache of getFirstElement()
     *
     * @var DOMElement
     */
    private $_first;

    // Core variables used by all functions.

    /**
     * Query string to retrieve the next element
     *
     * @var string
     */
    private $_nextName;

    /**
     * The main node, for
     *
     * @var array of nodes
     */
    private $nodes;

    /**
     * The "main" node of this object, unless $nodes is set.
     *
     * @var DOMNode
     */
    private $_rootNode;

    /**
     * Cache of searchNext(), false when last item was reached
     *
     * @var boolean
     */
    private $_searchGoing = true;

    /**
     * Returns the text content of the child items with the tagName
     * $name or of the xpath result set.
     *
     * E.g.:
     *
     * <code>
     * $x->a_name();
     * </code>
     *
     * Returns the concatenated text contents of all the a_name
     * elements.
     *
     * @param string $name
     * @param array $arguments Not used
     * @return string
     */
    public function __call($name, $arguments)
    {
        if ($this->_isForChildren()) {
            $n = $this->_rootNode;
        } else {
            $n = $this->_getFirstElement();
        }


        if ($n) {
            if (self::isXPath($name)) {
                return self::_createFromXPath($n, $name)->toString();
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
     * XmlRa is one object that can behave in three different ways depending on the
     * paramaters passed to this constructor.
     *
     * When a rootNode is passed and the next name is XMLRA_ANY then
     * this XmlRa object loops over all children of the root element.
     *
     * When a rootNode exists and the nextName is NOT XMLRA_ANY then
     * this XmlRa object consists of all children with the same element
     * name, asked for through $this->name.
     *
     * If no rootNode and nextName exist, then this
     * object loops through a list of Dom object passed
     * to it at initiation.
     *
     * @param array $nodes optionally empty array
     * @param DOMNode $rootNode
     * @param type $next_name
     */
    public function __construct(array $nodes, DOMNode $rootNode = null, $nextName)
    {
        $this->_nodes = $nodes;
        $this->_nextName = $nextName;
        $this->_rootNode = $rootNode;
    }

    /**
     * Destructor as the web of cross references in XmlRa is high
     */
    public function __destruct()
    {
        unset($this->_first, $this->_nodes, $this->_nextName, $this->_rootNode, $this->_searchGoing);
    }

    /**
     * Return all child items with the tagName $name or
     * an xpath result set.
     *
     * When used with a tagname you get a special version of this
     * object that allows you to do this:
     *
     * <code>
     * $a = $x->a_name; // returns a possibly empty list of al "a_name" elements
     *
     * $b = $a[5]; // Make sure there are 6 a_name elements and
     * $b->append($another_xml_ra_object); // Append value to object
     * </code>
     *
     * Actually the idea is to be able to add strings and such as well, but hey
     * this is where we are at now.
     *
     * @param string $name element name
     * @return self
     */
    public function __get($name)
    {
        if ($this->_isForChildren()) {
            $n = $this->_rootNode;
        } else {
            $n = $this->_getFirstElement();
        }

        if ($n) {
            if (self::isXPath($name)) {
                return self::_createFromXPath($n, $name);
            } else {
                return self::_createForName($n, $name);
            }
        }
    }

    /**
     * There exists a child item with the tagName $name or
     * the xpath result set is not empty.
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        if ($this->_isForChildren()) {
            $n = $this->_rootNode;
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

    /**
     * Convert this object to a valid XML string.
     *
     * NOTE: This function does not return the string
     * contents of the object, but the XML string
     * representing the object.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->_isForChildren()) {
            return self::xmlToString($this->_rootNode);
        } else {
            $count = $this->count();
            $text = array();

            for ($i = 0; $i < $count; $i++) {
                $text[] = self::xmlToString($this->_nodes[$i]);
            }

            return implode("\n", $text);
        }
    }

    /* public function __unset($name) {
      } */

    /**
     * Private static creater for an XmlRa item that
     * loops over all child items.
     *
     * @param DOMNode $rootNode
     * @return \self
     */
    private static function _createForChildren(DOMNode $rootNode)
    {
        return new self(array(), $rootNode, self::XMLRA_ANY);
    }

    /**
     * Create an instance of all childName elements of rootNode.
     *
     * Only used in conjunction with $this->name
     *
     * @param DOMNode $rootNode
     * @param string $childName
     * @return \self
     */
    private static function _createForName(DOMNode $rootNode, $childName)
    {
        return new self(array(), $rootNode, $childName);
    }

    /**
     * Returns an instance of self containing the nodes in the
     * nodelist as an array of items.
     *
     * @param DOMNodeList $nodes
     * @return \self
     */
    private static function _createFromNodeList(DOMNodeList $nodes)
    {
        $count = $nodes->length;

        if ($count) {
            for ($i = 0; $i < $count; $i++) {
                $narray[] = $nodes->item($i);
            }

            return new self($narray, null, null);
        }
    }

    /**
     * Returns an instance of self containing the nodes returned by the
     * xpath expression.
     *
     * @param DOMNodeList $nodes
     * @return \self
     */
    private static function _createFromXPath(DOMNode $node, $xpath)
    {
        $doc = self::getNodeDocument($node);

        return self::_createFromNodeList($doc->xpathQuery($xpath, $node));
    }

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

    /**
     * When a rootNode exists and the nextName is XMLRA_ANY then
     * this XmlRa object loops over all children of the root element.
     *
     * @return boolean
     */
    private function _isForChildren()
    {
        return $this->_rootNode && $this->_nextName && ($this->_nextName == self::XMLRA_ANY);
    }

    /**
     * When a rootNode exists and the nextName is NOT XMLRA_ANY then
     * this XmlRa object consists of all children with the same element
     * name, asked for through $this->name.
     *
     * @return boolean
     */
    private function _isForName()
    {
        return $this->_rootNode && $this->_nextName && ($this->_nextName != self::XMLRA_ANY);
    }

    /**
     * If no rootNode and nextName exist, then this
     * object loops through a list of Dom object passed
     * to it at initiation.
     *
     * @return boolean
     */
    private function _isForNodelist()
    {
        return !($this->_nextName && $this->_rootNode);
    }

    /**
     * Process the found result for returning
     *
     * @param mixed $node
     * @return mixed XmlRa or text when attribute or DOMCharacterData
     */
    private function _returnValue($node)
    {
        if ($node instanceof DOMNode) {
            if ($node instanceof DOMAttr) {
                return $node->value;
            }

            if ($node instanceof DOMCharacterData) {
                return $node->data;
            }

            return self::_createForChildren($node);
        }

        return $node;
    }

    /**
     * If $value is an instance of self, then this function returns
     * the contained core DOM object.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function _unpackValue($value)
    {
        if ($value instanceof self) {
            if ($value->_isForNodelist()) {
                $value = $value->_getFirstElement();
            } else {
                $value = $value->_rootNode;
            }
        }

        return $value;
    }

    // ArrayObject implementation
    // void ArrayObject::append ( mixed $newval )
    public function append($newval)
    {
        if ($this->_isForChildren()) {
            $n = $this->_rootNode;
        } elseif ($this->_isForName()) {
            $n = $this->_getFirstElement();
        } elseif (count($this->_nodes)) {
            $n = $this->_nodes[0];
        } else {
            throw new MUtil_XmlRa_XmlRaException('No parent node available to append to.');
        }


        if ($newval instanceof self) {
            // echo 'Appending to: '.$n->localName."\n";

            $count = $newval->count();
            for ($i = 0; $i < $count; $i++) {
                // echo 'Appending: '.$newval->_nodes[$i]->localName."\n";
                self::appendNode($n, $newval->_nodes[$i]);
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

    /**
     * Countable implementation
     *
     * Returns the number of nodes in this object
     *
     * @return type
     */
    public function count()
    {
        $this->searchLast();

        if ($this->nodes) {
            return count($this->_nodes);
        } elseif ($this->_rootNode instanceof DOMNode) {
            return $this->_rootNode->childNodes->length;
        }

        return 0;
    }

    private function createNext()
    {
        if ($this->_isForName()) {
            $doc = self::getNodeDocument($this->_rootNode);

            $n = $doc->createElement($this->_nextName);

            $this->_rootNode->appendChild($n);

            $this->_nodes[] = $n;

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

        // echo "XML ERROR:\n" . implode("\n", func_get_args()) . "\n";
        throw new MUtil_XmlRa_XmlRaException($errstr, $errno);
    }

    /**
     * Get the Nth indexed DOMNode element of this object.
     *
     * Use this when you want to perform basic DOM manipulations.
     *
     * @param mixed $index An integer or empty
     * @return DOMNode
     */
    public function getDOMNode($index = null)
    {
        if ($this->_isForChildren() && is_null($index)) {
            return $this->_rootNode;
        } elseif (is_null($index)) {
            return $this->getNodeByNumber(0, false);
        } else {
            return $this->getNodeByNumber($index, false);
        }
    }

    /**
     * Returns the DOMDocument of this XmlRa object.
     *
     * Only the document class of all XmlRa objects is of the
     * DOMDoucment child class MUtil_XmlRa_XmlRaDocument.
     *
     * @return \MUtil_XmlRa_XmlRaDocument
     */
    public function getDocument()
    {
        if ($this->_rootNode) {
            $n = $this->_rootNode;
        } else {
            $n = $this->_getFirstElement();
        }

        if ($n instanceof MUtil_XmlRa_XmlRaDocument) {
            return $n;
        } else {
            return $n->ownerDocument;
        }
    }

    /**
     * ArrayAccess iterator implementation, returns an iterator over
     * all the node children of this item.
     *
     * @return \MUtil_XmlRa_XmlRaIterator
     */
    public function getElementIterator()
    {
        $iter = new MUtil_XmlRa_XmlRaIterator($this);
        $iter->setFilterFunction(array(__CLASS__, 'isElement'));

        return $iter;
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

    /**
     * Return child element when $index is integer, the attribute
     * value when $index starts with '@' and executes and xpath
     * query otherwise.
     *
     * @param mixed $index
     * @param boolean $create
     * @return type
     */
    private function getNode($index, $create)
    {
        $n = null;

        // echo "\nosg ".$index;
        if (is_integer($index)) {
            $n = $this->getNodeByNumber($index, $create);
        } else {
            // Check for simple attribute retrieval
            if ($this->_nextName == self::XMLRA_ANY) {
                $first = $this->_rootNode;
            } else {
                $first = $this->_getFirstElement();
            }

            // Treat index as xpath and check for attribute marker
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
        if (isset($this->_nodes[$index])) {
            return $this->_nodes[$index];
        }

        while ($this->searchNext()) {
            if (isset($this->_nodes[$index])) {
                return $this->_nodes[$index];
            }
        }

        if ($create) {
            while ($this->createNext()) {
                if (isset($this->_nodes[$index])) {
                    return $this->_nodes[$index];
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
        if ($this->_nextName == self::XMLRA_ANY) {
            return true;
        }

        if ($this->_nextName && $node->localName) {
            return $node->localName == $this->_nextName;
        }

        return false;
    }

    /**
     * Return true if the passed value is a DOMElemennt
     *
     * @param mixed $value
     * @return boolean
     */
    public static function isElement($value)
    {
        $value = self::_unpackValue($value);

        return $value instanceof DOMElement;
    }

    /**
     * Return TRUE when the value of this index element is
     * one of the official XML FALSE values.
     *
     * @param type $index
     * @return boolean
     */
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

    /**
     * Check whether two DOMNodes are from the same document.
     *
     * @param DOMNode $n1
     * @param DOMNode $n2
     * @return boolean
     */
    public static function isSameDocument(DOMNode $n1, DOMNode $n2)
    {
        return self::getNodeDocument($n1) === self::getNodeDocument($n2);
    }

    /**
     * Return true when the value of this index element is
     * one of the official XML true values.
     *
     * @param type $index
     * @return boolean
     */
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

    /**
     * Helper function to check whether the input is a real xpath
     * string or 'just' an element name.
     *
     * @param string $path
     * @return boolean
     */
    public static function isXPath($path)
    {
        return preg_match('/[^\*\|\/\[\(\.\s@]*[\*\|\/\[\(\.\s@].*/', $path);
    }

    /**
     * Creates and XmlRa object from a file.
     *
     * One of the two main entry points to this class,
     *
     * @param string $filename
     * @param string $xpath
     * @return self
     * @throws MUtil_XmlRa_XmlRaException
     */
    public static function loadFile($filename, $xpath = null)
    {
        self::_errorInterceptionOn();

        $doc = new MUtil_XmlRa_XmlRaDocument();
        $loaded = $doc->load($filename);

        self::_errorInterceptionOff();

        if ($loaded) {
            $ra = self::_createForChildren($doc);

            if ($xpath) {
                return $ra[$xpath];
            } else {
                return $ra;
            }
        } else {
            throw new MUtil_XmlRa_XmlRaException('Document: "' . $filename . '" failed to load.');
        }
    }

    /**
     * Creates and XmlRa object from a well formatted XML string.
     *
     * One of the two main entry points to this class,
     *
     * @param string $string
     * @return self
     */
    public static function loadString($string)
    {
        $doc = new MUtil_XmlRa_XmlRaDocument();
        $doc->loadXML($string);
        return self::_createForChildren($doc);
    }

    // ArrayObject implementation
    // bool ArrayObject::offsetExists ( mixed $index )
    public function offsetExists($index)
    {
        // echo "Check: $index\n";
        return !is_null($this->getNode($index, false));
    }

    /**
     * ArrayAccess implementation offsetGet
     *
     * Return child element when $index is integer, the attribute
     * value when $index starts with '@' and executes and xpath
     * query otherwise.
     *
     * @param mixed $index
     * @return mixed
     */
    public function offsetGet($index)
    {
        // MUtil_Echo::track("Get $index");
        return $this->_returnValue($this->getNode($index, true));
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
        if ($this->_searchGoing) {
            $n = $this->searchNextNode();

            if ($n) {
                $this->_nodes[] = $n;
                return true;
            } else {
                $this->_searchGoing = false;
            }
        }

        return false;
    }

    /**
     * Only called from searchNext()
     */
    private function searchNextNode()
    {
        if ($this->_isForNodelist()) {
            return null;
        }

        $current = count($this->_nodes) - 1;
        // echo "Counted: $current\n";
        // Start seaching for first node
        if ($current < 0) {
            $nl = $this->_rootNode->childNodes;
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
        $n = $this->_nodes[$current]->nextSibling;

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

    /**
     * Save this XML object to a file.
     *
     * @param string $filename
     * @return This function returns the number of bytes that were written to the file, or FALSE on failure.
     */
    public function toFile($filename)
    {
        return file_put_contents($filename, $this->__toString());
    }

    /**
     * Returns this or the $index value converted to integer.
     *
     * @param mixed $index
     * @return int
     */
    public function toInt($index = null)
    {
        return intval($this->toString($index));
    }

    /**
     * Returns the data in the element to an array, as good as it can.
     *
     * Attributes of the $value will be converted to array entries
     * with '@' prepended to the element attribute names and elements
     * with their localName as element key.
     *
     * If two items have the same name, all values will be combined in an array.
     *
     * <code>
     *  $x = MUtil_XmlRa::loadString('&lt;a b="c"&gt;&lt;d&gt;e&lt;/d&gt;&lt;f&gt;g&lt;/f&gt;&lt;f&gt;h&lt;/f&gt;&lt;/a&gt;');
     *  print_r(MUtil_XmlRa::toArray($x));
     * </code>
     * Will output:
     * <code>
     * array{'@b' => 'c', 'd' => 'e', 'f' => array(0 => 'g', 1 => 'h');
     * </code>
     *
     * @param MUtil_XmlRa $value
     * @return array name -> value[s]
     */
    public static function toArray(self $value)
    {
        $results = array();

        $parent = self::_unpackValue($value);

        if ($parent instanceof DOMDocument) {
            $parent = $parent->documentElement;
        }

        if ($parent instanceof DOMNode) {
            // Iterate first over
            $iter = new AppendIterator();
            if (isset($parent->attributes)) {
                $iter->append(new IteratorIterator($parent->attributes));
            }
            if (isset($parent->childNodes)) {
                $iter->append(new IteratorIterator($parent->childNodes));
            }

            foreach ($iter as $n) {
               if ($n instanceof DOMElement) {
                    $name = $n->tagName;
                    $text = $n->textContent;
                } elseif ($n instanceof DOMAttr) {
                    $name = self::XMLRA_ATTR . $n->localName;
                    $text = $n->textContent;
                } else {
                    $name = null;
                }

                if ($name) {
                    if (isset($results[$name])) {
                        if (! is_array($results[$name])) {
                            $results[$name] = array($results[$name]);
                        }
                        $results[$name][] = $text;
                    } else {
                        $results[$name] = $text;
                    }
                }
            }
        }

        // MUtil_Echo::track($results);

        return $results;
    }

    /**
     * Returns this or the $index value converted to string.
     *
     * Note: this is different from __toString() in that the
     * text context is returned and not a valid XML string.
     *
     * @param mixed $index
     * @return type
     */
    public function toString($index = null)
    {
        if ($index) {
            $val = $this->getNode($index, false);

            if ($val) {
                return $val->textContent;
            }
        } elseif ($this->_isForChildren()) {
            return $this->_rootNode->textContent;
        } else {
            return $this->_getFirstElement()->textContent;
        }
    }

    /**
     * Returns any DOMNode type as a valid XML document string
     *
     * @param DOMNode $node
     * @return string
     */
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
