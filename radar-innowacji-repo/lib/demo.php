<?php
/* Dane demonstracyjne – przykładowe innowacje i zapotrzebowania do pokazania scenariusza oceny.
   Wszystkie są oznaczone flagą is_demo (na stronie: etykieta „Przykład”) i można je usunąć jednym kliknięciem. */
declare(strict_types=1);

const DEMO_PROJECTS = [
    [
        'title' => 'Szkolny system monitoringu jakości powietrza',
        'institution' => 'Zespół Szkół Technicznych (przykład)', 'category' => 'Szkoła', 'location' => 'Mielec',
        'innovation_type' => 'technologiczna', 'stage' => 'lokalnie', 'evidence' => 'pomiary', 'scope' => 'region',
        'cost' => 'niski', 'commercial' => 'moze', 'needs' => 'partner,promocja', 'areas' => 'Środowisko i energia,Edukacja,ICT i dane',
        'summary' => 'Uczniowie zbudowali sieć tanich czujników smogu przy szkołach i publikują dane na otwartej mapie dla mieszkańców.',
        'problem' => 'Mieszkańcy małych miejscowości nie mają dostępu do lokalnych danych o jakości powietrza – najbliższa stacja pomiarowa jest kilkanaście kilometrów dalej, a rodzice nie wiedzą, kiedy dzieci mogą bezpiecznie wychodzić na przerwy na zewnątrz.',
        'description' => 'Uczniowie technikum zaprojektowali czujnik pyłów zawieszonych PM2.5 i PM10 oparty na mikrokontrolerze i tanim module laserowym. Czujniki zamontowano na 6 budynkach szkół w powiecie. Dane co 10 minut trafiają na otwartą mapę i do aplikacji, która wysyła szkołom alert, gdy normy są przekroczone. Projekt jest realizowany na lekcjach elektroniki i programowania.',
        'results' => 'Działa od 14 miesięcy w 6 szkołach. Koszt jednego czujnika to ok. 350 zł. Mapę odwiedziło ponad 4 tys. osób. Szkoły w dni z alertem przenoszą przerwy do budynku.',
        'impact' => 'Każda szkoła z pracownią elektroniczną może zbudować czujniki z uczniami. Gminy zyskują tanie dane do planowania działań antysmogowych.',
        'creators' => 'Nauczyciel elektroniki i 12 uczniów klas 3–4',
    ],
    [
        'title' => 'Cyfrowy asystent sołtysa',
        'institution' => 'Urząd Gminy (przykład)', 'category' => 'Samorząd', 'location' => 'Dukla',
        'innovation_type' => 'organizacyjna', 'stage' => 'lokalnie', 'evidence' => 'pomiary', 'scope' => 'kraj',
        'cost' => 'niski', 'commercial' => 'nie', 'needs' => 'partner,mentoring', 'areas' => 'Administracja i e-usługi,Społeczność i integracja',
        'summary' => 'Prosty system zgłaszania usterek i ogłoszeń dla sołectw – działa przez SMS, więc korzystają z niego także seniorzy bez smartfonów.',
        'problem' => 'Sołtysi zbierają zgłoszenia mieszkańców (dziury w drogach, oświetlenie, odpady) na kartkach i telefonicznie. Zgłoszenia giną, urząd nie wie, co jest pilne, a mieszkańcy nie wiedzą, czy coś zostało załatwione.',
        'description' => 'Mieszkańcy wysyłają zgłoszenie SMS-em lub przez prosty formularz. System przypisuje je do sołectwa i właściwego referatu urzędu, a mieszkaniec dostaje SMS, gdy sprawa zostanie załatwiona. Sołtys przez ten sam system rozsyła ogłoszenia. Rozwiązanie zbudowano na gotowej bramce SMS i arkuszu kalkulacyjnym – bez kosztownego oprogramowania.',
        'results' => 'W 23 sołectwach w ciągu roku obsłużono 1 140 zgłoszeń. Średni czas reakcji spadł z 19 do 6 dni. 38% zgłoszeń wysłały osoby powyżej 60 roku życia.',
        'impact' => 'W Polsce działa ponad 40 tys. sołectw z tym samym problemem. Rozwiązanie wymaga jedynie bramki SMS i przeszkolenia jednej osoby w urzędzie.',
        'creators' => 'Referat organizacyjny urzędu gminy',
    ],
    [
        'title' => 'Biblioteka rzeczy',
        'institution' => 'Miejska Biblioteka Publiczna (przykład)', 'category' => 'Inna instytucja publiczna', 'location' => 'Krosno',
        'innovation_type' => 'spoleczna', 'stage' => 'lokalnie', 'evidence' => 'obserwacje', 'scope' => 'region',
        'cost' => 'niski', 'commercial' => 'nie', 'needs' => 'promocja,partner', 'areas' => 'Społeczność i integracja,Środowisko i energia,Kultura i dziedzictwo',
        'summary' => 'Wypożyczalnia narzędzi, sprzętu turystycznego i elektroniki działająca w bibliotece – zamiast kupować, mieszkańcy pożyczają.',
        'problem' => 'Mieszkańcy kupują drogi sprzęt (wiertarki, namioty, projektory), z którego korzystają kilka razy w roku. Biblioteki szukają sposobu, by przyciągnąć nowych czytelników.',
        'description' => 'Biblioteka udostępnia ponad 120 przedmiotów z darowizn i zakupów. Wypożyczenie odbywa się na kartę biblioteczną, tak jak książki. Wolontariusze sprawdzają stan sprzętu.',
        'results' => 'Zapisało się ponad 300 nowych czytelników. Najczęściej wypożyczane są narzędzia ogrodowe i sprzęt turystyczny.',
        'impact' => 'Model można przenieść do każdej biblioteki gminnej – wykorzystuje istniejący system wypożyczeń.',
        'creators' => 'Zespół biblioteki i wolontariusze',
    ],
    [
        'title' => 'Inteligentny czujnik oblodzenia skrzydła drona',
        'institution' => 'Koło naukowe Politechniki (przykład)', 'category' => 'Uczelnia', 'location' => 'Rzeszów',
        'innovation_type' => 'technologiczna', 'stage' => 'prototyp', 'evidence' => 'pomiary', 'scope' => 'kraj',
        'cost' => 'wysoki', 'commercial' => 'tak', 'needs' => 'finansowanie,ochrona,prototyp', 'areas' => 'Motoryzacja, lotnictwo i kosmos,ICT i dane',
        'summary' => 'Cienkowarstwowy czujnik, który wykrywa początki oblodzenia na skrzydle bezzałogowca i ostrzega operatora, zanim spadnie siła nośna.',
        'problem' => 'Oblodzenie jest jedną z głównych przyczyn awarii dronów latających zimą i w chmurach. Istniejące czujniki są ciężkie i drogie, przeznaczone dla dużych samolotów.',
        'description' => 'Studenci opracowali elastyczny czujnik pojemnościowy naklejany na krawędź natarcia skrzydła oraz algorytm rozpoznający pierwsze warstwy lodu. Prototyp przetestowano w komorze klimatycznej uczelni.',
        'results' => 'W 40 testach w komorze klimatycznej czujnik wykrył oblodzenie średnio 90 sekund przed spadkiem siły nośnej o 10%. Masa czujnika to 12 g.',
        'impact' => 'Producenci dronów inspekcyjnych i rolniczych mogą zwiększyć bezpieczeństwo lotów zimą. Technologia może trafić także do małego lotnictwa.',
        'creators' => 'Zespół 5 studentów lotnictwa pod opieką promotora',
    ],
    [
        'title' => 'Mapa barier architektonicznych tworzona przez mieszkańców',
        'institution' => 'Starostwo Powiatowe (przykład)', 'category' => 'Samorząd', 'location' => 'Przemyśl',
        'innovation_type' => 'spoleczna', 'stage' => 'prototyp', 'evidence' => 'obserwacje', 'scope' => 'region',
        'cost' => 'sredni', 'commercial' => 'nie', 'needs' => 'mentoring,finansowanie', 'areas' => 'Społeczność i integracja,Administracja i e-usługi,Zdrowie i jakość życia',
        'summary' => 'Mieszkańcy oznaczają na mapie schody, wysokie krawężniki i brak podjazdów, a powiat publikuje postęp ich usuwania.',
        'problem' => 'Osoby z niepełnosprawnościami i rodzice z wózkami napotykają bariery, o których urzędnicy często nie wiedzą. Brakuje danych do planowania inwestycji.',
        'description' => 'Aplikacja mapowa pozwala dodać zdjęcie i lokalizację bariery. Zgłoszenia trafiają do zarządcy drogi lub budynku. Raz w kwartale powiat publikuje raport z usuniętych barier.',
        'results' => 'W pilotażu w jednym mieście zgłoszono 210 barier, z czego 35 usunięto.',
        'impact' => 'Rozwiązanie przydaje się każdemu samorządowi realizującemu ustawę o dostępności.',
        'creators' => 'Pełnomocnik ds. dostępności i rada seniorów',
    ],
    [
        'title' => 'Szkolny system powiadomień o zagrożeniach dla rodziców',
        'institution' => 'Szkoła Podstawowa (przykład)', 'category' => 'Szkoła', 'location' => 'Sanok',
        'innovation_type' => 'organizacyjna', 'stage' => 'pomysl', 'evidence' => 'brak', 'scope' => 'region',
        'cost' => 'niski', 'commercial' => 'moze', 'needs' => 'mentoring,prototyp', 'areas' => 'Edukacja,ICT i dane',
        'summary' => 'Pomysł na łączenie alertów o smogu, upałach i zagrożeniach pogodowych w jeden komunikat dla rodziców uczniów.',
        'problem' => 'Rodzice dostają ostrzeżenia z wielu źródeł i nie wiedzą, czy dzieci będą wychodzić na zewnątrz. Szkoła informuje ich z opóźnieniem.',
        'description' => 'Koncepcja aplikacji, która pobiera dane o jakości powietrza i ostrzeżenia pogodowe, a dyrektor jednym kliknięciem wysyła rodzicom komunikat o zmianach planu dnia.',
        'results' => 'Na razie przeprowadzono ankietę wśród 80 rodziców – 71% chce takich powiadomień.',
        'impact' => 'Mogłoby z tego korzystać każde przedszkole i szkoła.',
        'creators' => 'Dyrektor szkoły i nauczyciel informatyki',
    ],
];

