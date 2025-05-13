<?php
$host = 'sql208.ezyro.com';
$dbname = 'ezyro_38880601_wowowo';
$username = 'ezyro_38880601';
$password = 'adeae1cb8df4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantısı başarısız: " . $e->getMessage());
}
?>