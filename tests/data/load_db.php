<?php
$db = new PDO('sqlite:' . realpath(__DIR__) . '/gemstracker.db');

$contents = file_get_contents(__DIR__ . '/sqllite/create-lite.sql');
$statements = explode(';', $contents);
foreach($statements as $statement) {
    $result = $db->exec($statement);
}

$db = null;