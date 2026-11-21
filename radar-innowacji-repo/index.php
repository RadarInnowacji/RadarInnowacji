<?php
/* Radar Innowacji – front controller. Wszystkie adresy trafiają tutaj (patrz .htaccess). */
declare(strict_types=1);
require __DIR__ . '/lib/core.php';
require __DIR__ . '/lib/innovation.php';
require __DIR__ . '/lib/demo.php';
require __DIR__ . '/templates/_helpers.php';

error_reporting(E_ALL);
ini_set('display_errors', is_debug() ? '1' : '0');
date_default_timezone_set(cfg('TIMEZONE', 'Europe/Warsaw'));

/* ---------------------------------------------------------------- sesja i nagłówki */
$https = str_starts_with(base_url(), 'https');
session_name('radar_sid');
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
$sessDir = ROOT . '/data/sessions';
if (!is_dir($sessDir)) @mkdir($sessDir, 0700, true);
if (is_dir($sessDir) && is_writable($sessDir)) session_save_path($sessDir);
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$ts = turnstile_enabled() ? 'https://challenges.cloudflare.com' : '';
header("Content-Security-Policy: default-src 'self'; script-src 'self' $ts; frame-src " . ($ts ?: "'none'")
     . "; style-src 'self'; img-src 'self' data:; font-src 'self'; worker-src 'self'; connect-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'; object-src 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if ($https) header('Strict-Transport-Security: max-age=31536000');

/* ---------------------------------------------------------------- kontekst żądania */
$GLOBALS['USER'] = null;
if (!empty($_SESSION['uid'])) {
    $GLOBALS['USER'] = q1('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']]);
    if (!$GLOBALS['USER']) unset($_SESSION['uid']);
}
$IP = client_ip();
$IP_HASH = phash($IP);
$SUBNET_HASH = phash(subnet_of($IP));
$METHOD = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$PATH = '/' . trim(rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'), '/');
$IS_API = str_starts_with($PATH, '/api/');

if ($METHOD === 'POST') {
    $tok = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($tok) || !hash_equals($_SESSION['csrf'], $tok)) fail(400, 'Nieprawidłowy token formularza – odśwież stronę.');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if ($origin && parse_url($origin, PHP_URL_HOST) !== parse_url(base_url(), PHP_URL_HOST) && !is_debug())
        fail(400, 'Nieprawidłowe źródło żądania.');
}

if (random_int(1, 200) === 1) { // sprzątanie
    $t = time();
    q('DELETE FROM rate_events WHERE ts < ?', [$t - 172800]);
    q('DELETE FROM used_challenges WHERE expires_at < ?', [$t]);
    q('DELETE FROM login_tokens WHERE expires_at < ?', [$t - 86400]);
}