const DEMO_NEEDS = [
    ['org_name' => 'Gmina wiejska (przykład)', 'org_type' => 'Instytucja publiczna / samorząd', 'title' => 'Szukamy sposobu na zgłaszanie usterek przez mieszkańców wsi',
     'description' => 'Mamy 18 sołectw i wielu starszych mieszkańców. Zgłoszenia o dziurach w drogach i zepsutym oświetleniu przychodzą telefonicznie do sołtysów i giną. Chcemy prostego, taniego systemu zgłoszeń, który obsłużą również seniorzy.',
     'areas' => 'Administracja i e-usługi,Społeczność i integracja', 'offer' => 'wdrozenie,testy'],
    ['org_name' => 'Firma produkująca drony inspekcyjne (przykład)', 'org_type' => 'Firma', 'title' => 'Technologie zwiększające bezpieczeństwo lotów dronów zimą',
     'description' => 'Szukamy zespołów pracujących nad czujnikami i systemami ostrzegania dla bezzałogowców – oblodzenie, wiatr, niska temperatura baterii. Możemy sfinansować dalsze testy i licencjonować technologię.',
     'areas' => 'Motoryzacja, lotnictwo i kosmos,ICT i dane', 'offer' => 'finansowanie,partnerstwo'],
    ['org_name' => 'Wydział Ochrony Środowiska miasta (przykład)', 'org_type' => 'Instytucja publiczna / samorząd', 'title' => 'Tanie lokalne pomiary jakości powietrza w osiedlach',
     'description' => 'Potrzebujemy gęstszej sieci pomiarów smogu niż oficjalne stacje, żeby kierować kontrole pieców i informować mieszkańców. Chętnie nawiążemy współpracę ze szkołami.',
     'areas' => 'Środowisko i energia,ICT i dane', 'offer' => 'wdrozenie,finansowanie'],
    ['org_name' => 'Fundacja wspierająca seniorów (przykład)', 'org_type' => 'Organizacja społeczna', 'title' => 'Dostępność przestrzeni publicznej dla osób starszych',
     'description' => 'Szukamy rozwiązań, które pomagają usuwać bariery architektoniczne i angażują seniorów w zgłaszanie problemów.',
     'areas' => 'Społeczność i integracja,Zdrowie i jakość życia', 'offer' => 'partnerstwo,testy'],
];

