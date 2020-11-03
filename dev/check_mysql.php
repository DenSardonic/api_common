<?php
define('AccessProtect', true);

require_once(__DIR__.'/../utils/ResourceDbMysql.php');

$dbService = new MysqlSmsDatabase();
$conn = $dbService->getConnection();

$query = "select * from stations";

$stmt = $conn->prepare($query);
$stmt->execute();

$res = $stmt->fetchAll();

print_r($res);

?>