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
$where = [
    ['id', '>=', 1],
    ['id', '<=', 3],
];
$pdo = PdoClient::connect($config);

try {
    $data = $pdo->table('posts')->where($where)->select('`id`')->all();
} catch (PdoClientException $e) {
    var_dump($e->getMessage());exit;
}
$lastSql = $pdo->getLastSql();
var_dump($data, $lastSql);