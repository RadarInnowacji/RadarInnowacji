<?php
/*
 * Radar Innowacji – przygotowanie do uruchomienia lokalnego.
 * Użycie:  php setup.php            (tworzy .env, bazę, konto admina i dane przykładowe)
 *          php setup.php --bez-demo (bez danych przykładowych)
 *          php setup.php --port=8080
 * Bezpieczne do wielokrotnego uruchamiania – niczego nie nadpisuje.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$ok = fn($m) => print("  [OK]  $m\n");
$fail = function ($m) { fwrite(STDERR, "  [BŁĄD] $m\n"); exit(1); };
$opts = getopt('', ['bez-demo', 'port:']);
$port = (int)($opts['port'] ?? 8000);

echo "\nRadar Innowacji – przygotowanie środowiska lokalnego\n----------------------------------------------------\n";

// 1. Wymagania
if (PHP_VERSION_ID < 80100) $fail('Wymagany PHP 8.1 lub nowszy (masz ' . PHP_VERSION . ').');
$ok('PHP ' . PHP_VERSION);
$missing = array_filter(['pdo_sqlite', 'mbstring'], fn($e) => !extension_loaded($e));
if ($missing) $fail('Brak rozszerzeń PHP: ' . implode(', ', $missing) . ".\n"
    . "         Windows: w pliku php.ini usuń średnik przed liniami extension=pdo_sqlite i extension=mbstring\n"
    . "                  (albo uruchom projekt skryptem start.bat, który włączy je automatycznie).\n"
    . "         Linux:   sudo apt install php-sqlite3 php-mbstring");
$ok('rozszerzenia pdo_sqlite i mbstring');

// 2. Plik .env
$env = __DIR__ . '/.env';
if (!is_file($env)) {
    $tpl = is_file(__DIR__ . '/.env.example') ? file_get_contents(__DIR__ . '/.env.example') : '';
    $set = [
        'SECRET_KEY' => bin2hex(random_bytes(32)), 'HASH_PEPPER' => bin2hex(random_bytes(32)),
        'BASE_URL' => "http://localhost:$port", 'ADMIN_EMAILS' => 'admin@example.com',
        'DEBUG' => 'true', 'SMTP_HOST' => '', 'SHOW_RESULTS' => 'always',
    ];
    foreach ($set as $k => $v) {
        $tpl = preg_match("/^$k=.*$/m", $tpl) ? preg_replace("/^$k=.*$/m", "$k=$v", $tpl) : $tpl . "\n$k=$v";
    }
    file_put_contents($env, $tpl);
    $ok('utworzono .env (tryb lokalny, losowe klucze, admin: admin@example.com)');
} else {
    $ok('.env już istnieje – pozostawiono bez zmian');
}

// 3. Baza, admin, dane przykładowe
require __DIR__ . '/lib/core.php';
require __DIR__ . '/lib/innovation.php';
require __DIR__ . '/lib/demo.php';
if (!is_writable(__DIR__ . '/data')) $fail('Folder data/ musi być zapisywalny.');
db();
$ok('baza danych: ' . cfg('DATABASE', 'data/radar.db'));

$admin = admin_emails()[0] ?? 'admin@example.com';
if (!q1('SELECT id FROM users WHERE email_norm = ?', [normalize_email($admin)])) {
    q('INSERT INTO users(email, email_norm, created_at, is_admin) VALUES (?,?,?,1)', [$admin, normalize_email($admin), time()]);
}
$adminId = (int)qv('SELECT id FROM users WHERE email_norm = ?', [normalize_email($admin)]);
q('UPDATE users SET is_admin = 1 WHERE id = ?', [$adminId]);
$ok("konto administratora: $admin");

if (!isset($opts['bez-demo'])) {
    if ((int)qv('SELECT COUNT(*) FROM projects WHERE is_demo = 1') === 0) {
        $GLOBALS['USER'] = q1('SELECT * FROM users WHERE id = ?', [$adminId]);
        $n = demo_load($adminId);
        $ok("wczytano dane przykładowe ($n wpisów: 25 innowacji, zapotrzebowania, głosy, oceny)");
    } else {
        $ok('dane przykładowe są już wczytane');
    }
}

$debug = cfg_bool('DEBUG');
echo "\nGotowe. Uruchom serwer:\n\n    php -S localhost:$port dev-router.php\n\n"
   . "i otwórz w przeglądarce:  http://localhost:$port\n"
   . "Logowanie jako administrator: „Zaloguj się” → $admin"
   . ($debug ? "\n(w trybie lokalnym link logowania pojawi się od razu na stronie – nie trzeba poczty)\n\n" : "\n\n");
