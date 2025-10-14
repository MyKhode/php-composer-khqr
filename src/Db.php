<?php
namespace App;
use PDO; use PDOException;


class Db
{
private static ?PDO $pdo = null;


public static function pdo(): PDO
{
if (!self::$pdo) {
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_NAME'));
self::$pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
}
return self::$pdo;
}
}