<?php
/* Radar Innowacji – silnik: słowniki, ocena potencjału, rekomendacje, podobieństwo, dopasowanie.
   Wszystko jest regułowe i jawne: każdy wynik ma uzasadnienie, które można pokazać użytkownikowi
   i jury. Nie używamy „czarnej skrzynki” AI – kryteria i wagi są w tym pliku. */
declare(strict_types=1);

/* ===================================================================== słowniki */

const INNO_TYPES = [
    'technologiczna' => 'Technologiczna (produkt, urządzenie, oprogramowanie)',
    'organizacyjna'  => 'Organizacyjna / procesowa (nowy sposób działania)',
    'edukacyjna'     => 'Edukacyjna (metoda, program, materiały)',
    'spoleczna'      => 'Społeczna (usługa dla ludzi, integracja, partycypacja)',
];
const INNO_TYPES_SHORT = ['technologiczna' => 'Technologiczna', 'organizacyjna' => 'Organizacyjna', 'edukacyjna' => 'Edukacyjna', 'spoleczna' => 'Społeczna'];

const STAGES = [
    'pomysl'   => 'Pomysł lub koncepcja',
    'prototyp' => 'Prototyp / pilotaż w toku',
    'lokalnie' => 'Działa w naszej instytucji',
    'wiele'    => 'Działa już w kilku miejscach',
];
const EVIDENCE = [
    'brak'       => 'Jeszcze nie mierzyliśmy efektów',
    'obserwacje' => 'Mamy obserwacje i opinie użytkowników',
    'pomiary'    => 'Mamy mierzalne wyniki (liczby, porównanie przed/po)',
];
const SCOPES = [
    'lokalna' => 'Głównie naszej instytucji',
    'region'  => 'Wielu podobnych instytucji w regionie',
    'kraj'    => 'Instytucji w całej Polsce lub za granicą',
];
const COSTS = [
    'niski'  => 'Niski – do ok. 10 tys. zł, bez specjalistycznego sprzętu',
    'sredni' => 'Średni – 10–100 tys. zł lub wymaga szkoleń',
    'wysoki' => 'Wysoki – ponad 100 tys. zł lub specjalistyczna infrastruktura',
];
const COMMERCIAL = [
    'nie'  => 'Nie – to rozwiązanie niekomercyjne',
    'moze' => 'Być może',
    'tak'  => 'Tak – są potencjalni klienci',
];
const NEEDS = [
    'partner'      => 'Partnera do wdrożenia w innej instytucji',
    'finansowanie' => 'Finansowania',
    'mentoring'    => 'Mentoringu / wsparcia eksperckiego',
    'prototyp'     => 'Dostępu do laboratorium / prototypowania',
    'ochrona'      => 'Ochrony własności intelektualnej',
    'promocja'     => 'Promocji i rozgłosu',
];
const AREAS = [
    'Edukacja', 'Administracja i e-usługi', 'Zdrowie i jakość życia', 'Środowisko i energia',
    'Transport i mobilność', 'Społeczność i integracja', 'ICT i dane', 'Motoryzacja, lotnictwo i kosmos', 'Kultura i dziedzictwo',
];
const PIPELINE = [
    'zgloszona'  => ['Zgłoszona', 'Czeka na weryfikację moderatora.'],
    'ocena'      => ['W ocenie', 'Eksperci oceniają potencjał według kryteriów.'],
    'oceniona'   => ['Oceniona', 'Ocena zakończona, rekomendacja ścieżki gotowa.'],
    'laczenie'   => ['Łączenie z partnerami', 'Trwają rozmowy z zainteresowanymi odbiorcami.'],
    'wdrazanie'  => ['W trakcie wdrożenia / transferu', 'Rozwiązanie jest rozwijane lub wdrażane w kolejnym miejscu.'],
    'wdrozona'   => ['Wdrożona poza miejscem powstania', 'Innowacja zadziałała w kolejnej organizacji.'],
];
const ORG_TYPES = ['Instytucja publiczna / samorząd', 'Szkoła lub uczelnia', 'Firma', 'Inwestor / fundusz', 'Organizacja społeczna'];
const OFFERS = ['wdrozenie' => 'Chcemy wdrożyć gotowe rozwiązanie', 'partnerstwo' => 'Szukamy partnera do wspólnego rozwoju',
                'finansowanie' => 'Możemy sfinansować / zainwestować', 'testy' => 'Udostępnimy miejsce na pilotaż / testy'];

