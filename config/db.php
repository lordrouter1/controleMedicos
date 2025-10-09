<?php
// Database connection helper
// Customize the constants below with your MySQL credentials.

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'controle_medicos');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

function get_db_connection(): mysqli
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($mysqli->connect_errno) {
        throw new RuntimeException('Erro ao conectar ao banco de dados: ' . $mysqli->connect_error);
    }

    if (!$mysqli->set_charset(DB_CHARSET)) {
        throw new RuntimeException('Erro ao definir charset: ' . $mysqli->error);
    }

    return $mysqli;
}
