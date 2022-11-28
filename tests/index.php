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
$where = [
    ['id', '>=', 1],
    ['id', '<=', 3],
];
$pdo = PdoClient::connect($config);

try {
//    $data = $pdo->table('posts')
//        ->where([['status', '=', 1], ['title', 'like', "%test%", "or"]])
//        ->where([['status', '=', 2], ['title', 'like', "%test2%", "or"]])
//        ->groupBy("status")
//        ->select("*")
//        ->first();
//    $data = $pdo->table('posts')
//        ->where([['status', '=', 1], ['title', 'like', "%test%", "or"]])
//        ->where([['status', '=', 2], ['title', 'like', "%test2%", "or"]])
//        ->groupBy("user_id")
//        ->orderBy("id desc")
//        ->field("*")
//        ->all();
//    $data = $pdo->table('posts')
//        ->where([['id', 'IN', [1,2]], ['title', 'like', "%test%", "or"]])
//        ->groupBy("user_id")
//        ->orderBy("id desc")
//        ->field("*")
//        ->count();
    $data = $pdo->table('users')
        ->where("id", "=", 1)
        ->field("*")
        ->first();
} catch (PdoClientException $e) {
    var_dump($e->getMessage());exit;
}
var_dump($pdo->getLastSql());