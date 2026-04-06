<?php
// Database credentials — this file must never be served directly (see .htaccess).
// Use a MySQL account with SELECT-only privileges on the `millport` database:
//   CREATE USER 'millport'@'localhost' IDENTIFIED BY 'strong-password-here';
//   GRANT SELECT ON millport.* TO 'millport'@'localhost';
//   FLUSH PRIVILEGES;

define('DB_HOST',    'localhost');
define('DB_NAME',    'millport');
define('DB_USER',    'millport');
define('DB_PASS',    'CHANGE_ME');
define('DB_CHARSET', 'utf8mb4');

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