/* ===================================================================== kryteria oceny (jawne wagi) */

const CRITERIA = [
    'skala'       => ['Skala problemu',            20, 'Ilu instytucji / ludzi dotyczy problem, który rozwiązuje innowacja.'],
    'nowatorstwo' => ['Nowatorstwo',               15, 'Czy podobne rozwiązania już istnieją (porównanie z bazą zgłoszeń).'],
    'dojrzalosc'  => ['Dojrzałość',                15, 'Jak daleko jest od pomysłu do działającego rozwiązania.'],
    'efekty'      => ['Potwierdzone efekty',       15, 'Czy efekty są zmierzone i udokumentowane.'],
    'transfer'    => ['Łatwość transferu',         20, 'Jak łatwo i tanio przenieść rozwiązanie do innej organizacji.'],
    'komercja'    => ['Potencjał komercyjny',      10, 'Czy ktoś mógłby za to płacić lub zainwestować.'],
    'spolecznosc' => ['Poparcie społeczności',      5, 'Głosy użytkowników platformy (względem najpopularniejszej innowacji).'],
];
/** Minimalne dopasowanie innowacja ↔ zapotrzebowanie, poniżej którego nie pokazujemy wyniku (szum). */
const MATCH_MIN = 0.18;
const EXPERT_CRITERIA = ['skala', 'nowatorstwo', 'dojrzalosc', 'efekty', 'transfer', 'komercja'];

function lc1(string $s): string { return mb_strtolower(mb_substr($s, 0, 1)) . mb_substr($s, 1); }
function csv_list(?string $s): array { return array_values(array_filter(array_map('trim', explode(',', (string)$s)))); }
function clamp15(float $v): int { return (int)max(1, min(5, round($v))); }
function norm15(float $v): float { return ($v - 1) / 4; }

/** Automatyczna, wstępna ocena na podstawie odpowiedzi z formularza + bazy porównawczej. */
function auto_scores(array $p): array {
    $s = []; $why = [];

    $s['skala'] = ['lokalna' => 2, 'region' => 4, 'kraj' => 5][$p['scope']] ?? 3;
    $why['skala'] = 'Problem dotyczy: ' . lc1(SCOPES[$p['scope']] ?? '—') . '.';

    $sim = similar_projects($p, 1);
    if ($sim) {
        $top = $sim[0];
        $s['nowatorstwo'] = clamp15(5 - $top['score'] * 7);
        $why['nowatorstwo'] = sprintf('Najbardziej podobne zgłoszenie: „%s” (podobieństwo %d%%).', $top['title'], round($top['score'] * 100));
    } else {
        $s['nowatorstwo'] = 5;
        $why['nowatorstwo'] = 'W bazie nie ma podobnych zgłoszeń.';
    }

    $s['dojrzalosc'] = ['pomysl' => 1, 'prototyp' => 3, 'lokalnie' => 4, 'wiele' => 5][$p['stage']] ?? 3;
    $why['dojrzalosc'] = 'Etap: ' . lc1(STAGES[$p['stage']] ?? '—') . '.';

    $s['efekty'] = ['brak' => 1, 'obserwacje' => 3, 'pomiary' => 5][$p['evidence']] ?? 2;
    $why['efekty'] = EVIDENCE[$p['evidence']] ?? '—';
    if ($s['efekty'] < 5 && preg_match('/\d+\s*(%|proc|godz|h\b|min|zł|os[oó]b|uczni|użytkown|razy)/iu', (string)$p['results'])) {
        $s['efekty']++;
        $why['efekty'] .= ' W opisie efektów podano konkretne liczby (+1).';
    }

    $t = ['niski' => 5, 'sredni' => 3, 'wysoki' => 2][$p['cost']] ?? 3;
    $tw = 'Koszt wdrożenia: ' . lc1(explode(' – ', COSTS[$p['cost']] ?? '—')[0]) . '.';
    if ($p['stage'] === 'wiele') { $t++; $tw .= ' Już działa w kilku miejscach (+1).'; }
    if ($p['stage'] === 'pomysl') { $t--; $tw .= ' Na etapie pomysłu (−1).'; }
    $s['transfer'] = clamp15($t); $why['transfer'] = $tw;

    $k = ['nie' => 1, 'moze' => 3, 'tak' => 4][$p['commercial']] ?? 2;
    $kw = 'Deklarowany potencjał komercyjny: ' . lc1(COMMERCIAL[$p['commercial']] ?? '—') . '.';
    if ($p['commercial'] === 'tak' && $p['innovation_type'] === 'technologiczna') { $k = 5; $kw .= ' Innowacja technologiczna (+1).'; }
    $s['komercja'] = $k; $why['komercja'] = $kw;

    $max = (int)qv("SELECT MAX(c) FROM (SELECT COUNT(*) c FROM votes WHERE status='counted' GROUP BY project_id)");
    $mine = (int)qv("SELECT COUNT(*) FROM votes WHERE status='counted' AND project_id = ?", [$p['id'] ?? 0]);
    if ($max > 0) { $s['spolecznosc'] = clamp15(1 + 4 * $mine / $max); $why['spolecznosc'] = "$mine " . glosy($mine) . " (najwięcej w bazie: $max)."; }
    else { $s['spolecznosc'] = 3; $why['spolecznosc'] = 'Brak głosów w bazie – wartość neutralna.'; }

    return [$s, $why];
}