/* ---------------------------------------------------------------- widoki */
function render(string $tpl, array $vars = [], int $code = 200): never {
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    $vars += ['user' => current_user(), 'csrf' => $_SESSION['csrf'], 'title' => null];
    extract($vars);
    ob_start();
    include ROOT . "/templates/$tpl.php";
    $content = ob_get_clean();
    include ROOT . '/templates/layout.php';
    exit;
}
function fail(int $code, string $msg): never {
    global $IS_API;
    if ($IS_API) json_out(['error' => $msg], $code);
    render('error', ['code' => $code, 'message' => $msg], $code);
}
function require_login(): array {
    global $PATH;
    if (!current_user()) { flash('Zaloguj się, aby kontynuować.'); redirect('/logowanie?next=' . urlencode($PATH)); }
    return current_user();
}
function require_admin(): array {
    $u = current_user();
    if (!$u || !$u['is_admin']) fail(404, 'Nie znaleziono strony.');
    return $u;
}
function project_rows(string $where = "p.status = 'approved'", array $params = [], string $order = 'p.created_at DESC'): array {
    return q("SELECT p.*, (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id AND v.status = 'counted') AS votes
              FROM projects p WHERE $where ORDER BY $order", $params)->fetchAll();
}

/* ---------------------------------------------------------------- routing */
$m = [];
try {
    match (true) {
        $PATH === '/' => page_index(),
        (bool)preg_match('#^/projekt/(\d+)$#', $PATH, $m) => page_project((int)$m[1]),
        $PATH === '/wyniki' => page_ranking(),
        $METHOD === 'POST' && (bool)preg_match('#^/projekt/(\d+)/kontakt$#', $PATH, $m) => page_contact((int)$m[1]),
        $PATH === '/zapotrzebowania' => page_needs(),
        $PATH === '/zapotrzebowania/nowe' => page_need_new(),
        (bool)preg_match('#^/zapotrzebowanie/(\d+)$#', $PATH, $m) => page_need((int)$m[1]),
        $PATH === '/api/podobne' => api_similar(),
        $PATH === '/jak-oceniamy' => render('method', ['title' => 'Jak oceniamy innowacje']),
        $PATH === '/zasady' => render('rules', ['title' => 'Zasady']),
        $PATH === '/logowanie' => page_login(),
        $PATH === '/logowanie/wyslano' => page_login_sent(),
        (bool)preg_match('#^/auth/([A-Za-z0-9_\-]{20,80})$#', $PATH, $m) => page_auth($m[1]),
        $PATH === '/wyloguj' && $METHOD === 'POST' => (function () { session_destroy(); redirect('/'); })(),
        $PATH === '/zglos' => page_submit(),
        $PATH === '/moje' => page_mine(),
        $PATH === '/api/challenge' => api_challenge(),
        $PATH === '/api/vote' && $METHOD === 'POST' => api_vote(),
        $PATH === '/admin' => page_admin(),
        $PATH === '/test-poczty' => page_mail_test(),
        $PATH === '/admin/eksport.csv' => admin_export(),
        (bool)preg_match('#^/admin/ocena/(\d+)$#', $PATH, $m) => admin_assess((int)$m[1]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/projekt/(\d+)/etap$#', $PATH, $m) => admin_pipeline((int)$m[1]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/potrzeba/(\d+)/(zatwierdz|odrzuc)$#', $PATH, $m) => admin_need((int)$m[1], $m[2]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/demo/(wczytaj|usun)$#', $PATH, $m) => admin_demo($m[1]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/projekt/(\d+)/usun$#', $PATH, $m) => admin_delete_project((int)$m[1]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/potrzeba/(\d+)/usun$#', $PATH, $m) => admin_delete_need((int)$m[1]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/projekt/(\d+)/(zatwierdz|odrzuc|cofnij)$#', $PATH, $m) => admin_project((int)$m[1], $m[2]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/glos/(\d+)/(zatwierdz|odrzuc)$#', $PATH, $m) => admin_vote((int)$m[1], $m[2]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/grupa/(zatwierdz|odrzuc)$#', $PATH, $m) => admin_group($m[1]),
        $METHOD === 'POST' && (bool)preg_match('#^/admin/uzytkownik/(\d+)/blokuj$#', $PATH, $m) => admin_ban((int)$m[1]),
        default => fail(404, 'Nie znaleziono strony.'),
    };
} catch (Throwable $e) {
    error_log((string)$e);
    if (db()->inTransaction()) db()->rollBack();
    fail(500, is_debug() ? $e->getMessage() : 'Wystąpił błąd serwera. Spróbuj ponownie za chwilę.');
}

/* ================================================================ strony publiczne */

function page_index(): never {
    $q = trim((string)($_GET['q'] ?? '')); $cat = (string)($_GET['kategoria'] ?? ''); $sort = (string)($_GET['sort'] ?? 'potencjal');
    $type = (string)($_GET['typ'] ?? ''); $area = (string)($_GET['obszar'] ?? ''); $stage = (string)($_GET['etap'] ?? '');
    $where = ["p.status = 'approved'"]; $params = [];
    if ($q !== '') { $where[] = '(p.title LIKE ? OR p.institution LIKE ? OR p.location LIKE ? OR p.summary LIKE ? OR p.problem LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%"); }
    if (in_array($cat, CATEGORIES, true)) { $where[] = 'p.category = ?'; $params[] = $cat; }
    if (isset(INNO_TYPES[$type])) { $where[] = 'p.innovation_type = ?'; $params[] = $type; }
    if (isset(STAGES[$stage])) { $where[] = 'p.stage = ?'; $params[] = $stage; }
    if (in_array($area, AREAS, true)) { $where[] = "(',' || p.areas || ',') LIKE ?"; $params[] = "%,$area,%"; }
    $order = $sort === 'popularne' && results_visible() ? 'votes DESC, p.created_at' : ($sort === 'az' ? 'p.title COLLATE NOCASE' : 'p.created_at DESC');
    $projects = project_rows(implode(' AND ', $where), $params, $order);
    foreach ($projects as &$p) { $ev = evaluate($p); $p['total'] = $ev['total']; $p['path'] = recommend($p, $ev)[0]['name'] ?? ''; }
    unset($p);
    if ($sort === 'potencjal') usort($projects, fn($a, $b) => $b['total'] <=> $a['total']);
    $totals = q1("SELECT (SELECT COUNT(*) FROM projects WHERE status='approved') AS projects,
                         (SELECT COUNT(*) FROM needs WHERE status='approved') AS needs,
                         (SELECT COUNT(*) FROM contact_requests) AS contacts,
                         (SELECT COUNT(DISTINCT lower(location)) FROM projects WHERE status='approved') AS places");
    render('index', ['projects' => $projects, 'q' => $q, 'cat' => $cat, 'type' => $type, 'area' => $area, 'stage' => $stage,
                     'sort' => $sort, 'voted' => my_votes(), 'votes_left' => votes_left(), 'totals' => $totals, 'hero' => true]);
}

function page_project(int $id): never {
    $p = q1("SELECT p.*, (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id AND v.status = 'counted') AS votes FROM projects p WHERE id = ?", [$id]);
    $u = current_user();
    if (!$p || ($p['status'] !== 'approved' && !($u && ($u['is_admin'] || $u['id'] == $p['submitted_by'])))) fail(404, 'Nie znaleziono projektu.');
    $ev = evaluate($p);
    render('project', ['p' => $p, 'ev' => $ev, 'paths' => recommend($p, $ev), 'similar' => similar_projects($p, 4),
                       'needs' => can_see_project_needs($p) ? matching_needs($p, 4) : null, 'ext' => external_sources($p), 'voted' => isset(my_votes()[$id]),
                       'votes_left' => votes_left(), 'title' => $p['title'],
                       'contacts' => (int)qv('SELECT COUNT(*) FROM contact_requests WHERE project_id = ?', [$id])]);
}

function page_ranking(): never {
    $rows = project_rows();
    foreach ($rows as &$p) { $ev = evaluate($p); $p['total'] = $ev['total']; $p['experts'] = $ev['experts']; $p['path'] = recommend($p, $ev)[0]['name'] ?? ''; }
    unset($p);
    $by = ($_GET['wg'] ?? 'potencjal') === 'glosy' && results_visible() ? 'glosy' : 'potencjal';
    usort($rows, fn($a, $b) => $by === 'glosy' ? $b['votes'] <=> $a['votes'] : $b['total'] <=> $a['total']);
    render('results', ['projects' => $rows, 'by' => $by, 'title' => 'Ranking']);
}

/* „Jestem zainteresowany” – kontakt z twórcami bez ujawniania ich adresu publicznie */
function page_contact(int $id): never {
    global $SUBNET_HASH;
    $u = require_login();
    $p = q1("SELECT * FROM projects WHERE id = ? AND status = 'approved'", [$id]) ?? fail(404, 'Nie znaleziono projektu.');
    $role = (string)($_POST['role'] ?? ''); $org = trim(mb_substr((string)($_POST['organization'] ?? ''), 0, 160));
    $msg = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 2000));
    $roles = ['wdrozenie' => 'Chcę wdrożyć to rozwiązanie u siebie', 'partner' => 'Chcę współpracować / rozwijać', 'inwestor' => 'Mogę sfinansować / zainwestować',
              'mentor' => 'Mogę pomóc jako ekspert / mentor', 'podobne' => 'Pracujemy nad podobnym rozwiązaniem'];
    if (!isset($roles[$role]) || mb_strlen($msg) < 20) { flash('Wybierz rodzaj zainteresowania i napisz wiadomość (min. 20 znaków).', 'error'); redirect("/projekt/$id#kontakt"); }
    if (!empty($_POST['website']) || !rate_limit("contact:{$u['id']}", 5, 86400) || !rate_limit("contact-net:$SUBNET_HASH", 20, 86400)) {
        flash('Limit wiadomości na dziś został wyczerpany.', 'error'); redirect("/projekt/$id#kontakt");
    }
    q('INSERT INTO contact_requests(project_id, user_id, role, organization, message, created_at) VALUES (?,?,?,?,?,?)', [$id, $u['id'], $role, $org, $msg, time()]);
    if ($p['pipeline'] === 'oceniona' || $p['pipeline'] === 'ocena') q("UPDATE projects SET pipeline = 'laczenie' WHERE id = ?", [$id]);
    audit('contact_request', ['project_id' => $id, 'role' => $role]);
    send_mail($p['contact_email'], 'Ktoś jest zainteresowany Twoją innowacją: ' . $p['title'],
        "Dzień dobry,\n\nna platformie " . site_name() . " ktoś zgłosił zainteresowanie innowacją „{$p['title']}”.\n\n"
        . "Rodzaj zainteresowania: {$roles[$role]}\n" . ($org ? "Organizacja: $org\n" : '') . "Kontakt: {$u['email']}\n\nWiadomość:\n$msg\n\n"
        . "Odpowiedz bezpośrednio na adres: {$u['email']}\n\n" . site_name());
    flash('Wiadomość wysłana do twórców. Odpowiedzą bezpośrednio na Twój adres e-mail.', 'ok');
    redirect("/projekt/$id");
}

/* ================================================================ zapotrzebowania (kto czego szuka) */

function page_needs(): never {
    $u = current_user();
    $mine = $u ? q('SELECT * FROM needs WHERE submitted_by = ? ORDER BY created_at DESC', [$u['id']])->fetchAll() : [];
    foreach ($mine as &$n) $n['matches'] = $n['status'] === 'approved' ? matching_projects_for_need($n, 3) : [];
    unset($n);
    $all = ($u && $u['is_admin']) ? q("SELECT * FROM needs WHERE submitted_by != ? ORDER BY created_at DESC", [$u['id']])->fetchAll() : [];
    foreach ($all as &$n) $n['matches'] = $n['status'] === 'approved' ? matching_projects_for_need($n, 3) : [];
    unset($n);
    render('needs', ['mine' => $mine, 'all' => $all, 'for_me' => $u ? needs_for_creator((int)$u['id']) : [], 'title' => 'Szukam rozwiązania']);
}

function page_need(int $id): never {
    global $METHOD, $SUBNET_HASH;
    $n = q1('SELECT * FROM needs WHERE id = ?', [$id]);
    if (!$n || !can_view_need($n)) {
        if (!current_user()) { flash('Zaloguj się, aby zobaczyć to zapotrzebowanie.'); redirect('/logowanie?next=' . urlencode("/zapotrzebowanie/$id")); }
        fail(404, 'Nie znaleziono zapotrzebowania.');
    }
    $u = current_user();
    $isOwner = (int)$u['id'] === (int)$n['submitted_by'];
    $myMatches = needs_for_creator((int)$u['id'])[$id]['projects'] ?? [];

    if ($METHOD === 'POST') { // twórca odpowiada: „mamy takie rozwiązanie”
        $msg = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 2000));
        $pid = (int)($_POST['project_id'] ?? 0);
        if (!$myMatches || !in_array($pid, array_column($myMatches, 'id'), true)) fail(403, 'Możesz odpowiedzieć tylko pasującą innowacją.');
        if (mb_strlen($msg) < 20) { flash('Napisz wiadomość (min. 20 znaków).', 'error'); redirect("/zapotrzebowanie/$id"); }
        if (!rate_limit("needresp:{$u['id']}", 5, 86400) || !rate_limit("needresp-net:$SUBNET_HASH", 20, 86400)) { flash('Limit wiadomości na dziś został wyczerpany.', 'error'); redirect("/zapotrzebowanie/$id"); }
        q('INSERT INTO need_responses(need_id, user_id, project_id, message, created_at) VALUES (?,?,?,?,?)', [$id, $u['id'], $pid, $msg, time()]);
        $p = q1('SELECT title FROM projects WHERE id = ?', [$pid]);
        audit('need_response', ['need_id' => $id, 'project_id' => $pid]);
        if (!$n['is_demo'])
            send_mail($n['contact_email'], 'Odpowiedź na Wasze zapotrzebowanie: ' . $n['title'],
                "Dzień dobry,\n\ntwórcy innowacji „{$p['title']}” odpowiedzieli na Wasze zapotrzebowanie „{$n['title']}” na platformie " . site_name() . ".\n\n"
                . "Wiadomość:\n$msg\n\nKarta innowacji: " . base_url() . "/projekt/$pid\nKontakt: {$u['email']}\n\n" . site_name());
        flash('Wiadomość wysłana. Organizacja odpowie bezpośrednio na Twój adres e-mail.', 'ok');
        redirect("/zapotrzebowanie/$id");
    }
    render('need', ['n' => $n, 'is_owner' => $isOwner, 'my_matches' => $myMatches,
                    'matches' => ($isOwner || $u['is_admin']) ? matching_projects_for_need($n, 6) : [],
                    'responses' => ($isOwner || $u['is_admin']) ? q('SELECT r.*, p.title AS ptitle, u.email FROM need_responses r LEFT JOIN projects p ON p.id = r.project_id JOIN users u ON u.id = r.user_id WHERE need_id = ? ORDER BY r.created_at DESC', [$id])->fetchAll() : [],
                    'title' => $n['title']]);
}

