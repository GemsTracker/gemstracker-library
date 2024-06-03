<?php

namespace Gems\Db;

class SqliteConverter
{
    public static function fromMysqlSchema(string $schema): string
    {
        $patterns = [
            '/\`([^\`]*)\`/' => '"\1"',
            '/CREATE\s+TABLE\s+if\s+not\s+exists\s+/' => 'CREATE TABLE ',
            '/AUTO_INCREMENT\s*=\s*\d+\s*/' => '',
            '/AUTO_INCREMENT/' => 'AUTOINCREMENT',
            '/AUTOINCREMENT/' => '',
            '/(\sUNIQUE)\s+KEY(\s+|,|\()/' => '\1\2',
            '/(\sUNIQUE\s)\s*[^\s(]+\s+\(/' => '\1(',
            '/,\s*(INDEX|KEY)\s*\([^)]+\)/' => '',
            '/,\s*(INDEX|KEY)\s+\w+\s*\([^)]+\)/' => '',
            '/,\s*[^PRIMARY]\sKEY\s*\([^)]+\)/' => '',
            '/\/\*.*\*\//' => '',
            '/(\s)BIGINT(\s)/' => '\1INTEGER\2',
            '/(\s)INT(\s)/' => '\1INTEGER\2',
            '/(\s)BOOLEAN(\s)/' => '\1TINYINT(1)\2',
            '/(\s)signed(\s)/' => '\1',
            '/ENGINE=[^\s,;]+\s*/' => '',
            '/(DEFAULT|)\s+CHARSET\s*=\s*[^\s,;]+\s+COLLATE\s*=\s*[^\s,;]+/' => '',
            '/DEFAULT\s+CHARACTER\s+SET\s*/' => 'CHARACTER SET ',
            '/DEFAULT\s+CHARSET\s*/' => 'CHARACTER SET ',
            '/CHARACTER\s+SET\s*[^\s,;]+\s*/' => '',
            '/CHARSET\s+[^\s,;]+\s*/' => '',
            '/COLLATE\s*[^\s,;]+\s*/' => '',
            '/\sENUM(\s|\()[^)]+\)\s*/' => ' VARCHAR(100) ',
            '/SET\s?\([^)]+\)\s*/' => '',
            '/UNSIGNED\s*/' => '',
            '/(\s)(DATETIME|DATE|TIMESTAMP)(\s|,)/' => '\1TEXT\3',
            '/\s+DEFAULT\s+NULL(\s+|,)/' => '\1',
            '/\s+DEFAULT\s+TRUE(\s+|,)/' => ' default 1\1',
            '/\s+DEFAULT\s+FALSE(\s+|,)/' => ' default 0\1',
            '/(?:(\s+NOT\s+NULL)|\s+NULL)([\s,])/' => '\1\2',
            '/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/' => '',
            '/\s+REFERENCES\s+[^\s]+\s+\([^)]+\)\s*/' => ''
        ];

        foreach ($patterns as $pattern => $replacement) {
            $schema = preg_replace($pattern, $replacement, $schema);
        }

        return $schema;
    }
}