/** Ocena końcowa: ekspercka (średnia ekspertów) tam, gdzie jest; w pozostałych kryteriach automatyczna. */
function evaluate(array $p): array {
    [$auto, $why] = auto_scores($p);
    $rows = q('SELECT a.*, u.email FROM assessments a JOIN users u ON u.id = a.assessor_id WHERE project_id = ? ORDER BY created_at', [$p['id']])->fetchAll();
    $expert = []; $comments = [];
    foreach ($rows as $r) {
        foreach (json_decode($r['scores'], true) ?: [] as $c => $v) $expert[$c][] = (int)$v;
        if (trim((string)$r['comment']) !== '') $comments[] = ['text' => $r['comment'], 'at' => $r['created_at']];
    }
    $final = []; $source = []; $total = 0.0;
    foreach (CRITERIA as $c => [$label, $w]) {
        if (!empty($expert[$c])) { $final[$c] = round(array_sum($expert[$c]) / count($expert[$c]), 1); $source[$c] = 'ekspert'; }
        else { $final[$c] = $auto[$c]; $source[$c] = 'auto'; }
        $total += $w * norm15((float)$final[$c]);
    }
    return ['scores' => $final, 'auto' => $auto, 'why' => $why, 'source' => $source, 'total' => (int)round($total),
            'experts' => count($rows), 'comments' => $comments];
}

function score_label(int $t): string {
    return $t >= 75 ? 'Bardzo wysoki potencjał' : ($t >= 55 ? 'Wysoki potencjał' : ($t >= 35 ? 'Umiarkowany potencjał' : 'Potencjał do rozwinięcia'));
}

/* ===================================================================== rekomendacja ścieżki */

