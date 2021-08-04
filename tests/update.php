<?php

use tjn\pdo\PdoClient;
use tjn\pdo\PdoClientException;

require_once "../vendor/autoload.php";


$config = [
    'dbms' => 'mysql',
    'host' => '192.168.6.216',
    'post' => '3306',
    'dbname' => 'blog',
    'user' => 'root',
    'password' => 'root',
];

$pdo = PdoClient::connect($config);

try {
    $data = [
        'user_id' => 1,
        'title' => 'test',
        'content' => 'content test',
    ];
    $status = $pdo->table('posts')->where([['id', '=', 1]])->update($data);
} catch (PdoClientException $e) {
    var_dump($e->getMessage());
    exit;
}
$lastSql = $pdo->getLastSql();
var_dump($status, $lastSql);