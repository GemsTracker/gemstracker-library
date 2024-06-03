<?php

namespace Gems\Db;

class SqliteConverter
{
    public static function fromMysqlSchema(string $schema): string
    {
        $patterns = [
            '/\`([^\`]*)\`/' => '"\1"',
            '/CREATE\s+TABLE\s+if\s+not\s+exists\s+/i' => 'CREATE TABLE ',
            '/AUTO_INCREMENT\s*=\s*\d+\s*/i' => '',
            '/AUTO_INCREMENT/i' => 'AUTOINCREMENT',
            '/AUTOINCREMENT/i' => '',
            '/(\sUNIQUE)\s+KEY(\s+|,|\()/i' => '\1\2',
            '/(\sUNIQUE\s)\s*[^\s(]+\s+\(/i' => '\1(',
            '/,\s*(INDEX|KEY)\s*\([^)]+\)/i' => '',
            '/,\s*(INDEX|KEY)\s+\w+\s*\([^)]+\)/i' => '',
            '/,\s*[^PRIMARY]\sKEY\s*\([^)]+\)/i' => '',
            '/\/\*.*\*\//i' => '',
            '/(\s)BIGINT(\s)/i' => '\1INTEGER\2',
            '/(\s)INT(\s)/i' => '\1INTEGER\2',
            '/(\s)BOOLEAN(\s)/i' => '\1TINYINT(1)\2',
            '/(\s)signed(\s)/i' => '\1',
            '/ENGINE=[^\s,;]+\s*/i' => '',
            '/(DEFAULT|)\s+CHARSET\s*=\s*[^\s,;]+\s+COLLATE\s*=\s*[^\s,;]+/i' => '',
            '/DEFAULT\s+CHARACTER\s+SET\s*/i' => 'CHARACTER SET ',
            '/DEFAULT\s+CHARSET\s*/i' => 'CHARACTER SET ',
            '/CHARACTER\s+SET\s*[^\s,;]+\s*/i' => '',
            '/CHARSET\s+[^\s,;]+\s*/i' => '',
            '/COLLATE\s*[^\s,;]+\s*/i' => '',
            '/\sENUM(\s|\()[^)]+\)\s*/i' => ' VARCHAR(100) ',
            '/SET\s?\([^)]+\)\s*/i' => '',
            '/UNSIGNED\s*/i' => '',
            '/(\s)(DATETIME|DATE|TIMESTAMP)(\s|,)/i' => '\1TEXT\3',
            '/\s+DEFAULT\s+NULL(\s+|,)/i' => '\1',
            '/\s+DEFAULT\s+TRUE(\s+|,)/i' => ' default 1\1',
            '/\s+DEFAULT\s+FALSE(\s+|,)/i' => ' default 0\1',
            '/(?:(\s+NOT\s+NULL)|\s+NULL)([\s,])/i' => '\1\2',
            '/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/i' => '',
            '/\s+REFERENCES\s+[^\s]+\s+\([^)]+\)\s*/i' => ''
        ];

        foreach ($patterns as $pattern => $replacement) {
            $schema = preg_replace($pattern, $replacement, $schema);
        }

        return $schema;
    }
}