function page_need_new(): never {
    global $METHOD, $SUBNET_HASH;
    $u = require_login();
    if ($METHOD !== 'POST') render('need_new', ['form' => [], 'title' => 'Dodaj zapotrzebowanie']);
    $f = [];
    foreach (['org_name', 'org_type', 'title', 'description', 'contact_email'] as $k) $f[$k] = trim((string)($_POST[$k] ?? ''));
    $f['areas'] = implode(',', array_values(array_intersect(AREAS, (array)($_POST['areas'] ?? []))));
    $f['offer'] = implode(',', array_values(array_intersect(array_keys(OFFERS), (array)($_POST['offer'] ?? []))));
    $errors = [];
    if (mb_strlen($f['org_name']) < 3) $errors[] = 'Podaj nazwę organizacji.';
    if (!in_array($f['org_type'], ORG_TYPES, true)) $errors[] = 'Wybierz rodzaj organizacji.';
    if (mb_strlen($f['title']) < 10 || mb_strlen($f['title']) > 160) $errors[] = 'Tytuł: od 10 do 160 znaków.';
    if (mb_strlen($f['description']) < 50 || mb_strlen($f['description']) > 3000) $errors[] = 'Opis: od 50 do 3000 znaków.';
    if (check_email($f['contact_email'])) $errors[] = 'Podaj poprawny e-mail kontaktowy.';
    if (!$errors && !bot_traps_ok()) $errors[] = 'Weryfikacja antyspamowa nie powiodła się – odczekaj chwilę i wyślij ponownie.';
    if (!$errors && !(rate_limit("need-user:{$u['id']}", 3, 86400) && rate_limit("need-net:$SUBNET_HASH", 15, 86400))) $errors[] = 'Limit zgłoszeń na dziś został wyczerpany.';
    if ($errors) { foreach ($errors as $e) flash($e, 'error'); render('need_new', ['form' => $f, 'title' => 'Dodaj zapotrzebowanie'], 400); }
    q('INSERT INTO needs(org_name, org_type, title, description, areas, offer, contact_email, submitted_by, created_at) VALUES (?,?,?,?,?,?,?,?,?)',
      [$f['org_name'], $f['org_type'], $f['title'], $f['description'], $f['areas'], $f['offer'], $f['contact_email'], $u['id'], time()]);
    audit('need_submitted', ['title' => $f['title']]);
    foreach (admin_emails() as $adm)
        send_mail($adm, 'Nowe zapotrzebowanie do moderacji: ' . $f['title'], "{$f['org_name']} ({$f['org_type']}) szuka:\n{$f['title']}\n\n{$f['description']}\n\nPanel: " . base_url() . "/admin\n\n" . site_name());
    flash('Dziękujemy! Zapotrzebowanie trafiło do moderacji. Po publikacji pokażemy Ci pasujące innowacje – widzisz je tylko Ty i ich twórcy.', 'ok');
    redirect('/zapotrzebowania');
}