const PATHS = [
    'transfer' => [
        'name' => 'Transfer i replikacja w innych instytucjach',
        'desc' => 'Rozwiązanie działa i da się je tanio przenieść – największą wartość przyniesie wdrożenie w kolejnych szkołach, gminach lub urzędach.',
        'steps' => ['Przygotować „paczkę wdrożeniową”: instrukcję, materiały, koszty i harmonogram.',
                    'Zorganizować wizytę studyjną lub webinar dla zainteresowanych instytucji.',
                    'Wybrać 1–3 instytucje pilotażowe z listy dopasowanych zapotrzebowań.'],
        'kpi' => ['Liczba instytucji, które wdrożyły rozwiązanie w ciągu 12 miesięcy', 'Średni czas i koszt wdrożenia w nowej instytucji',
                  'Efekt w nowej instytucji porównany z efektem u twórców'],
        'support' => ['Sieć współpracy instytucji publicznych regionu', 'Wizyty studyjne i webinary prowadzone przez PCI', 'Programy Interreg (transfer dobrych praktyk między regionami)'],
    ],
    'komercjalizacja' => [
        'name' => 'Komercjalizacja / startup',
        'desc' => 'Są potencjalni klienci, a rozwiązanie jest wystarczająco dojrzałe – warto sprawdzić model biznesowy i drogę do rynku.',
        'steps' => ['Przeprowadzić 10 rozmów z potencjalnymi klientami (walidacja problemu i ceny).',
                    'Ustalić prawa do rozwiązania z macierzystą instytucją (umowa, spin-off, licencja).',
                    'Zgłosić się do programu akceleracyjnego i przygotować prezentację dla inwestorów.'],
        'kpi' => ['Liczba przeprowadzonych rozmów z klientami', 'Pierwszy płacący klient lub list intencyjny', 'Pozyskane finansowanie (zł)'],
        'support' => ['Program akceleracyjny PCI', 'Inwestorzy i fundusze zalążkowe', 'Centra transferu technologii uczelni'],
    ],
    'rozwoj' => [
        'name' => 'Rozwój i pilotaż',
        'desc' => 'Pomysł jest obiecujący, ale potrzebuje dopracowania i sprawdzenia w praktyce, zanim będzie gotowy do przeniesienia.',
        'steps' => ['Zbudować prototyp i określić mierzalny cel pilotażu (wskaźnik przed/po).',
                    'Przeprowadzić pilotaż z prawdziwymi użytkownikami przez 1–3 miesiące.',
                    'Udokumentować wyniki i zaktualizować kartę innowacji na Radarze.'],
        'kpi' => ['Zrealizowany pilotaż z określonym celem', 'Liczba użytkowników testowych', 'Zmierzony efekt względem stanu wyjściowego'],
        'support' => ['ProtoLab PCI – prototypowanie (dla innowatorów 16–30 lat)', 'Mentoring ekspertów PCI', 'Laboratoria uczelni regionu'],
    ],
    'ochrona' => [
        'name' => 'Ochrona własności intelektualnej',
        'desc' => 'Rozwiązanie technologiczne, nowatorskie i z potencjałem rynkowym – przed upublicznieniem szczegółów warto zbadać stan techniki i rozważyć ochronę.',
        'steps' => ['Sprawdzić bazy patentowe UPRP i EPO (linki poniżej).', 'Skonsultować się z rzecznikiem patentowym.',
                    'Rozważyć zgłoszenie patentu lub wzoru użytkowego przed publiczną prezentacją szczegółów.'],
        'kpi' => ['Wykonane badanie stanu techniki', 'Złożone zgłoszenie patentowe / wzoru użytkowego'],
        'support' => ['Urząd Patentowy RP', 'Rzecznicy patentowi współpracujący z PCI', 'Centra transferu technologii uczelni'],
    ],
    'finansowanie' => [
        'name' => 'Pozyskanie finansowania',
        'desc' => 'Dalszy rozwój lub skalowanie wymaga środków – warto dopasować rozwiązanie do dostępnych programów.',
        'steps' => ['Oszacować budżet na kolejne 12 miesięcy.', 'Dopasować program finansowania (fundusze europejskie, granty, inwestor).',
                    'Przygotować wniosek z mierzalnymi wskaźnikami z karty innowacji.'],
        'kpi' => ['Złożony wniosek o finansowanie', 'Pozyskana kwota', 'Czas od rekomendacji do złożenia wniosku'],
        'support' => ['Fundusze Europejskie dla Podkarpacia', 'Programy NCBR', 'Inwestorzy i fundusze zalążkowe'],
    ],
];

