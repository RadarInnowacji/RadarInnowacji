<?php
/* Radar Innowacji – rdzeń: konfiguracja, baza, bezpieczeństwo, ocena ryzyka. */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
const CATEGORIES = ['Szkoła', 'Uczelnia', 'Samorząd', 'Inna instytucja publiczna'];

/* ===================================================================== konfiguracja (.env) */

function load_env(string $path): array {
    $env = [];
    if (!is_file($path)) return $env;
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim(trim($v), "\"'");
    }
    return $env;
}

$GLOBALS['ENV'] = load_env(ROOT . '/.env');

function cfg(string $key, $default = null) {
    $v = $GLOBALS['ENV'][$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
}
function cfg_int(string $key, int $default): int { return (int)cfg($key, $default); }
function cfg_bool(string $key, bool $default = false): bool {
    $v = cfg($key);
    return $v === null ? $default : in_array(strtolower((string)$v), ['1', 'true', 'yes', 'tak', 'on'], true);
}

function site_name(): string { return cfg('SITE_NAME', 'Radar Innowacji'); }
function base_url(): string { return rtrim(cfg('BASE_URL', 'http://localhost:8000'), '/'); }
function is_debug(): bool { return cfg_bool('DEBUG'); }
function secret(): string {
    $s = cfg('SECRET_KEY');
    if (!$s) { http_response_code(500); exit('Brak SECRET_KEY w pliku .env'); }
    return $s;
}
function pepper(): string { return cfg('HASH_PEPPER') ?: secret() . '-pepper'; }
function admin_emails(): array {
    return array_filter(array_map(fn($e) => strtolower(trim($e)), explode(',', (string)cfg('ADMIN_EMAILS', ''))));
}
function votes_per_user(): int { return cfg_int('VOTES_PER_USER', 3); }

/* ===================================================================== baza danych (SQLite) */

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $path = cfg('DATABASE', ROOT . '/data/radar.db');
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 15,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 15000;');
    $pdo->exec(file_get_contents(ROOT . '/lib/schema.sql'));
    migrate($pdo);
    return $pdo;
}

/* Dodaje nowe kolumny do istniejącej bazy (aktualizacja bez utraty danych). */
function migrate(PDO $pdo): void {
    $have = array_column($pdo->query('PRAGMA table_info(projects)')->fetchAll(), 'name');
    $cols = [
        'innovation_type' => "TEXT NOT NULL DEFAULT 'organizacyjna'",
        'stage'      => "TEXT NOT NULL DEFAULT 'lokalnie'",
        'evidence'   => "TEXT NOT NULL DEFAULT 'obserwacje'",
        'scope'      => "TEXT NOT NULL DEFAULT 'region'",
        'cost'       => "TEXT NOT NULL DEFAULT 'sredni'",
        'commercial' => "TEXT NOT NULL DEFAULT 'moze'",
        'needs'      => "TEXT NOT NULL DEFAULT ''",
        'areas'      => "TEXT NOT NULL DEFAULT ''",
        'problem'    => "TEXT NOT NULL DEFAULT ''",
        'results'    => "TEXT NOT NULL DEFAULT ''",
        'creators'   => "TEXT NOT NULL DEFAULT ''",
        'pipeline'   => "TEXT NOT NULL DEFAULT 'zgloszona'",
        'is_demo'    => 'INTEGER NOT NULL DEFAULT 0',
    ];
    foreach ($cols as $c => $def) if (!in_array($c, $have, true)) $pdo->exec("ALTER TABLE projects ADD COLUMN $c $def");
    $ucols = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
    if (!in_array('is_demo', $ucols, true)) $pdo->exec('ALTER TABLE users ADD COLUMN is_demo INTEGER NOT NULL DEFAULT 0');
}

function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function q1(string $sql, array $params = []) { return q($sql, $params)->fetch() ?: null; }
function qv(string $sql, array $params = []) { return q($sql, $params)->fetchColumn(); }

function audit(string $action, ?array $details = null): void {
    q('INSERT INTO audit_log(ts, actor_id, action, details) VALUES (?,?,?,?)',
      [time(), current_user()['id'] ?? null, $action, $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null]);
}