require_once __DIR__ . '/demo_more.php';

/* Liczba głosów dla kolejnych przykładowych innowacji (w tej samej kolejności co listy) */
const DEMO_VOTES = [184, 96, 61, 142, 37, 8,  23, 71, 45, 119, 52, 33, 15, 88, 64, 158, 29, 41, 76, 0, 57, 107, 12, 47, 4];
/* Etapy procesu dla wybranych przykładów (reszta: „W ocenie”) */
const DEMO_PIPELINE = [
    'Młodzieżowy budżet obywatelski' => 'wdrozona', 'Wspólny zakup energii przez grupę gmin' => 'wdrozona',
    'Rezerwacja świetlic i sal gminnych online' => 'wdrazanie', 'Mobilna pracownia robotyki dla wiejskich szkół' => 'wdrazanie',
    'Cyfrowy asystent sołtysa' => 'laczenie', 'Szkolny system monitoringu jakości powietrza' => 'laczenie', 'Inteligentny czujnik oblodzenia skrzydła drona' => 'laczenie',
];
/* Przykładowe oceny eksperckie: skala, nowatorstwo, dojrzałość, efekty, transfer, komercja + komentarz */
const DEMO_ASSESSMENTS = [
    'Cyfrowy asystent sołtysa' => [[5, 3, 4, 5, 5, 2], 'Sprawdzone rozwiązanie o bardzo niskim koszcie – idealny kandydat do transferu do innych gmin wiejskich.'],
    'Inteligentny czujnik oblodzenia skrzydła drona' => [[4, 5, 3, 4, 2, 5], 'Wysoka nowatorskość. Przed publikacją wyników zalecamy badanie stanu techniki i zgłoszenie patentowe.'],
    'Algorytm planowania tras śmieciarek' => [[5, 4, 4, 5, 3, 5], 'Silne wyniki i realny rynek – rekomendujemy rozmowy z firmami komunalnymi i centrum transferu technologii.'],
    'Młodzieżowy budżet obywatelski' => [[4, 3, 5, 5, 5, 1], 'Model już przejęły kolejne gminy – warto przygotować otwarty podręcznik wdrożenia.'],
    'Wirtualne laboratorium chemiczne w goglach VR' => [[5, 4, 3, 3, 3, 5], 'Duży potencjał rynkowy w edukacji. Potrzebny pilotaż z pomiarem efektów nauczania.'],
    'Szkolny system powiadomień o zagrożeniach dla rodziców' => [[3, 3, 1, 1, 4, 3], 'Pomysł na wczesnym etapie – warto połączyć siły z autorami szkolnego monitoringu powietrza.'],
];

