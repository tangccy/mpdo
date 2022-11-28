<?php

use tjn\pdo\PdoClient;
use tjn\pdo\PdoClientException;

require_once "../vendor/autoload.php";


$config = [
	'dbms' => 'mysql',
	'host' => '127.0.0.1',
	'port' => '3306',
	'dbname' => 'learn01',
	'user' => 'root',
	'password' => '123456',
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