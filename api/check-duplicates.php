<?php declare(strict_types=1);
require_once dirname(__DIR__) . '/lib/env.php';

session_name('duplicate_check_session');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();

function executeDB(): PDO { return new PDO("mysql:host=" . getenv('DB_HOST') . ";port=" . (getenv('DB_PORT') ?: 3306) . ";dbname=" . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]); }

/**
 * Endpoint para detectar duplicados en la tabla marcas.
 */
function detectDuplicates(PDO $db): array { try {$stmt = executeDB()->query("SELECT COUNT(*) AS total_records FROM marcas"); $groupQuery = "SELECT athlete_id, prueba_id,"; return ['success' => true]; } catch(PDO\StatementException) {} return []; }
