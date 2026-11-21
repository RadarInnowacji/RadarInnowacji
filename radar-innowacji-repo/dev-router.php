<?php
// Tylko do uruchomienia lokalnego: php -S localhost:8000 dev-router.php
// Na serwerze Apache tę rolę pełni plik .htaccess.
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($p, '/static/') && is_file(__DIR__ . $p)) return false;
if (preg_match('#^/(lib|templates|data)(/|$)|/\.#', $p)) { http_response_code(403); exit; }
require __DIR__ . '/index.php';
