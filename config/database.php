<?php
// -----------------------------------------------
// データベース設定
// -----------------------------------------------

$is_sakura = (strpos($_SERVER['HTTP_HOST'] ?? '', 'sakura.ne.jp') !== false);

if ($is_sakura) {
    // === さくらサーバー用（★要書き換え） ===
    define('DB_HOST', 'XXXX');
    define('DB_NAME', 'XXXX');
    define('DB_USER', 'XXXX');
    define('DB_PASS', 'XXXX');
} else {
    // === ローカル（XAMPP）用 ===
    define('DB_HOST', 'XXXX');
    define('DB_NAME', 'XXXX');
    define('DB_USER', 'XXXX');
    define('DB_PASS', 'XXXX');
}

define('DB_CHARSET', 'utf8mb4');

function getDB(): ?PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            return null;
        }
    }
    return $pdo;
}