/* Podpowiedź podobnych innowacji w trakcie wypełniania formularza (ograniczanie dublowania prac) */
function api_similar(): never {
    global $IP_HASH;
    if (!rate_limit("sim:$IP_HASH", 60, 600)) json_out(['error' => 'Zbyt wiele zapytań.'], 429);
    $text = mb_substr((string)($_GET['text'] ?? ''), 0, 4000);
    if (mb_strlen(trim($text)) < 15) json_out(['items' => []]);
    $out = [];
    foreach (similar_to_text($text, 'project', 4, null, 0.15) as $r)
        $out[] = ['id' => $r['id'], 'title' => $r['title'], 'institution' => $r['row']['institution'], 'score' => (int)round($r['score'] * 100)];
    json_out(['items' => $out]);
}

/* ================================================================ logowanie linkiem */

function page_login(): never {
    global $METHOD, $IP_HASH, $SUBNET_HASH;
    $next = safe_next($_REQUEST['next'] ?? '/');
    if ($METHOD === 'POST') {
        $email = trim((string)($_POST['email'] ?? ''));
        $err = check_email($email);
        if (!$err && !bot_traps_ok()) $err = 'Weryfikacja antyspamowa nie powiodła się. Odczekaj chwilę i spróbuj ponownie.';
        if ($err) { flash($err, 'error'); render('login', ['email' => $email, 'next' => $next, 'title' => 'Logowanie'], 400); }
        $norm = normalize_email($email);
        if (!(rate_limit("login-ip:$IP_HASH", 10, 3600) && rate_limit("login-net:$SUBNET_HASH", 40, 3600) && rate_limit("login-mail:$norm", 3, 3600))) {
            flash('Zbyt wiele prób logowania. Spróbuj ponownie za godzinę.', 'error');
            render('login', ['email' => $email, 'next' => $next, 'title' => 'Logowanie'], 429);
        }
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        q('INSERT INTO login_tokens(token_hash, email, email_norm, created_at, expires_at) VALUES (?,?,?,?,?)',
          [hash('sha256', $token), $email, $norm, time(), time() + 1800]);
        $link = base_url() . "/auth/$token?next=" . urlencode($next);
        $ok = send_mail($email, 'Logowanie – ' . site_name(),
            "Cześć!\n\nKliknij, aby się zalogować (link ważny 30 minut, jednorazowy):\n$link\n\nJeśli to nie Ty – zignoruj tę wiadomość.\n\n" . site_name());
        if (!$ok) { flash('Nie udało się wysłać e-maila. Spróbuj ponownie za chwilę.', 'error'); render('login', ['email' => $email, 'next' => $next], 500); }
        $_SESSION['dev_link'] = (is_debug() && !cfg('SMTP_HOST')) ? $link : null;
        redirect('/logowanie/wyslano');
    }
    render('login', ['email' => '', 'next' => $next, 'title' => 'Logowanie']);
}

function page_login_sent(): never {
    $dev = $_SESSION['dev_link'] ?? null; unset($_SESSION['dev_link']);
    render('login_sent', ['dev_link' => $dev, 'title' => 'Sprawdź skrzynkę']);
}

function page_auth(string $token): never {
    global $METHOD, $IP_HASH, $SUBNET_HASH;
    $th = hash('sha256', $token);
    $row = q1('SELECT * FROM login_tokens WHERE token_hash = ?', [$th]);
    if (!$row || $row['used_at'] || $row['expires_at'] < time()) { flash('Link wygasł lub został już użyty. Poproś o nowy.', 'error'); redirect('/logowanie'); }
    $next = safe_next($_REQUEST['next'] ?? '/');
    // GET pokazuje przycisk – skanery linków w poczcie nie zużyją tokenu
    if ($METHOD !== 'POST') render('auth', ['token' => $token, 'next' => $next, 'title' => 'Potwierdź logowanie']);

    $fp = substr((string)($_POST['fp'] ?? ''), 0, 128);
    $fpHash = $fp !== '' ? phash($fp) : null;
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    $st = q('UPDATE login_tokens SET used_at = ? WHERE token_hash = ? AND used_at IS NULL', [time(), $th]);
    if ($st->rowCount() !== 1) { $pdo->exec('ROLLBACK'); flash('Link został już użyty.', 'error'); redirect('/logowanie'); }
    $user = q1('SELECT * FROM users WHERE email_norm = ?', [$row['email_norm']]);
    $isAdmin = in_array(strtolower($row['email']), admin_emails(), true) || in_array($row['email_norm'], admin_emails(), true);
    if (!$user) {
        $accs = (int)qv('SELECT COUNT(*) FROM users WHERE subnet_hash = ? AND created_at > ?', [$SUBNET_HASH, time() - 86400]);
        if ($accs >= cfg_int('MAX_ACCOUNTS_PER_SUBNET_DAY', 60)) {
            $pdo->exec('ROLLBACK');
            flash('Z Twojej sieci założono dziś bardzo dużo kont. Spróbuj z innej sieci (np. dane komórkowe) lub napisz do organizatora.', 'error');
            redirect('/');
        }
        q('INSERT INTO users(email, email_norm, created_at, ip_hash, subnet_hash, fp_hash, is_admin) VALUES (?,?,?,?,?,?,?)',
          [$row['email'], $row['email_norm'], time(), $IP_HASH, $SUBNET_HASH, $fpHash, $isAdmin ? 1 : 0]);
        $user = q1('SELECT * FROM users WHERE email_norm = ?', [$row['email_norm']]);
    } elseif ($isAdmin && !$user['is_admin']) {
        q('UPDATE users SET is_admin = 1 WHERE id = ?', [$user['id']]);
    }
    $pdo->exec('COMMIT');
    session_regenerate_id(true);
    $_SESSION = ['uid' => (int)$user['id'], 'csrf' => bin2hex(random_bytes(32))];
    flash('Zalogowano.', 'ok');
    redirect($next);
}