/* ===================================================================== pomocnicze */

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function dt($ts): string { return $ts ? date('d.m.Y H:i', (int)$ts) : '—'; }
function glosy($n): string {
    $n = (int)$n;
    if ($n === 1) return 'głos';
    if (in_array($n % 10, [2, 3, 4], true) && !in_array($n % 100, [12, 13, 14], true)) return 'głosy';
    return 'głosów';
}
function asset(string $p): string {
    $f = ROOT . '/static/' . $p;
    return '/static/' . $p . (is_file($f) ? '?v=' . filemtime($f) : '');
}
function flash(string $msg, string $cat = 'info'): void { $_SESSION['flash'][] = [$cat, $msg]; }
function take_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function redirect(string $to, int $code = 302): never { header('Location: ' . $to, true, $code); exit; }
function safe_next(?string $n): string {
    return ($n && str_starts_with($n, '/') && !str_starts_with($n, '//') && !str_contains($n, '\\')) ? $n : '/';
}
function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===================================================================== hashe (RODO) i IP */

function phash(?string $v): ?string {
    return ($v === null || $v === '') ? null : substr(hash_hmac('sha256', $v, pepper()), 0, 32);
}
function client_ip(): string {
    if (cfg_bool('CLOUDFLARE') && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function subnet_of(string $ip): string {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $p = explode('.', $ip);
        return "$p[0].$p[1].$p[2].0/24";
    }
    $bin = @inet_pton($ip);
    if ($bin === false) return $ip;
    return bin2hex(substr($bin, 0, 6)) . '/48';
}

/* ===================================================================== e-maile */

function disposable_domains(): array {
    static $set = null;
    if ($set === null) {
        $set = [];
        foreach (file(ROOT . '/lib/disposable_domains.txt', FILE_IGNORE_NEW_LINES) as $l) {
            $l = strtolower(trim($l));
            if ($l !== '' && $l[0] !== '#') $set[$l] = true;
        }
    }
    return $set;
}
function normalize_email(string $email): string {
    $email = strtolower(trim($email));
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    $local = explode('+', $local, 2)[0];
    if ($domain === 'gmail.com' || $domain === 'googlemail.com') { $local = str_replace('.', '', $local); $domain = 'gmail.com'; }
    return "$local@$domain";
}
function check_email(string $email): ?string {
    if ($email === '' || strlen($email) > 254 || !preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $email))
        return 'Podaj poprawny adres e-mail.';
    $domain = strtolower(substr($email, strrpos($email, '@') + 1));
    $parts = explode('.', $domain);
    for ($i = 0; $i < count($parts) - 1; $i++)
        if (isset(disposable_domains()[implode('.', array_slice($parts, $i))]))
            return 'Tego adresu e-mail nie można użyć. Podaj inny adres.';
    $allowed = array_filter(array_map('trim', explode(',', strtolower((string)cfg('ALLOWED_EMAIL_DOMAINS', '')))));
    if ($allowed) {
        $ok = false;
        foreach ($allowed as $d) if ($domain === $d || str_ends_with($domain, ".$d")) $ok = true;
        if (!$ok) return 'Ten adres e-mail nie jest dopuszczony w tej edycji.';
    }
    return null;
}

/* ===================================================================== wysyłka poczty */

function send_mail(string $to, string $subject, string $body, ?string $button = null): bool {
    $from = cfg('SMTP_FROM', site_name() . ' <no-reply@' . parse_url(base_url(), PHP_URL_HOST) . '>');
    $subjEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $html = mail_html($subject, $body, $button);
    $boundary = 'b' . bin2hex(random_bytes(12));
    $mime = "MIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$boundary\"\r\nAuto-Submitted: auto-generated";
    $content = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($body))
             . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
             . "--$boundary--\r\n";
    if (!cfg('SMTP_HOST')) {
        // Bez SMTP: wbudowana funkcja mail() serwera (na nazwa.pl działa od ręki)
        if (is_debug()) {
            error_log("[DEV MAIL] $to | $subject\n$body");
            @file_put_contents(ROOT . '/data/ostatni-mail.html', $html);   // podgląd wyglądu przy pracy lokalnej
            return true;
        }
        preg_match('/<([^>]+)>/', $from, $m);
        $fromAddr = $m[1] ?? $from;
        $headers = "From: $from\r\nReply-To: $fromAddr\r\n$mime";
        $ok = @mail($to, $subjEnc, $content, $headers, '-f' . $fromAddr);
        mail_log('mail() z parametrem -f: ' . ($ok ? 'przyjęte przez serwer' : 'ODRZUCONE'));
        if (!$ok) {
            $ok = @mail($to, $subjEnc, $content, $headers);
            mail_log('mail() bez -f: ' . ($ok ? 'przyjęte przez serwer' : 'ODRZUCONE'));
        }
        return $ok;
    }
    return smtp_send($to, $subjEnc, $content, $from, $mime);
}

