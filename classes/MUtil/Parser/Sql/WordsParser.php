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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
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

include_once 'MUtil/Parser/Sql/WordsParserException.php';

/**
 * Parses a statement into SQL 'word', where quoted strings, fields or database
 * object names and comments are seen as a single word each, even when
 * containing whitespace.
 *
 * @package    MUtil
 * @subpackage Parser_Sql
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Parser_Sql_WordsParser
{
    const MODE_WORD = 0;
    const MODE_WHITESPACE = 1;
    const MODE_BRACKET = 2;
    const MODE_COMMA = 3;
    const MODE_SEMI_COLON = 4;
    const MODE_QUOTED_STRING = 100;
    const MODE_LINE_COMMENT = 101;
    const MODE_ACCESS_NAME = 102;
    const MODE_MULTI_LINE_COMMENT = 201;

    private $make_word_function;
    private $len;
    private $len_minus_1;
    private $line;
    private $pos;
    private $start;
    private $statement;

    /**
     *
     * @param unknown_type $statements The sql statements to parse
     * @param unknown_type $make_word_function aq function in the form: self::makeWord($word, $is_word, $start_line, $start_char)
     * @return unknown_type
     *
     *  $make_word_function should be a function accepting these
     *  parameters and returning an object that should be added to
     *  the array returned as a result from splitStatement():
     *
     *      makeWord($word, $is_word, $start_line, $start_char)
     *
     *  The default implementations does returns an array of words,
     *  throwing out the positional and word information.
     */
    public function __construct($statements, $make_word_function = null)
    {
        $this->statement = $statements;
        $this->len = strlen($statements);
        $this->len_minus_1 = $this->len - 1;
        $this->line = 1;
        $this->pos = 1;
        $this->start = 0;
        if ($make_word_function) {
            $this->make_word_function = $make_word_function;
        } else {
            $this->make_word_function = array(__CLASS__, 'makeWord');
        }
    }

    private function findCharEnd($i, $char, $start_line = 0, $start_pos = 0)
    {
        if ($start_line == 0) {
            $start_line = $this->line;
            $start_pos = $this->pos;
        }

        while ((++$i < $this->len) && ($this->statement[$i] != $char)) {
            $this->setLine($i);

            // Check for escape
            if ($this->statement[$i] == '\\') {
                $i++;
                $this->pos++;
            }
        }

        if ($i >= $this->len) {
            throw new MUtil_Parser_Sql_WordsParserException('Opening '.$char.' does not close', $start_line, $start_pos);
        }

        // Check for character repeat
        if (($i < $this->len_minus_1) && ($this->statement[$i + 1] == $char)) {
            $this->pos += 2;
            return $this->findCharEnd($i + 1, $char, $start_line, $start_pos);
        }

        return $i;
    }

    private function findCharsEnd($i, $chars, $start_line = 0, $start_pos = 0)
    {
        $start = $i++;
        $char1 = $chars[0];
        $char2 = $chars[1];

        if ($start_line == 0) {
            $start_line = $this->line;
            $start_pos = $this->pos;
        }

        while ((++$i < $this->len_minus_1) && (! (($this->statement[$i] == $char1) && ($this->statement[$i + 1] == $char2)))) {
            $this->setLine($i);
        }

        if ($i >= $this->len) {
            throw new MUtil_Parser_Sql_WordsParserException('Opening '.$chars.' does not close', $start_line, $start_pos);
        }

        $this->setLine(++$i);

        return $i;
    }

    private function findLineEnd($i)
    {
        $epos = strpos($this->statement, "\n", $i + 1);

        if ($epos === false) {
            return $this->len;
        }

        // One less on Windows line end
        if ($this->statement[$epos - 1] == "\r") {
            return $epos - 2;
        }
        return $epos - 1;
    }

    /**
     * Default implementation for returning tokens from
     * splitStatement.
     *
     * $word       The new word.
     * $is_word    This is a word, not a comment or whitespace.
     * $start_line The (start) line of the current word.
     * $start_char The (start) character of the current word.
     *
     * Not that all this implementation do is return the $word. If
     * you want to use the rest than make your own function that
     * keeps the information at hand.
     */
    public static function makeWord($word, $is_word, $start_line, $start_char)
    {
        return $word;
    }

    private function mode($i)
    {
        switch ($this->statement[$i]) {
        case ' ':
        case "\n":
        case "\t":
        case "\r":
            return self::MODE_WHITESPACE;
        case '\'':
            return self::MODE_QUOTED_STRING;
        case ',':
            return self::MODE_COMMA;
        case ';':
            return self::MODE_SEMI_COLON;
        case '(':
        case ')':
            return self::MODE_BRACKET;
        case '#':
            return self::MODE_LINE_COMMENT;
        case '[':
            return self::MODE_ACCESS_NAME;
        case '-':
            if (($i < $this->len_minus_1) && ($this->statement[$i + 1] == '-')) {
                return self::MODE_LINE_COMMENT;
            }
        case '/':
            if (($i < $this->len_minus_1) && ($this->statement[$i + 1] == '*') && ($this->statement[$i] != '-')) {
                return self::MODE_MULTI_LINE_COMMENT;
            }

        default:
            // Last ditch check
            if (ctype_space($this->statement[$i])) {
                return self::MODE_WHITESPACE;
            }

            return self::MODE_WORD;
        }
    }

    private static function modeIsOneChar($mode)
    {
        switch ($mode) {
        case self::MODE_COMMA;
        case self::MODE_SEMI_COLON;
        case self::MODE_BRACKET;
            return true;
        }

        return false;
    }

    private static function modeIsWord($mode)
    {
        switch ($mode) {
        case self::MODE_WHITESPACE:
        case self::MODE_LINE_COMMENT:
            return false;
        }

        return true;
    }

    private static function modeNotComment($mode)
    {
        switch ($mode) {
        case self::MODE_LINE_COMMENT:
        case self::MODE_MULTI_LINE_COMMENT:
            return false;
        }

        return true;
    }

    private function setLine($i)
    {
        if ($this->statement[$i] == "\n") {
            $this->line++;
            $this->pos = 0;
        } else {
            $this->pos++;
        }
    }

    public function splitStatement($keep_comments = true)
    {
        $i = $this->start;

        if ($i >= $this->len) {
            return;
        }

        switch ($next_mode = $this->mode(0)) {
        case self::MODE_QUOTED_STRING:
            $i = $this->findCharEnd($i, '\'');
            break;
        case self::MODE_LINE_COMMENT:
            $i = $this->findLineEnd($i) + 1;
            break;
        case self::MODE_ACCESS_NAME:
            $i = $this->findCharEnd($i, ']');
            break;
        case self::MODE_MULTI_LINE_COMMENT:
            $i = $this->findCharsEnd($i, '*/') + 1;
            break;
        }
        $last = null;
        $mode_start = $i;
        $mode_start_line = $this->line;
        $mode_start_char = $this->pos;

        $sql = array();

        while ($i < $this->len) {

            // Take care of positioning
            $this->setLine($i);

            $this_mode = $next_mode;
            $next_mode = $this->mode($i);

            if (($this_mode != $next_mode) || self::modeIsOneChar($this_mode)) {
                if ($keep_comments || self::modeNotComment($this_mode)) {
                    $sql[] = call_user_func($this->make_word_function, substr($this->statement, $mode_start, $i - $mode_start), self::modeIsWord($this_mode), $mode_start_line, $mode_start_char);
                }
                $mode_start = $i;
                $mode_start_line = $this->line;
                $mode_start_char = $this->pos;

                switch ($next_mode) {
                case self::MODE_QUOTED_STRING:
                    $i = $this->findCharEnd($i, '\'');
                    break;
                case self::MODE_LINE_COMMENT:
                    $i = $this->findLineEnd($i);
                    break;
                case self::MODE_ACCESS_NAME:
                    $i = $this->findCharEnd($i, ']');
                    break;
                case self::MODE_MULTI_LINE_COMMENT:
                    $i = $this->findCharsEnd($i, '*/');
                    break;
                case self::MODE_SEMI_COLON:
                    $this->start = $i + 1;
                    return $sql;
                }
            }

            $i++;
        }
        // BUG WARNING: Use $next_mode in this line because the while loop has just
        // exited, so the current mode is the next mode.
        if ($sql && ($keep_comments || self::modeNotComment($next_mode))) {
            $sql[] = call_user_func($this->make_word_function, substr($this->statement, $mode_start, $i - $mode_start), self::modeIsWord($this_mode), $mode_start_line, $mode_start_char);
        }
        $this->start = $this->len;

        return $sql;
    }

    public static function splitStatements($statements, $keep_comments = true, $make_strings = true, $make_word_function = null)
    {
        $parser = new self($statements, $make_word_function);

        $stmts = array();

        while ($stmt = $parser->splitStatement($keep_comments)) {
            if ($make_strings) {
                $sql = implode('', $stmt);

                if (strlen(trim($sql))) {
                    $stmts[] = trim($sql);
                }
            } else {
                $stmts[] = $stmt;
            }
        }

        return $stmts;
    }
}