/* ================================================================ zgłoszenia */

function page_submit(): never {
    global $METHOD, $SUBNET_HASH;
    $u = require_login();
    if (!submissions_open()) render('submit', ['closed' => true, 'form' => [], 'title' => 'Zgłoś innowację']);
    if ($METHOD !== 'POST') render('submit', ['closed' => false, 'form' => [], 'title' => 'Zgłoś innowację']);

    $f = [];
    foreach (['title','institution','category','location','summary','problem','description','results','impact','creators','link','contact_email',
              'innovation_type','stage','evidence','scope','cost','commercial'] as $k)
        $f[$k] = trim((string)($_POST[$k] ?? ''));
    $f['areas'] = implode(',', array_values(array_intersect(AREAS, (array)($_POST['areas'] ?? []))));
    $f['needs'] = implode(',', array_values(array_intersect(array_keys(NEEDS), (array)($_POST['needs'] ?? []))));
    $limits = ['title' => [5, 120, 'Nazwa innowacji'], 'institution' => [3, 160, 'Instytucja'], 'location' => [2, 80, 'Miejscowość'],
               'summary' => [20, 280, 'Opis w jednym zdaniu'], 'problem' => [30, 2000, 'Problem'], 'description' => [80, 5000, 'Na czym polega rozwiązanie'],
               'results' => [10, 2000, 'Efekty'], 'impact' => [20, 2000, 'Kto jeszcze może skorzystać'], 'creators' => [0, 300, 'Zespół']];
    $errors = [];
    foreach ($limits as $k => [$lo, $hi, $name]) {
        $len = mb_strlen($f[$k]);
        if ($len < $lo || $len > $hi) $errors[] = "$name: od $lo do $hi znaków.";
    }
    if (!in_array($f['category'], CATEGORIES, true)) $errors[] = 'Wybierz rodzaj instytucji.';
    foreach (['innovation_type' => INNO_TYPES, 'stage' => STAGES, 'evidence' => EVIDENCE, 'scope' => SCOPES, 'cost' => COSTS, 'commercial' => COMMERCIAL] as $k => $dict)
        if (!isset($dict[$f[$k]])) $errors[] = 'Uzupełnij wszystkie pytania w części „Ocena wstępna”.';
    if ($f['areas'] === '') $errors[] = 'Wybierz co najmniej jeden obszar.';
    if ($f['link'] !== '' && !preg_match('#^https?://#i', $f['link'])) $errors[] = 'Link musi zaczynać się od http:// lub https://';
    if (check_email($f['contact_email'])) $errors[] = 'Podaj poprawny e-mail kontaktowy.';
    if (empty($_POST['consent'])) $errors[] = 'Zaakceptuj zasady.';
    $errors = array_values(array_unique($errors));
    if (!$errors && !bot_traps_ok()) $errors[] = 'Weryfikacja antyspamowa nie powiodła się – odczekaj chwilę i wyślij ponownie.';
    if (!$errors && !(rate_limit("submit-user:{$u['id']}", 3, 86400) && rate_limit("submit-net:$SUBNET_HASH", 15, 86400)))
        $errors[] = 'Limit zgłoszeń na dziś został wyczerpany.';
    if ($errors) { foreach ($errors as $e) flash($e, 'error'); render('submit', ['closed' => false, 'form' => $f, 'title' => 'Zgłoś innowację'], 400); }

    q('INSERT INTO projects(title, institution, category, location, summary, problem, description, results, impact, creators, link, contact_email,
       innovation_type, stage, evidence, scope, cost, commercial, areas, needs, submitted_by, created_at)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
      [$f['title'], $f['institution'], $f['category'], $f['location'], $f['summary'], $f['problem'], $f['description'], $f['results'],
       $f['impact'], $f['creators'], $f['link'] ?: null, $f['contact_email'], $f['innovation_type'], $f['stage'], $f['evidence'],
       $f['scope'], $f['cost'], $f['commercial'], $f['areas'], $f['needs'], $u['id'], time()]);
    $newId = (int)db()->lastInsertId();
    audit('project_submitted', ['title' => $f['title']]);
    foreach (admin_emails() as $adm) {
        send_mail($adm, 'Nowa innowacja do moderacji: ' . $f['title'],
            "Nowa innowacja czeka na moderację.\n\nNazwa: {$f['title']}\nInstytucja: {$f['institution']} ({$f['location']})\n"
            . "Rodzaj: " . (INNO_TYPES_SHORT[$f['innovation_type']] ?? '') . "\nZgłaszający: {$u['email']}\n\n{$f['summary']}\n\n"
            . "Podgląd: " . base_url() . "/projekt/$newId\nPanel: " . base_url() . "/admin\n\n" . site_name());
    }
    flash('Dziękujemy! Innowacja trafiła do moderacji. Wstępną ocenę i rekomendację zobaczysz na jej karcie.', 'ok');
    redirect("/projekt/$newId");
}