/* Zamienia zwykły tekst maila na estetyczny HTML (tabele + style inline – działa w Gmailu, Outlooku i na telefonach).
   Reguły: pusta linia = nowy akapit; linia z samym adresem lub „Etykieta: adres” z tej strony = przycisk;
   „Klucz: wartość” = pogrubiony klucz; akapit zaczynający się od „Wiadomość:” = cytat; ostatni akapit z nazwą serwisu = stopka. */
function mail_html(string $subject, string $body, ?string $button = null): string {
    $site = site_name(); $base = base_url(); $host = parse_url($base, PHP_URL_HOST) ?: $base;
    $e = fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8');
    $link = fn($x) => preg_replace('~(https?://[^\s<]+)~u', '<a href="$1" style="color:#0d4a86;text-decoration:underline">$1</a>',
                         preg_replace('~([\w.+-]+@[\w-]+\.[\w.-]+)~u', '<a href="mailto:$1" style="color:#0d4a86">$1</a>', $x));
    $btn = fn($label, $url) => '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0"><tr><td style="background:#0d4a86;border-radius:6px">'
        . '<a href="' . $e($url) . '" style="display:inline-block;padding:14px 28px;font:700 16px/1.2 Arial,Helvetica,sans-serif;color:#ffffff;text-decoration:none">' . $e($label) . ' &rarr;</a></td></tr></table>';
    $paras = preg_split("/\n\s*\n/", trim(str_replace("\r", '', $body)));
    if ($paras && trim(end($paras)) === $site) array_pop($paras);
    $out = ''; $pre = '';
    foreach ($paras as $para) {
        $lines = explode("\n", $para);
        if (preg_match('/^Wiadomość:\s*$/u', $lines[0]) && count($lines) > 1) {
            $out .= '<p style="margin:0 0 6px;font:700 13px Arial,sans-serif;color:#5b6b80;text-transform:uppercase;letter-spacing:.5px">Wiadomość</p>'
                  . '<div style="margin:0 0 18px;padding:14px 18px;background:#f3f7fb;border-left:4px solid #8cc63f;font:15px/1.6 Arial,sans-serif;color:#1d2b3a">'
                  . nl2br($e(implode("\n", array_slice($lines, 1)))) . '</div>';
            continue;
        }
        $html = []; $buttons = '';
        foreach ($lines as $ln) {
            $t = trim($ln);
            if ($pre === '' && $t !== '' && !preg_match('~https?://~', $t)) $pre = $t;
            if (preg_match('~^(https?://\S+)$~', $t, $m) && str_starts_with($m[1], $base)) { $buttons .= $btn($button ?? 'Otwórz', $m[1]) . '<p style="margin:-8px 0 18px;font:12px/1.5 Arial,sans-serif;color:#6b7a8c;word-break:break-all">Przycisk nie działa? Skopiuj ten adres do przeglądarki:<br>' . $link($e($m[1])) . '</p>'; continue; }
            if (preg_match('~^([^:]{2,30}):\s*(https?://\S+)$~u', $t, $m) && str_starts_with($m[2], $base)) { $buttons .= $btn($m[1], $m[2]); continue; }
            if (preg_match('/^([A-ZĄĆĘŁŃÓŚŹŻ][^:]{1,30}):\s+(.+)$/u', $t, $m))
                $html[] = '<strong style="color:#0b1d45">' . $e($m[1]) . ':</strong> ' . $link($e($m[2]));
            else $html[] = $link($e($t));
        }
        $html = array_filter($html, fn($x) => $x !== '');
        if ($html) $out .= '<p style="margin:0 0 16px;font:16px/1.6 Arial,Helvetica,sans-serif;color:#1d2b3a">' . implode('<br>', $html) . '</p>';
        $out .= $buttons;
    }
    $logo = $e($base . '/static/mail-logo.png');
    return '<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="color-scheme" content="light"><title>' . $e($subject) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#eef2f7">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . $e(mb_substr($pre, 0, 120)) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7"><tr><td align="center" style="padding:28px 12px">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px">'
        // nagłówek
        . '<tr><td style="background:#0b1d45;border-radius:10px 10px 0 0;padding:22px 32px">'
        . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
        . '<td style="padding-right:12px"><img src="' . $logo . '" width="40" height="40" alt="" style="display:block;border:0"></td>'
        . '<td style="font:700 20px Arial,Helvetica,sans-serif;color:#ffffff">' . $e($site) . '</td></tr></table></td></tr>'
        . '<tr><td style="background:#8cc63f;height:4px;line-height:4px;font-size:0">&nbsp;</td></tr>'
        // treść
        . '<tr><td style="background:#ffffff;padding:32px 32px 18px">'
        . '<h1 style="margin:0 0 20px;font:700 22px/1.3 Arial,Helvetica,sans-serif;color:#0b1d45">' . $e($subject) . '</h1>'
        . $out . '</td></tr>'
        // stopka
        . '<tr><td style="background:#ffffff;border-radius:0 0 10px 10px;padding:0 32px 26px">'
        . '<p style="margin:0;padding-top:16px;border-top:1px solid #e2e8f0;font:13px/1.5 Arial,sans-serif;color:#6b7a8c">'
        . 'Wiadomość wysłana automatycznie – prosimy na nią nie odpowiadać.<br>'
        . '<a href="' . $e($base) . '" style="color:#0d4a86;text-decoration:none;font-weight:700">' . $e($host) . '</a> · odkrywamy, oceniamy i łączymy lokalne innowacje</p>'
        . '</td></tr></table></td></tr></table></body></html>';
}