function demo_all_projects(): array { return array_merge(DEMO_PROJECTS, DEMO_PROJECTS_MORE); }

function demo_load(int $adminId): int {
    $t = time(); $n = 0; $pdo = db();
    mt_srand(2026);
    $pdo->exec('BEGIN IMMEDIATE');
    $ids = [];
    $all = demo_all_projects();
    foreach ($all as $i => $p) {
        q('INSERT INTO projects(title, institution, category, location, summary, description, impact, link, contact_email, submitted_by, status,
              created_at, reviewed_at, reviewed_by, innovation_type, stage, evidence, scope, cost, commercial, needs, areas, problem, results, creators, pipeline, is_demo)
           VALUES (?,?,?,?,?,?,?,NULL,?,?,\'approved\',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)',
          [$p['title'], $p['institution'], $p['category'], $p['location'], $p['summary'], $p['description'], $p['impact'],
           current_user()['email'] ?? 'demo@example.org', $adminId, $t - (count($all) - $i) * 7200 - 20 * 86400, $t - 15 * 86400, $adminId,
           $p['innovation_type'], $p['stage'], $p['evidence'], $p['scope'], $p['cost'], $p['commercial'], $p['needs'], $p['areas'],
           $p['problem'], $p['results'], $p['creators'], DEMO_PIPELINE[$p['title']] ?? 'ocena']);
        $ids[$i] = (int)$pdo->lastInsertId();
        $n++;
    }
    foreach (array_merge(DEMO_NEEDS, DEMO_NEEDS_MORE) as $d) {
        q("INSERT INTO needs(org_name, org_type, title, description, areas, offer, contact_email, submitted_by, status, is_demo, created_at)
           VALUES (?,?,?,?,?,?,?,?,'approved',1,?)",
          [$d['org_name'], $d['org_type'], $d['title'], $d['description'], $d['areas'], $d['offer'], current_user()['email'] ?? 'demo@example.org', $adminId, $t - 10 * 86400]);
        $n++;
    }

    // Przykładowi głosujący (adresy w zarezerwowanej domenie example.org) – każdy ma maks. tyle głosów, ile pozwala regulamin
    $per = votes_per_user();
    $need = array_sum(DEMO_VOTES);
    $userCount = (int)ceil($need / max(1, $per - 0.4)) + 25;
    $users = []; $left = [];
    for ($u = 1; $u <= $userCount; $u++) {
        $email = sprintf('glosujacy%04d@example.org', $u);
        q('INSERT OR IGNORE INTO users(email, email_norm, created_at, ip_hash, subnet_hash, fp_hash, is_demo) VALUES (?,?,?,?,?,?,1)',
          [$email, $email, $t - mt_rand(2, 18) * 86400, phash("demo-ip-$u"), phash('demo-net-' . intdiv($u, 3)), phash("demo-fp-$u")]);
        $uid = (int)qv('SELECT id FROM users WHERE email_norm = ?', [$email]);
        $users[] = $uid; $left[$uid] = $per;
    }
    foreach ($ids as $i => $pid) {
        $target = DEMO_VOTES[$i] ?? 0;
        $pool = array_values(array_filter($users, fn($u) => $left[$u] > 0));
        shuffle($pool);
        foreach (array_slice($pool, 0, $target) as $uid) {
            q("INSERT INTO votes(user_id, project_id, created_at, ip_hash, subnet_hash, fp_hash, risk_score, risk_reasons, status) VALUES (?,?,?,?,?,?,0,'[]','counted')",
              [$uid, $pid, $t - mt_rand(60, 14 * 86400), phash("demo-ip-$uid"), phash('demo-net-' . $uid), phash("demo-fp-$uid")]);
            $left[$uid]--;
        }
    }

    // Przykład wyłapanej „farmy” kont: 9 kont z jednego urządzenia w kilka minut – głosy czekają w panelu na weryfikację
    $farmTarget = $ids[19] ?? end($ids);
    for ($f = 1; $f <= 9; $f++) {
        $email = sprintf('konto%02d@farma-przyklad.example.org', $f);
        q('INSERT OR IGNORE INTO users(email, email_norm, created_at, ip_hash, subnet_hash, fp_hash, is_demo) VALUES (?,?,?,?,?,?,1)',
          [$email, $email, $t - 3600, phash('farm-ip'), phash('farm-net'), phash('farm-fp')]);
        $uid = (int)qv('SELECT id FROM users WHERE email_norm = ?', [$email]);
        $reasons = ['+' . min(40 + 10 * ($f - 1), 80) . ' ten sam odcisk urządzenia u ' . ($f - 1) . ' innych kont na ten projekt', '+10 konto młodsze niż 2 min',
                    '+' . min(10 + 5 * max(0, $f - 4), 40) . " $f nowych kont z tej podsieci w 24 h"];
        if ($f > 1) q("INSERT INTO votes(user_id, project_id, created_at, ip_hash, subnet_hash, fp_hash, risk_score, risk_reasons, status) VALUES (?,?,?,?,?,?,?,?,'pending')",
          [$uid, $farmTarget, $t - 3000 + $f * 20, phash('farm-ip'), phash('farm-net'), phash('farm-fp'), 60 + 10 * $f, json_encode($reasons, JSON_UNESCAPED_UNICODE)]);
    }

    // Oceny eksperckie (w imieniu administratora, który wczytuje dane)
    foreach ($all as $i => $p) {
        if (!isset(DEMO_ASSESSMENTS[$p['title']])) continue;
        [$sc, $comment] = DEMO_ASSESSMENTS[$p['title']];
        q('INSERT OR REPLACE INTO assessments(project_id, assessor_id, scores, comment, created_at) VALUES (?,?,?,?,?)',
          [$ids[$i], $adminId, json_encode(array_combine(EXPERT_CRITERIA, $sc)), $comment, $t - 5 * 86400]);
        if (!isset(DEMO_PIPELINE[$p['title']])) q("UPDATE projects SET pipeline = 'oceniona' WHERE id = ?", [$ids[$i]]);
    }

    // Przykładowe zgłoszenia zainteresowania (łączenie z odbiorcami)
    $byTitle = array_combine(array_column($all, 'title'), $ids);
    $contacts = [
        ['Cyfrowy asystent sołtysa', 'wdrozenie', 'Gmina wiejska (przykład)', 'Chcielibyśmy wdrożyć system w naszych 18 sołectwach. Czy możemy umówić wizytę studyjną?'],
        ['Cyfrowy asystent sołtysa', 'wdrozenie', 'Urząd Gminy (przykład)', 'Szukamy właśnie takiego rozwiązania dla seniorów. Prosimy o instrukcję wdrożenia.'],
        ['Inteligentny czujnik oblodzenia skrzydła drona', 'inwestor', 'Firma produkująca drony (przykład)', 'Jesteśmy zainteresowani wspólnymi testami i licencją. Prosimy o kontakt.'],
        ['Szkolny system monitoringu jakości powietrza', 'wdrozenie', 'Wydział Ochrony Środowiska (przykład)', 'Chcemy rozszerzyć sieć czujników na osiedla i sfinansować 20 kolejnych urządzeń.'],
        ['Algorytm planowania tras śmieciarek', 'partner', 'Spółka komunalna (przykład)', 'Chętnie przetestujemy algorytm u nas – obsługujemy 4 gminy.'],
        ['Biodegradowalne doniczki z łusek gryki', 'inwestor', 'Fundusz zalążkowy (przykład)', 'Technologia wpisuje się w naszą strategię. Prosimy o prezentację.'],
        ['Szkolny system powiadomień o zagrożeniach dla rodziców', 'podobne', 'Zespół Szkół Technicznych (przykład)', 'Mamy działające czujniki smogu – połączmy siły zamiast budować od zera.'],
    ];
    foreach ($contacts as $k => [$title, $role, $org, $msg])
        if (isset($byTitle[$title])) q('INSERT INTO contact_requests(project_id, user_id, role, organization, message, created_at) VALUES (?,?,?,?,?,?)',
            [$byTitle[$title], $users[$k], $role, $org, $msg, $t - mt_rand(1, 6) * 86400]);

    $pdo->exec('COMMIT');
    mt_srand();
    return $n;
}

function demo_remove(): int {
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    $ids = q('SELECT id FROM projects WHERE is_demo = 1')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        q('DELETE FROM votes WHERE project_id = ?', [$id]);
        q('DELETE FROM assessments WHERE project_id = ?', [$id]);
        q('DELETE FROM contact_requests WHERE project_id = ?', [$id]);
        q('UPDATE need_responses SET project_id = NULL WHERE project_id = ?', [$id]);
    }
    q('DELETE FROM need_responses WHERE need_id IN (SELECT id FROM needs WHERE is_demo = 1) OR user_id IN (SELECT id FROM users WHERE is_demo = 1)');
    q('DELETE FROM votes WHERE user_id IN (SELECT id FROM users WHERE is_demo = 1)');
    q('DELETE FROM contact_requests WHERE user_id IN (SELECT id FROM users WHERE is_demo = 1)');
    $n = q('DELETE FROM projects WHERE is_demo = 1')->rowCount();
    $n += q('DELETE FROM needs WHERE is_demo = 1')->rowCount();
    q('DELETE FROM users WHERE is_demo = 1');
    $pdo->exec('COMMIT');
    return $n;
}
