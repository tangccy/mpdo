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
//        ->select("*")
//        ->all();
//    $data = $pdo->table('posts')
//        ->where([['id', 'IN', [1,2]], ['title', 'like', "%test%", "or"]])
//        ->groupBy("user_id")
//        ->orderBy("id desc")
//        ->select("*")
//        ->count();
    $data = $pdo->table('posts', 'p')
        ->where([['id', 'IN', [1,2]], ['title', 'like', "%test%", "or"]])
        ->join('user u', 'u.id=p.user_id')
        ->join('comment c', 'u.id=c.user_id', 'left')
        ->groupBy("user_id")
        ->orderBy("id desc")
        ->select("*")
        ->all();
} catch (PdoClientException $e) {
    var_dump($e->getMessage(), $pdo->getLastSql());exit;
}
$lastSql = $pdo->getLastSql();
var_dump($data, $lastSql);