function mail_log(string $line): void { $GLOBALS['MAIL_LOG'][] = $line; }

function smtp_send(string $to, string $subject, string $content, string $from, string $mime): bool {
    $host = cfg('SMTP_HOST'); $port = cfg_int('SMTP_PORT', 465); $ssl = cfg_bool('SMTP_SSL', $port === 465);
    $fp = @stream_socket_client(($ssl ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $errstr, 15);
    if (!$fp) { error_log("SMTP connect: $errstr"); mail_log("Nie można połączyć z $host:$port – $errstr"); return false; }
    mail_log("Połączono z $host:$port" . ($ssl ? ' (SSL)' : ''));
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) { $r = ''; while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
    $cmd = function (string $c, array $ok, ?string $label = null) use ($fp, $read) {
        fwrite($fp, $c . "\r\n"); $r = $read();
        mail_log('> ' . ($label ?? $c) . '   < ' . trim($r));
        if (!in_array((int)substr($r, 0, 3), $ok, true)) throw new RuntimeException('SMTP: ' . trim($r));
        return $r;
    };
    try {
        $read();
        $ehlo = 'radar.' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost');
        $cmd("EHLO $ehlo", [250]);
        if (!$ssl) {
            $cmd('STARTTLS', [220]);
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cmd("EHLO $ehlo", [250]);
        }
        if (cfg('SMTP_USER')) {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode(cfg('SMTP_USER')), [334], '[login]');
            $cmd(base64_encode((string)cfg('SMTP_PASSWORD', '')), [235], '[hasło]');
        }
        preg_match('/<([^>]+)>/', $from, $m);
        $cmd('MAIL FROM:<' . ($m[1] ?? $from) . '>', [250]);
        $cmd("RCPT TO:<$to>", [250, 251]);
        $cmd('DATA', [354]);
        $msg = "From: $from\r\nTo: <$to>\r\nSubject: $subject\r\nDate: " . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(12)) . "@$ehlo>\r\n"
             . "$mime\r\n\r\n" . $content;
        $cmd($msg . "\r\n.", [250], '[treść wiadomości]');
        $cmd('QUIT', [221]);
        fclose($fp);
        return true;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        mail_log('BŁĄD: ' . $e->getMessage());
        fclose($fp);
        return false;
    }
}