function recommend(array $p, array $ev): array {
    $s = $ev['scores']; $n = fn($c) => norm15((float)$s[$c]);
    $needs = csv_list($p['needs']);
    $fit = [];
    $fit['transfer'] = 100 * (0.35 * $n('transfer') + 0.25 * $n('skala') + 0.25 * $n('efekty') + 0.15 * $n('dojrzalosc'));
    $fit['komercjalizacja'] = 100 * (0.45 * $n('komercja') + 0.25 * $n('dojrzalosc') + 0.15 * $n('nowatorstwo') + 0.15 * $n('skala'));
    if ($s['komercja'] <= 1.5) $fit['komercjalizacja'] *= 0.3;
    $fit['rozwoj'] = 100 * (0.5 * (1 - $n('dojrzalosc')) + 0.3 * (1 - $n('efekty')) + 0.2 * $n('nowatorstwo'));
    $fit['ochrona'] = $p['innovation_type'] === 'technologiczna' ? 100 * (0.5 * $n('nowatorstwo') + 0.3 * $n('komercja') + 0.2 * $n('dojrzalosc')) : 0;
    // Finansowanie to zwykle ścieżka wspierająca, dlatego mnożnik 0,85 – wygrywa tylko, gdy nic innego nie pasuje lepiej.
    $fit['finansowanie'] = 85 * (0.4 * (in_array('finansowanie', $needs, true) ? 1 : 0)
                               + 0.3 * (['wysoki' => 1, 'sredni' => 0.5][$p['cost']] ?? 0) + 0.3 * $n('skala'));
    arsort($fit);

    $label = fn($c) => CRITERIA[$c][0] . ' ' . rtrim(rtrim(number_format((float)$s[$c], 1, ',', ''), '0'), ',') . '/5';
    $reasons = [
        'transfer' => [$label('transfer'), $label('skala'), $label('efekty')],
        'komercjalizacja' => [$label('komercja'), $label('dojrzalosc'), $label('nowatorstwo')],
        'rozwoj' => [$label('dojrzalosc') . ' – jest jeszcze przestrzeń do rozwoju', $label('efekty'), $label('nowatorstwo')],
        'ochrona' => ['Innowacja technologiczna', $label('nowatorstwo'), $label('komercja')],
        'finansowanie' => array_values(array_filter([in_array('finansowanie', $needs, true) ? 'Twórcy zgłosili potrzebę finansowania' : null,
                          'Koszt wdrożenia: ' . lc1(explode(' – ', COSTS[$p['cost']] ?? '')[0]), $label('skala')])),
    ];
    $out = [];
    foreach ($fit as $k => $v) {
        $v = (int)round($v);
        if ($v < 35 && $out) continue;
        $out[] = PATHS[$k] + ['key' => $k, 'fit' => $v, 'reasons' => $reasons[$k]];
        if (count($out) >= 3) break;
    }
    return $out;
}

/* ===================================================================== podobieństwo (TF-IDF + cosinus)
   Proste, przejrzyste i działające bez zewnętrznych usług. Polskie słowa są skracane do rdzenia
   (pierwsze 6 liter), żeby „szkoła/szkole/szkołach” liczyły się jako to samo słowo. */

const STOPWORDS = ['oraz','który','która','które','których','którym','jest','są','być','było','będzie','może','można','przez','przy','także',
    'tylko','takie','taki','jako','jego','jej','ich','nasz','nasza','nasze','naszej','naszych','dla','nie','tak','czy','już','się','to','ten',
    'ta','te','tym','tych','tej','na','w','we','z','ze','do','od','po','o','i','a','u','że','jak','co','bo','lub','ale','aby','żeby','by',
    'też','gdy','kiedy','bardzo','więcej','mniej','wiele','kilka','każdy','każda','każde','wszystkie','swoje','swój','swoją','sobie',
    'pomysł','projekt','projektu','rozwiązanie','rozwiązania','innowacja','innowacji','instytucji','instytucja','dzięki','pozwala','umożliwia'];

function tokens(string $text): array {
    static $stop = null;
    $stop ??= array_flip(STOPWORDS);
    $text = mb_strtolower($text);
    $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($words as $w) {
        if (mb_strlen($w) < 3 || isset($stop[$w]) || ctype_digit($w)) continue;
        $out[] = mb_substr($w, 0, 6);
    }
    return $out;
}

function doc_text(array $d): string {
    return implode(' ', [$d['title'] ?? '', $d['title'] ?? '', $d['summary'] ?? '', $d['problem'] ?? '', $d['description'] ?? '',
                         str_replace(',', ' ', $d['areas'] ?? ''), $d['impact'] ?? '']);
}

