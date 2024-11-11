<?php

namespace GemsTest\testUtils;

class SqliteFunctions
{
    public static function addSqlFunctonsToPdoAdapter(\PDO $pdo)
    {
        $pdo->sqliteCreateFunction('now', [__CLASS__, 'now']);
        $pdo->sqliteCreateFunction('CHAR_LENGTH', 'strlen');
        $pdo->sqliteCreateFunction('concat', [__CLASS__, 'concat']);
        $pdo->sqliteCreateFunction('concat_ws', [__CLASS__, 'concatWs']);
    }

    /**
     * SQLite compatibility implementation for the CONCAT() SQL function.
     */
    public static function concat() {
        $args = func_get_args();
        return implode('', $args);
    }

    /**
     * SQLite compatibility implementation for the CONCAT_WS() SQL function.
     *
     * @see http://dev.mysql.com/doc/refman/5.6/en/string-functions.html#function_concat-ws
     */
    public static function concatWs() {
        $args = func_get_args();
        $separator = array_shift($args);
        // If the separator is NULL, the result is NULL.
        if ($separator === FALSE || is_null($separator)) {
            return NULL;
        }
        // Skip any NULL values after the separator argument.
        $args = array_filter($args, function ($value) {
            return !is_null($value);
        });
        return implode($separator, $args);
    }

    public static function now()
    {
        $now = new \DateTimeImmutable();
        $format = 'Y-m-d';
        return $now->format($format);
    }
}