<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Parser_Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

require_once 'PHPUnit/Framework/TestCase.php';

/**
 *
 *
 * @package    MUtil
 * @subpackage Parser_Sql
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class WordsParserTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @var MUtil_Parser_Sql_WordsParser
     */
    protected $_parser;

    /**
     * Test script containing all kind of comments, quoted values, etc...
     *
     * @var string
     */
    protected $_sql = "
SELECT /* comment */ Something as [Really Something] FROM Nothing;

-- Hi Mom

UPDATE Nothing SET Something = '\" /* -- bla' WHERE SomethingElse = \"'quoted'\";

-- Bye mom
";

    /**
     * Result array output without comments, contains the full whitespace
     *
     * This shows you how the raw split is performed
     *
     * @var array
     */
    protected $_sqlOutputArray = array(
        array("
", "SELECT", " ", // Here was a comment
            " ", "Something", " ", "as", " ", "[Really Something]", " ", "FROM", " ", "Nothing"),
        array("

", // another comment
"

", "UPDATE", " ", "Nothing", " ", "SET", " ", "Something", " ", "=", " ", "'\" /* -- bla'", " ",
            "WHERE", " ", "SomethingElse", " ", "=", " ", "\"'quoted'\""),
        array("

", // Third comment
"
"));

    /**
     * Result string output without comments
     *
     * @var array
     */
    protected $_sqlOutputString = array(
        "SELECT  Something as [Really Something] FROM Nothing",
        "UPDATE Nothing SET Something = '\" /* -- bla' WHERE SomethingElse = \"'quoted'\"");

    public function setUp()
    {
        $this->_parser = new MUtil_Parser_Sql_WordsParser($this->_sql);
    }

    public function testParse()
    {
        $result = $this->_parser->splitStatement(false);
        $this->assertEquals($result[1], 'SELECT');
        $this->assertCount(13, $result);
    }

    public function testParseComment()
    {
        $result = $this->_parser->splitStatement(true);
        $this->assertEquals($result[3], '/* comment */');
        $this->assertCount(14, $result);
    }

    public function testSplitAllArray()
    {
        $result = MUtil_Parser_Sql_WordsParser::splitStatements($this->_sql, false, false);
        $this->assertCount(3, $result);
        // $this->assertEquals($result[0], $this->_sqlOutputArray[0]);
        // $this->assertEquals($result[1], $this->_sqlOutputArray[1]);
        // $this->assertEquals($result[2], $this->_sqlOutputArray[2]);
        $this->assertEquals($result, $this->_sqlOutputArray);
    }

    public function testSplitAllString()
    {
        $result = MUtil_Parser_Sql_WordsParser::splitStatements($this->_sql, false, true);
        $this->assertCount(2, $result);
        $this->assertEquals($result, $this->_sqlOutputString);
    }
}