/* ===================================================================== rate limiting */

function rate_limit(string $key, int $limit, int $window): bool {
    $t = time();
    if ((int)qv('SELECT COUNT(*) FROM rate_events WHERE key = ? AND ts > ?', [$key, $t - $window]) >= $limit) return false;
    q('INSERT INTO rate_events(key, ts) VALUES (?, ?)', [$key, $t]);
    return true;
}

/* ===================================================================== proof-of-work */

function sign(string $payload): string { return substr(hash_hmac('sha256', $payload, secret()), 0, 32); }

function make_challenge(int $uid, int $diff): string {
    $payload = $uid . '.' . (time() + 600) . '.' . bin2hex(random_bytes(12)) . '.' . $diff;
    return $payload . '.' . sign($payload);
}
function leading_zero_bits(string $bin): int {
    $bits = 0;
    for ($i = 0, $n = strlen($bin); $i < $n; $i++) {
        $b = ord($bin[$i]);
        if ($b === 0) { $bits += 8; continue; }
        for ($m = 0x80; $m && !($b & $m); $m >>= 1) $bits++;
        break;
    }
    return $bits;
}
function verify_challenge(string $challenge, $nonce, int $uid): array {
    $parts = explode('.', $challenge);
    if (count($parts) !== 5 || !ctype_digit((string)$nonce)) return [false, 'Nieprawidłowe wyzwanie.'];
    [$cu, $exp, $rid, $diff, $sig] = $parts;
    if (!hash_equals(sign("$cu.$exp.$rid.$diff"), $sig)) return [false, 'Nieprawidłowy podpis wyzwania.'];
    if ((int)$cu !== $uid) return [false, 'Wyzwanie należy do innego użytkownika.'];
    if ((int)$exp < time()) return [false, 'Wyzwanie wygasło – spróbuj ponownie.'];
    if (leading_zero_bits(hash('sha256', "$challenge:" . (string)(int)$nonce, true)) < (int)$diff)
        return [false, 'Weryfikacja przeglądarki nie powiodła się.'];
    try { q('INSERT INTO used_challenges(id, expires_at) VALUES (?, ?)', [$rid, (int)$exp]); }
    catch (PDOException) { return [false, 'To wyzwanie zostało już użyte.']; }
    return [true, null];
}

/* ===================================================================== pułapki formularzy */

function form_timer(): string { $t = (string)time(); return $t . '.' . sign('form' . $t); }
function check_form_timer(string $tok, int $min = 3, int $max = 7200): bool {
    $p = explode('.', $tok);
    if (count($p) !== 2 || !hash_equals(sign('form' . $p[0]), $p[1])) return false;
    $age = time() - (int)$p[0];
    return $age >= $min && $age <= $max;
}
function turnstile_enabled(): bool { return cfg('TURNSTILE_SECRET') && cfg('TURNSTILE_SITE_KEY'); }
function verify_turnstile(?string $token): bool {
    if (!turnstile_enabled()) return true;
    if (!$token) return false;
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 8,
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query(['secret' => cfg('TURNSTILE_SECRET'), 'response' => $token, 'remoteip' => client_ip()])]]);
    $r = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    return $r && (json_decode($r, true)['success'] ?? false);
}
function bot_traps_ok(): bool {
    if (!empty($_POST['website'])) return false;                 // honeypot
    if (!check_form_timer((string)($_POST['ft'] ?? ''))) return false; // za szybko = bot
    return verify_turnstile($_POST['cf-turnstile-response'] ?? null);
}

/* ===================================================================== użytkownik i fazy kampanii */

function current_user(): ?array { return $GLOBALS['USER'] ?? null; }

