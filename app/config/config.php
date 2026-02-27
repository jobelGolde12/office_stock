<?php
date_default_timezone_set("Asia/Kolkata");

$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

define('SITE_ROOT', 'http://localhost:8000/');

define('TURSO_DB_URL', getenv('TURSO_DATABASE_URL'));
define('TURSO_AUTH_TOKEN', getenv('TURSO_AUTH_TOKEN'));

define('DATABASE_TYPE', 'turso');


 ?>