/** Korpus: opublikowane innowacje (+ zapotrzebowania). Liczony raz na żądanie. */
function corpus(): array {
    static $c = null;
    if ($c !== null) return $c;
    $docs = [];
    foreach (q("SELECT id, title, summary, problem, description, areas, impact, institution, location FROM projects WHERE status = 'approved'")->fetchAll() as $r)
        $docs['p' . $r['id']] = ['kind' => 'project', 'row' => $r, 'tf' => array_count_values(tokens(doc_text($r)))];
    foreach (q("SELECT id, title, description, areas, org_name, org_type, offer, submitted_by FROM needs WHERE status = 'approved'")->fetchAll() as $r)
        $docs['n' . $r['id']] = ['kind' => 'need', 'row' => $r, 'tf' => array_count_values(tokens($r['title'] . ' ' . $r['title'] . ' ' . $r['description'] . ' ' . str_replace(',', ' ', $r['areas'] ?? '')))];
    $df = [];
    foreach ($docs as $d) foreach ($d['tf'] as $t => $_) $df[$t] = ($df[$t] ?? 0) + 1;
    $N = max(count($docs), 1);
    $idf = [];
    foreach ($df as $t => $n) $idf[$t] = log(($N + 1) / ($n + 0.5)) + 1;
    foreach ($docs as $k => $d) $docs[$k]['vec'] = weigh($d['tf'], $idf);
    return $c = ['docs' => $docs, 'idf' => $idf];
}

function weigh(array $tf, array $idf): array {
    $v = []; $norm = 0.0;
    foreach ($tf as $t => $n) { $w = (1 + log($n)) * ($idf[$t] ?? 2.0); $v[$t] = $w; $norm += $w * $w; }
    $norm = sqrt($norm) ?: 1;
    foreach ($v as $t => $w) $v[$t] = $w / $norm;
    return $v;
}

function cosine(array $a, array $b): array {
    $s = 0.0; $shared = [];
    if (count($a) > count($b)) [$a, $b] = [$b, $a];
    foreach ($a as $t => $w) if (isset($b[$t])) { $s += $w * $b[$t]; $shared[$t] = $w * $b[$t]; }
    arsort($shared);
    return [$s, array_slice(array_keys($shared), 0, 5)];
}

/** Zwraca podobne dokumenty danego rodzaju: [{id, title, score, shared, row}] */
/** Rdzeń → pierwsze pełne słowo z tekstu (żeby pokazywać „zgłoszenia”, a nie „zgłosz”). */
function stem_words(string $text): array {
    $map = [];
    foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) as $w)
        if (mb_strlen($w) >= 3) $map[mb_substr($w, 0, 6)] ??= $w;
    return $map;
}

function similar_to_text(string $text, string $kind = 'project', int $limit = 5, ?string $excludeKey = null, float $min = 0.12): array {
    $c = corpus();
    $vec = weigh(array_count_values(tokens($text)), $c['idf']);
    $words = stem_words($text);
    $res = [];
    foreach ($c['docs'] as $k => $d) {
        if ($d['kind'] !== $kind || $k === $excludeKey) continue;
        [$s, $shared] = cosine($vec, $d['vec']);
        $shared = array_values(array_unique(array_map(fn($st) => $words[$st] ?? $st, $shared)));
        if ($s >= $min) $res[] = ['id' => (int)$d['row']['id'], 'title' => $d['row']['title'], 'score' => $s, 'shared' => $shared, 'row' => $d['row']];
    }
    usort($res, fn($x, $y) => $y['score'] <=> $x['score']);
    return array_slice($res, 0, $limit);
}

function similar_projects(array $p, int $limit = 5): array {
    return similar_to_text(doc_text($p), 'project', $limit, isset($p['id']) ? 'p' . $p['id'] : null);
}

/** Zapotrzebowania pasujące do innowacji – rdzeń „łączenia z odbiorcami”. Wspólny obszar daje premię. */
function matching_needs(array $p, int $limit = 5): array {
    $res = similar_to_text(doc_text($p), 'need', 20, null, 0.08);
    $areas = csv_list($p['areas']);
    foreach ($res as &$r) {
        $common = array_intersect($areas, csv_list($r['row']['areas'] ?? ''));
        if ($common) { $r['score'] += 0.1; $r['areas'] = array_values($common); }
    }
    unset($r);
    usort($res, fn($x, $y) => $y['score'] <=> $x['score']);
    return array_slice(array_values(array_filter($res, fn($r) => $r['score'] >= MATCH_MIN)), 0, $limit);
}