function page_mine(): never {
    $u = require_login();
    render('mine', [
        'projects' => q('SELECT * FROM projects WHERE submitted_by = ? ORDER BY created_at DESC', [$u['id']])->fetchAll(),
        'votes' => q("SELECT v.created_at, p.id, p.title FROM votes v JOIN projects p ON p.id = v.project_id
                      WHERE v.user_id = ? AND v.status != 'rejected' ORDER BY v.created_at DESC", [$u['id']])->fetchAll(),
        'needs' => q('SELECT * FROM needs WHERE submitted_by = ? ORDER BY created_at DESC', [$u['id']])->fetchAll(),
        'for_me' => needs_for_creator((int)$u['id']),
        'votes_left' => votes_left(), 'title' => 'Moje konto']);
}

/* ================================================================ głosowanie (API) */

function vote_precheck(int $pid): void {
    $u = current_user();
    if (!$u) json_out(['error' => 'Zaloguj się, aby głosować.'], 401);
    if ($u['is_banned']) json_out(['error' => 'Twoje konto zostało zablokowane.'], 403);
    $st = voting_state();
    if ($st !== 'open') json_out(['error' => $st === 'before' ? 'Głosowanie jeszcze się nie rozpoczęło.' : 'Głosowanie zostało zakończone.'], 403);
    $minAge = cfg_int('MIN_ACCOUNT_AGE_MINUTES', 0);
    if ($minAge && time() - $u['created_at'] < $minAge * 60) json_out(['error' => "Głosować można $minAge min po założeniu konta."], 403);
    if (!q1("SELECT id FROM projects WHERE id = ? AND status = 'approved'", [$pid])) json_out(['error' => 'Nie ma takiego projektu.'], 404);
    if (q1('SELECT 1 FROM votes WHERE user_id = ? AND project_id = ?', [$u['id'], $pid])) json_out(['error' => 'Już głosowałeś/aś na ten projekt.'], 409);
    if (votes_left() <= 0) json_out(['error' => 'Wykorzystano wszystkie głosy (' . votes_per_user() . ').'], 409);
}

function api_challenge(): never {
    global $SUBNET_HASH;
    $pid = (int)($_GET['project_id'] ?? 0);
    vote_precheck($pid);
    $u = current_user();
    if (!rate_limit("chal:{$u['id']}", 20, 600)) json_out(['error' => 'Zbyt wiele prób. Odczekaj kilka minut.'], 429);
    $diff = user_is_suspicious($SUBNET_HASH) ? cfg_int('POW_DIFFICULTY_SUSPICIOUS', 21) : cfg_int('POW_DIFFICULTY', 18);
    json_out(['challenge' => make_challenge((int)$u['id'], $diff), 'difficulty' => $diff]);
}

function api_vote(): never {
    global $IP_HASH, $SUBNET_HASH;
    $data = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $pid = (int)($data['project_id'] ?? 0);
    vote_precheck($pid);
    $u = current_user();
    if (!(rate_limit("vote-user:{$u['id']}", 10, 60) && rate_limit("vote-ip:$IP_HASH", 60, 3600)))
        json_out(['error' => 'Zbyt wiele prób. Odczekaj chwilę.'], 429);
    [$ok, $msg] = verify_challenge((string)($data['challenge'] ?? ''), (string)($data['nonce'] ?? ''), (int)$u['id']);
    if (!$ok) json_out(['error' => $msg], 400);

    $fp = substr((string)($data['fp'] ?? ''), 0, 128);
    $fpHash = $fp !== '' ? phash($fp) : null;
    [$score, $reasons] = score_vote($u, $pid, $SUBNET_HASH, $fpHash, !empty($data['wd']));
    $status = $score >= cfg_int('RISK_THRESHOLD', 50) ? 'pending' : 'counted';

    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE'); // blokada zapisu – brak wyścigu przy limicie głosów
    try {
        if ((int)qv('SELECT COUNT(*) FROM votes WHERE user_id = ?', [$u['id']]) >= votes_per_user()) {
            $pdo->exec('ROLLBACK'); json_out(['error' => 'Wykorzystano wszystkie głosy.'], 409);
        }
        q('INSERT INTO votes(user_id, project_id, created_at, ip_hash, subnet_hash, fp_hash, ua_hash, risk_score, risk_reasons, status)
           VALUES (?,?,?,?,?,?,?,?,?,?)',
          [$u['id'], $pid, time(), $IP_HASH, $SUBNET_HASH, $fpHash, phash($_SERVER['HTTP_USER_AGENT'] ?? ''), $score,
           json_encode($reasons, JSON_UNESCAPED_UNICODE), $status]);
        $pdo->exec('COMMIT');
    } catch (PDOException) {
        $pdo->exec('ROLLBACK');
        json_out(['error' => 'Już głosowałeś/aś na ten projekt.'], 409);
    }
    // Ta sama odpowiedź dla głosu zaliczonego i wstrzymanego – bot nie wie, że został wyłapany.
    json_out(['ok' => true, 'message' => 'Dziękujemy, głos został przyjęty!', 'votes_left' => votes_left()]);
}

/* ================================================================ test poczty
   https://twojadomena.pl/test-poczty?k=PIERWSZE_16_ZNAKOW_SECRET_KEY&do=adres@email.pl
   Wysyła testowy mail i pokazuje szczegóły – do diagnozy, gdy maile nie dochodzą. */

function page_mail_test(): never {
    if (!hash_equals(substr(secret(), 0, 16), (string)($_GET['k'] ?? ''))) fail(404, 'Nie znaleziono strony.');
    header('Content-Type: text/plain; charset=utf-8');
    $to = (string)($_GET['do'] ?? (admin_emails()[0] ?? ''));
    echo "TEST POCZTY – " . site_name() . "\n========================\n";
    echo 'PHP: ' . PHP_VERSION . "\n";
    echo 'Tryb: ' . (cfg('SMTP_HOST') ? 'SMTP (' . cfg('SMTP_HOST') . ':' . cfg_int('SMTP_PORT', 465) . ')' : 'wbudowana funkcja mail() serwera') . "\n";
    echo 'Nadawca: ' . cfg('SMTP_FROM', '(domyślny)') . "\n";
    echo "Odbiorca: $to\n";
    echo 'ADMIN_EMAILS: ' . implode(', ', admin_emails()) . "\n\n";
    if (check_email($to)) { echo "Podaj poprawny adres w parametrze &do=\n"; exit; }
    $GLOBALS['MAIL_LOG'] = [];
    $ok = send_mail($to, 'Test poczty – ' . site_name(), "To jest testowa wiadomość.\nJeśli ją widzisz, wysyłka maili działa.\n\n" . date('d.m.Y H:i:s'));
    foreach ($GLOBALS['MAIL_LOG'] as $l) echo "  $l\n";
    echo "\nWYNIK: " . ($ok ? "serwer przyjął wiadomość do wysłania.\nSprawdź skrzynkę ORAZ folder SPAM (może dojść z opóźnieniem do kilku minut).\nJeśli nie doszła w 10 min, przełącz na SMTP w pliku .env." : "wysyłka NIE powiodła się – szczegóły powyżej.") . "\n";
    exit;
}

/* ================================================================ panel administratora */

function page_admin(): never {
    require_admin();
    render('admin', [
        'stats' => q1("SELECT (SELECT COUNT(*) FROM users) AS users,
                              (SELECT COUNT(*) FROM projects WHERE status='pending') AS p_pending,
                              (SELECT COUNT(*) FROM projects WHERE status='approved') AS p_approved,
                              (SELECT COUNT(*) FROM votes WHERE status='counted') AS v_counted,
                              (SELECT COUNT(*) FROM votes WHERE status='pending') AS v_pending,
                              (SELECT COUNT(*) FROM votes WHERE status='rejected') AS v_rejected"),
        'pending_projects' => q("SELECT p.*, u.email AS submitter FROM projects p LEFT JOIN users u ON u.id = p.submitted_by
                                 WHERE p.status = 'pending' ORDER BY p.created_at")->fetchAll(),
        'groups' => q("SELECT v.subnet_hash, v.project_id, p.title, COUNT(*) AS n, MIN(v.created_at) AS first, MAX(v.created_at) AS last,
                              ROUND(AVG(v.risk_score)) AS avg_score, COUNT(DISTINCT v.fp_hash) AS fps
                       FROM votes v JOIN projects p ON p.id = v.project_id WHERE v.status = 'pending'
                       GROUP BY v.subnet_hash, v.project_id ORDER BY n DESC LIMIT 100")->fetchAll(),
        'pending_votes' => q("SELECT v.*, u.email, p.title FROM votes v JOIN users u ON u.id = v.user_id JOIN projects p ON p.id = v.project_id
                              WHERE v.status = 'pending' ORDER BY v.created_at DESC LIMIT 300")->fetchAll(),
        'log' => q('SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.id DESC LIMIT 50')->fetchAll(),
        'pending_needs' => q("SELECT * FROM needs WHERE status = 'pending' ORDER BY created_at")->fetchAll(),
        'published_needs' => q("SELECT * FROM needs WHERE status = 'approved' ORDER BY created_at DESC")->fetchAll(),
        'to_assess' => q("SELECT p.id, p.title, p.institution, p.pipeline, p.is_demo, (SELECT COUNT(*) FROM assessments a WHERE a.project_id = p.id) AS n_ass
                          FROM projects p WHERE p.status = 'approved' ORDER BY n_ass, p.created_at")->fetchAll(),
        'contacts' => q('SELECT c.*, p.title, u.email FROM contact_requests c JOIN projects p ON p.id = c.project_id JOIN users u ON u.id = c.user_id
                         ORDER BY c.created_at DESC LIMIT 50')->fetchAll(),
        'demo_count' => (int)qv('SELECT COUNT(*) FROM projects WHERE is_demo = 1'),
        'need_responses' => q('SELECT r.*, n.title AS ntitle, p.title AS ptitle, u.email FROM need_responses r JOIN needs n ON n.id = r.need_id
                               LEFT JOIN projects p ON p.id = r.project_id JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC LIMIT 50')->fetchAll(),
        'threshold' => cfg_int('RISK_THRESHOLD', 50), 'title' => 'Panel moderatora']);
}

function admin_project(int $pid, string $action): never {
    $u = require_admin();
    $status = ['zatwierdz' => 'approved', 'odrzuc' => 'rejected', 'cofnij' => 'pending'][$action];
    q('UPDATE projects SET status = ?, reviewed_at = ?, reviewed_by = ?, review_note = ? WHERE id = ?',
      [$status, time(), $u['id'], mb_substr((string)($_POST['note'] ?? ''), 0, 500), $pid]);
    if ($status === 'approved') q("UPDATE projects SET pipeline = 'ocena' WHERE id = ? AND pipeline = 'zgloszona'", [$pid]);
    audit("project_$status", ['project_id' => $pid]);
    // Poinformuj zgłaszającego o decyzji
    $p = q1('SELECT p.title, p.review_note, p.is_demo, u.email FROM projects p LEFT JOIN users u ON u.id = p.submitted_by WHERE p.id = ?', [$pid]);
    if ($p && $p['is_demo']) $p['email'] = null;
    if ($p && $p['email'] && $status !== 'pending') {
        $body = $status === 'approved'
            ? "Twój projekt „{$p['title']}” został zatwierdzony i jest już widoczny na radarze:\n" . base_url() . "/projekt/$pid\n\nPodziel się linkiem – każdy może oddać na niego głos."
            : "Twój projekt „{$p['title']}” nie został opublikowany." . ($p['review_note'] ? "\n\nUzasadnienie: {$p['review_note']}" : '');
        send_mail($p['email'], ($status === 'approved' ? 'Projekt opublikowany: ' : 'Decyzja w sprawie projektu: ') . $p['title'], $body . "\n\n" . site_name());
    }
    flash("Projekt #$pid: " . ['approved' => 'zatwierdzony', 'rejected' => 'odrzucony', 'pending' => 'cofnięty do moderacji'][$status] . '.', 'ok');
    redirect('/admin');
}

function admin_assess(int $pid): never {
    global $METHOD;
    $u = require_admin();
    $p = q1("SELECT p.*, (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id AND v.status = 'counted') AS votes FROM projects p WHERE id = ?", [$pid]) ?? fail(404, 'Nie znaleziono projektu.');
    if ($METHOD === 'POST') {
        $scores = [];
        foreach (EXPERT_CRITERIA as $c) { $v = (int)($_POST["c_$c"] ?? 0); if ($v >= 1 && $v <= 5) $scores[$c] = $v; }
        if (count($scores) !== count(EXPERT_CRITERIA)) { flash('Oceń wszystkie kryteria w skali 1–5.', 'error'); redirect("/admin/ocena/$pid"); }
        q('INSERT INTO assessments(project_id, assessor_id, scores, comment, created_at) VALUES (?,?,?,?,?)
           ON CONFLICT(project_id, assessor_id) DO UPDATE SET scores = excluded.scores, comment = excluded.comment, created_at = excluded.created_at',
          [$pid, $u['id'], json_encode($scores), mb_substr(trim((string)($_POST['comment'] ?? '')), 0, 2000), time()]);
        q("UPDATE projects SET pipeline = 'oceniona' WHERE id = ? AND pipeline IN ('zgloszona','ocena')", [$pid]);
        audit('project_assessed', ['project_id' => $pid, 'scores' => $scores]);
        flash('Ocena zapisana. Rekomendacja została przeliczona.', 'ok');
        redirect("/projekt/$pid#ocena");
    }
    $mine = q1('SELECT * FROM assessments WHERE project_id = ? AND assessor_id = ?', [$pid, $u['id']]);
    $ev = evaluate($p);
    render('assess', ['p' => $p, 'ev' => $ev, 'mine' => $mine ? json_decode($mine['scores'], true) : null,
                      'mine_comment' => $mine['comment'] ?? '', 'title' => 'Ocena: ' . $p['title']]);
}

function admin_pipeline(int $pid): never {
    require_admin();
    $st = (string)($_POST['pipeline'] ?? '');
    if (!isset(PIPELINE[$st])) fail(400, 'Nieprawidłowy etap.');
    q('UPDATE projects SET pipeline = ? WHERE id = ?', [$st, $pid]);
    audit('pipeline_changed', ['project_id' => $pid, 'pipeline' => $st]);
    flash('Etap zaktualizowany: ' . PIPELINE[$st][0] . '.', 'ok');
    redirect("/projekt/$pid");
}

function admin_need(int $id, string $action): never {
    require_admin();
    $status = $action === 'zatwierdz' ? 'approved' : 'rejected';
    q('UPDATE needs SET status = ? WHERE id = ?', [$status, $id]);
    audit("need_$status", ['need_id' => $id]);
    flash('Zapotrzebowanie ' . ($status === 'approved' ? 'opublikowane.' : 'odrzucone.'), 'ok');
    redirect('/admin');
}

function admin_delete_project(int $pid): never {
    require_admin();
    $p = q1('SELECT title FROM projects WHERE id = ?', [$pid]) ?? fail(404, 'Nie znaleziono innowacji.');
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    $votes = q('DELETE FROM votes WHERE project_id = ?', [$pid])->rowCount();
    q('DELETE FROM assessments WHERE project_id = ?', [$pid]);
    q('DELETE FROM contact_requests WHERE project_id = ?', [$pid]);
    q('UPDATE need_responses SET project_id = NULL WHERE project_id = ?', [$pid]);
    q('DELETE FROM projects WHERE id = ?', [$pid]);
    $pdo->exec('COMMIT');
    audit('project_deleted', ['project_id' => $pid, 'title' => $p['title'], 'votes' => $votes]);
    flash("Usunięto innowację „{$p['title']}”. Razem z nią usunięto głosy ($votes), oceny i zgłoszenia kontaktu.", 'ok');
    redirect('/admin');
}

function admin_delete_need(int $id): never {
    require_admin();
    $n = q1('SELECT title FROM needs WHERE id = ?', [$id]) ?? fail(404, 'Nie znaleziono zapotrzebowania.');
    q('DELETE FROM need_responses WHERE need_id = ?', [$id]);
    q('DELETE FROM needs WHERE id = ?', [$id]);
    audit('need_deleted', ['need_id' => $id, 'title' => $n['title']]);
    flash("Usunięto zapotrzebowanie „{$n['title']}”.", 'ok');
    redirect('/admin');
}

function admin_demo(string $action): never {
    $u = require_admin();
    if ($action === 'wczytaj') {
        if ((int)qv('SELECT COUNT(*) FROM projects WHERE is_demo = 1')) { flash('Dane przykładowe są już wczytane.', 'info'); redirect('/admin'); }
        $n = demo_load((int)$u['id']);
        audit('demo_loaded', ['count' => $n]);
        flash("Wczytano $n przykładowych wpisów (25 innowacji i zapotrzebowania) wraz z przykładowymi głosami, ocenami i kontaktami. Wszystko jest oznaczone etykietą „Przykład”.", 'ok');
    } else {
        $n = demo_remove();
        audit('demo_removed', ['count' => $n]);
        flash("Usunięto $n przykładowych wpisów wraz z przykładowymi kontami, głosami i ocenami.", 'ok');
    }
    redirect('/admin');
}

function admin_vote(int $vid, string $action): never {
    require_admin();
    $status = $action === 'zatwierdz' ? 'counted' : 'rejected';
    q('UPDATE votes SET status = ? WHERE id = ?', [$status, $vid]);
    audit("vote_$status", ['vote_id' => $vid]);
    redirect('/admin#glosy');
}

function admin_group(string $action): never {
    require_admin();
    $status = $action === 'zatwierdz' ? 'counted' : 'rejected';
    $subnet = ($_POST['subnet'] ?? '') ?: null; $pid = (int)($_POST['project_id'] ?? 0);
    $st = q("UPDATE votes SET status = ? WHERE status = 'pending' AND subnet_hash IS ? AND project_id = ?", [$status, $subnet, $pid]);
    audit("vote_group_$status", ['project_id' => $pid, 'count' => $st->rowCount()]);
    flash('Zmieniono ' . $st->rowCount() . ' głosów na: ' . ($status === 'counted' ? 'zaliczone' : 'odrzucone') . '.', 'ok');
    redirect('/admin#glosy');
}

function admin_ban(int $uid): never {
    require_admin();
    q('UPDATE users SET is_banned = 1 WHERE id = ?', [$uid]);
    $n = q("UPDATE votes SET status = 'rejected' WHERE user_id = ?", [$uid])->rowCount();
    audit('user_banned', ['user_id' => $uid, 'votes_rejected' => $n]);
    flash("Zablokowano użytkownika #$uid, odrzucono $n głosów.", 'ok');
    redirect('/admin#glosy');
}

function admin_export(): never {
    require_admin();
    audit('export_csv');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=wyniki.csv');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $head = ['id', 'nazwa', 'instytucja', 'rodzaj instytucji', 'miejscowość', 'typ innowacji', 'etap', 'obszary', 'potencjał (0-100)'];
    foreach (CRITERIA as [$label]) $head[] = $label;
    array_push($head, 'rekomendowana ścieżka', 'dopasowanie ścieżki %', 'etap procesu', 'głosy zaliczone', 'głosy oczekujące', 'zgłoszenia zainteresowania');
    fputcsv($out, $head, ';');
    foreach (q("SELECT p.*, (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id AND v.status='counted') AS votes,
                       (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id AND v.status='pending') AS vpending,
                       (SELECT COUNT(*) FROM contact_requests c WHERE c.project_id = p.id) AS contacts
                FROM projects p WHERE p.status = 'approved'")->fetchAll() as $p) {
        $ev = evaluate($p); $rec = recommend($p, $ev)[0] ?? ['name' => '', 'fit' => ''];
        $row = [$p['id'], $p['title'], $p['institution'], $p['category'], $p['location'], INNO_TYPES_SHORT[$p['innovation_type']] ?? '',
                STAGES[$p['stage']] ?? '', $p['areas'], $ev['total']];
        foreach (CRITERIA as $c => $_) $row[] = str_replace('.', ',', (string)$ev['scores'][$c]);
        array_push($row, $rec['name'], $rec['fit'], PIPELINE[$p['pipeline']][0] ?? '', $p['votes'], $p['vpending'], $p['contacts']);
        fputcsv($out, $row, ';');
    }
    exit;
}
