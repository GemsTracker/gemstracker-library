<?php
/**
 * This file is kept for reference after deleting the obsolete Zend_Db_Statement replacement
 * Only change was in the regular expression in the _stripQuoted() method
 * OLD
 * $regex = "/$q([^$q{$escapeChar}]*|($qe)*)*$q/s"
 * NEW
 * $regex = "/$q([^$q{$escapeChar}]*|($qe)*|({$escapeChar}[\\w_%'\"0{$escapeChar}])*)*$q/s";
 * See line 35-36 of this file
 */

    /**
     * Remove parts of a SQL string that contain quoted strings
     * of values or identifiers.
     *
     * @param string $sql
     * @return string
     * /
    protected function _stripQuoted($sql)
    {
        // get the character for value quoting
        // this should be '
        $q = $this->_adapter->quote('a');
        $q = $q[0];        
        // get the value used as an escaped quote,
        // e.g. \' or ''
        $qe = $this->_adapter->quote($q);
        $qe = substr($qe, 1, 2);
        $qe = preg_quote($qe);
        $escapeChar = substr($qe,0,1);
        // remove 'foo\'bar'
        if (!empty($q)) {
            $escapeChar = preg_quote($escapeChar);
            // this segfaults only after 65,000 characters instead of 9,000
            // $sql = preg_replace("/$q([^$q{$escapeChar}]*|($qe)*)*$q/s", '', $sql);
            $regex = "/$q([^$q{$escapeChar}]*|($qe)*|({$escapeChar}[\\w_%'\"0{$escapeChar}])*)*$q/s";
            $sql = preg_replace($regex, '', $sql);
        }
        
        // get a version of the SQL statement with all quoted
        // values and delimited identifiers stripped out
        // remove "foo\"bar"
        $sql = preg_replace("/\"(\\\\\"|[^\"])*\"/Us", '', $sql);

        // get the character for delimited id quotes,
        // this is usually " but in MySQL is `
        $d = $this->_adapter->quoteIdentifier('a');
        $d = $d[0];
        // get the value used as an escaped delimited id quote,
        // e.g. \" or "" or \`
        $de = $this->_adapter->quoteIdentifier($d);
        $de = substr($de, 1, 2);
        $de = preg_quote($de);
        // Note: $de and $d where never used..., now they are:
        $sql = preg_replace("/$d($de|\\\\{2}|[^$d])*$d/Us", '', $sql);

        return $sql;
    }