function matching_projects_for_need(array $n, int $limit = 6): array {
    $res = similar_to_text($n['title'] . ' ' . $n['title'] . ' ' . $n['description'] . ' ' . str_replace(',', ' ', $n['areas'] ?? ''), 'project', 20, null, 0.08);
    $areas = csv_list($n['areas']);
    foreach ($res as &$r) {
        $full = q1('SELECT areas FROM projects WHERE id = ?', [$r['id']]);
        if (array_intersect($areas, csv_list($full['areas'] ?? ''))) $r['score'] += 0.1;
    }
    unset($r);
    usort($res, fn($x, $y) => $y['score'] <=> $x['score']);
    return array_slice(array_values(array_filter($res, fn($r) => $r['score'] >= MATCH_MIN)), 0, $limit);
}

/** Słowa kluczowe do wyszukiwania w zewnętrznych bazach (patenty, publikacje, projekty UE). */
function keywords(array $p, int $n = 5): array {
    $text = mb_strtolower($p['title'] . ' ' . $p['summary'] . ' ' . $p['problem'] . ' ' . $p['description']);
    $words = preg_split('/[^\p{L}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $stop = array_flip(STOPWORDS); $count = [];
    foreach ($words as $w) if (mb_strlen($w) >= 5 && !isset($stop[$w])) $count[$w] = ($count[$w] ?? 0) + 1;
    $idf = corpus()['idf']; $scored = [];
    foreach ($count as $w => $c) $scored[$w] = $c * ($idf[mb_substr($w, 0, 6)] ?? 2.5);
    arsort($scored);
    $out = []; $stems = [];
    foreach (array_keys($scored) as $w) { $st = mb_substr($w, 0, 6); if (isset($stems[$st])) continue; $stems[$st] = 1; $out[] = $w; if (count($out) >= $n) break; }
    return $out;
}

function external_sources(array $p): array {
    $kw = implode(' ', array_slice(keywords($p, 4), 0, 3));
    $q = urlencode($kw);
    return [
        'keywords' => $kw,
        'links' => [
            ['Wyszukiwarka Urzędu Patentowego RP', 'https://ewyszukiwarka.pue.uprp.gov.pl/search/simple-search', 'patenty i wzory w Polsce – wklej słowa kluczowe'],
            ['Rejestr Europejskiego Urzędu Patentowego', 'https://register.epo.org/regviewer', 'zgłoszenia patentowe w Europie'],
            ['Google Patents', 'https://patents.google.com/?q=' . $q, 'światowe bazy patentowe – wyszukiwanie od razu'],
            ['Polska Bibliografia Naukowa', 'https://pbn.nauka.gov.pl/core/#/home', 'publikacje naukowe polskich uczelni'],
            ['Projekty Interreg Europe', 'https://interreg.gov.pl/lista-projektow/', 'zrealizowane projekty międzyregionalne'],
            ['Sukcesy beneficjentów NCBR', 'https://www.gov.pl/web/ncbr/wyszukiwarka-sukcesow-beneficjentow', 'projekty B+R finansowane przez NCBR'],
        ],
    ];
}

/* ===================================================================== widoczność zapotrzebowań
   Zapotrzebowanie (i jego dopasowania) widzi tylko: jego autor, administrator oraz twórcy innowacji,
   które do niego pasują. Osoby postronne nie mają tu nic do roboty, a opis potrzeby bywa poufny. */

/** Zapotrzebowania pasujące do opublikowanych innowacji danego użytkownika: [need_id => ['need'=>..., 'projects'=>[[id,title,score]]]] */
function needs_for_creator(int $uid): array {
    $out = [];
    foreach (q("SELECT * FROM projects WHERE submitted_by = ? AND status = 'approved'", [$uid])->fetchAll() as $p) {
        foreach (matching_needs($p, 10) as $m) {
            if ((int)($m['row']['submitted_by'] ?? 0) === $uid) continue;
            $out[$m['id']]['need'] = $m['row'];
            $out[$m['id']]['projects'][] = ['id' => (int)$p['id'], 'title' => $p['title'], 'score' => $m['score']];
        }
    }
    return $out;
}

function can_view_need(array $n): bool {
    $u = current_user();
    if (!$u) return false;
    if ($u['is_admin'] || (int)$u['id'] === (int)$n['submitted_by']) return true;
    return $n['status'] === 'approved' && isset(needs_for_creator((int)$u['id'])[(int)$n['id']]);
}

function can_see_project_needs(array $p): bool {
    $u = current_user();
    return $u && ($u['is_admin'] || (int)$u['id'] === (int)$p['submitted_by']);
}