function ts_of(?string $iso): ?int { return $iso ? (strtotime($iso) ?: null) : null; }
function voting_state(): string {
    $t = time(); $s = ts_of(cfg('VOTING_START')); $e = ts_of(cfg('VOTING_END'));
    if ($s && $t < $s) return 'before';
    if ($e && $t > $e) return 'after';
    return 'open';
}
function submissions_open(): bool { $e = ts_of(cfg('SUBMISSIONS_END')); return !$e || time() <= $e; }
function results_visible(): bool {
    $u = current_user();
    return cfg('SHOW_RESULTS', 'always') === 'always' || voting_state() === 'after' || ($u && $u['is_admin']);
}
function votes_left(): int {
    $u = current_user();
    if (!$u) return votes_per_user();
    return max(votes_per_user() - (int)qv('SELECT COUNT(*) FROM votes WHERE user_id = ?', [$u['id']]), 0);
}
function my_votes(): array {
    $u = current_user();
    if (!$u) return [];
    return array_flip(q("SELECT project_id FROM votes WHERE user_id = ? AND status != 'rejected'", [$u['id']])->fetchAll(PDO::FETCH_COLUMN));
}

/* ===================================================================== ocena ryzyka głosu
   Głos z wysokim wynikiem nie jest odrzucany jawnie: użytkownik widzi „głos przyjęty”,
   ale głos czeka na weryfikację moderatora i nie liczy się do wyników (shadow-hold). */

function score_vote(array $user, int $pid, ?string $subnet, ?string $fp, bool $webdriver): array {
    $t = time(); $score = 0; $reasons = [];
    $add = function (int $p, string $r) use (&$score, &$reasons) { $score += $p; $reasons[] = "+$p $r"; };

    if ($webdriver) $add(60, 'navigator.webdriver = true (przeglądarka sterowana automatem)');
    if (!$fp) $add(20, 'brak odcisku przeglądarki (JS wyłączony / skrypt)');
    if ($fp) {
        $n = (int)qv('SELECT COUNT(DISTINCT user_id) FROM votes WHERE fp_hash = ? AND project_id = ? AND user_id != ?', [$fp, $pid, $user['id']]);
        if ($n >= 1) $add(min(40 + 10 * ($n - 1), 80), "ten sam odcisk urządzenia u $n innych kont na ten projekt");
    }
    if ($subnet) {
        $accs = (int)qv('SELECT COUNT(*) FROM users WHERE subnet_hash = ? AND created_at > ?', [$subnet, $t - 86400]);
        if ($accs > 4) $add(min(10 + 5 * ($accs - 4), 40), "$accs nowych kont z tej podsieci w 24 h");
        $sv = (int)qv('SELECT COUNT(*) FROM votes WHERE subnet_hash = ? AND project_id = ? AND created_at > ?', [$subnet, $pid, $t - 3600]);
        if ($sv >= 3) $add(min(15 + 5 * ($sv - 3), 40), "$sv głosów na ten projekt z tej podsieci w 1 h");
    }
    if ($t - (int)$user['created_at'] < 120) $add(10, 'konto młodsze niż 2 min');

    $last10 = (int)qv('SELECT COUNT(*) FROM votes WHERE project_id = ? AND created_at > ?', [$pid, $t - 600]);
    $row = q1('SELECT COUNT(*) AS n, MIN(created_at) AS f FROM votes WHERE project_id = ?', [$pid]);
    if ($row['n'] && $row['f'] && $last10 >= 20) {
        $avg = $row['n'] / max(($t - $row['f']) / 600, 1);
        if ($last10 > 5 * $avg) $add(20, sprintf('nagły skok: %d głosów w 10 min (średnio %.1f)', $last10, $avg));
    }
    $domain = substr($user['email_norm'], strrpos($user['email_norm'], '@') + 1);
    $popular = ['gmail.com','wp.pl','o2.pl','onet.pl','interia.pl','op.pl','outlook.com','hotmail.com','yahoo.com',
                'icloud.com','gazeta.pl','proton.me','protonmail.com','tlen.pl','interia.eu','poczta.onet.pl'];
    if (!in_array($domain, $popular, true)) {
        $dv = (int)qv('SELECT COUNT(*) FROM votes v JOIN users u ON u.id = v.user_id WHERE v.project_id = ? AND v.created_at > ? AND u.email_norm LIKE ?',
                      [$pid, $t - 3600, '%@' . $domain]);
        if ($dv >= 10) $add(15, "$dv głosów z domeny @$domain w 1 h");
    }
    return [$score, $reasons];
}

function user_is_suspicious(?string $subnet): bool {
    return (int)qv('SELECT COUNT(*) FROM users WHERE subnet_hash = ? AND created_at > ?', [$subnet, time() - 86400]) > 4;
}
