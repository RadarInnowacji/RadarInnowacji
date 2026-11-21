# Radar Innowacji

**Od ukrytego pomysłu do wdrożenia w kolejnej instytucji.**
Rozwiązanie wyzwania *„Radar Innowacji”* Podkarpackiego Centrum Innowacji (HackNation 2026).

🌐 Działająca wersja: **https://radarinnowacji.pl**

## Problem
Wartościowe innowacje powstają w szkołach, uczelniach, samorządach i instytucjach publicznych, ale zwykle zostają w organizacji, w której powstały. Brakuje prostego mechanizmu, który pozwala je odnajdywać, porównywać ich potencjał i kierować do odbiorców. Przez to różne instytucje rozwiązują te same problemy od zera.

## Rozwiązanie
Platforma prowadzi innowację przez cały proces: **zgłoszenie → ocena potencjału → rekomendacja ścieżki → połączenie z partnerami**.

| Funkcja | Opis |
|---|---|
| Karta innowacji | Uporządkowane informacje: problem, rozwiązanie, efekty, etap, skala, koszt transferu, obszary, potrzeby twórców. Także innowacje organizacyjne, edukacyjne i społeczne. |
| Podpowiedź podobnych | Już podczas wypełniania formularza system pokazuje podobne innowacje z bazy (TF-IDF + podobieństwo cosinusowe) – mniej dublowania prac. |
| Ocena potencjału | 7 jawnych kryteriów z wagami (skala 20%, transfer 20%, nowatorstwo 15%, dojrzałość 15%, efekty 15%, komercja 10%, społeczność 5%). Ocena wstępna automatyczna, z uzasadnieniem każdej liczby; eksperci mogą ją skorygować. |
| Rekomendacja ścieżki | Transfer, komercjalizacja, rozwój i pilotaż, ochrona IP, finansowanie – z % dopasowania, uzasadnieniem, krokami i **miernikami skuteczności**. |
| Łączenie | Organizacje opisują, czego szukają; system dopasowuje innowacje w obie strony. Dopasowania są niepubliczne – widzą je tylko zainteresowane strony. Kontakt bez ujawniania adresów e-mail. |
| Etapy procesu | Zgłoszona → W ocenie → Oceniona → Łączenie → Wdrażanie → Wdrożona poza miejscem powstania. |
| Bazy zewnętrzne | Słowa kluczowe i linki do UPRP, EPO, Google Patents, PBN, Interreg, NCBR. |
| Bezpieczeństwo | Logowanie linkiem e-mail (bez haseł), CSRF, CSP, IP tylko jako HMAC (RODO), wielowarstwowa ochrona głosowania przed botami (proof-of-work, rate limiting, wykrywanie wielu kont z jednego urządzenia, wstrzymywanie podejrzanych głosów do weryfikacji). |
| Panel moderatora | Moderacja, ocena ekspercka, usuwanie, weryfikacja podejrzanych głosów, dziennik działań, eksport CSV, dane demonstracyjne jednym kliknięciem. |

Szczegóły metody oceny, źródła danych i ograniczenia: strona **/jak-oceniamy** w aplikacji (`templates/method.php`), reguły w `lib/innovation.php`.

## Technologie
PHP 8.1+ · SQLite (PDO) · czysty HTML/CSS/JS (bez frameworków) · działa na zwykłym hostingu współdzielonym.

## Uruchomienie lokalne (ok. 5 minut)

Projekt nie wymaga bazy danych na serwerze, Node.js, Dockera ani Composera. Wystarczy **PHP 8.1 lub nowszy** z rozszerzeniami `pdo_sqlite` i `mbstring` (zwykle są w standardowej instalacji).

### Krok 1. Pobierz kod
- Przez Git: `git clone <adres-tego-repozytorium>` i wejdź do utworzonego folderu,
- albo na GitHubie: **Code → Download ZIP**, rozpakuj i otwórz rozpakowany folder.

### Krok 2. Sprawdź, czy masz PHP
W terminalu (Windows: *PowerShell* lub *Wiersz polecenia*) wpisz:
```
php -v
```
Jeśli widzisz `PHP 8.1` lub nowszy, przejdź do kroku 3. W przeciwnym razie zainstaluj PHP:

| System | Instalacja |
|---|---|
| **Windows** | Najprościej: `winget install PHP.PHP.8.3` w PowerShellu, potem **zamknij i otwórz terminal ponownie**. Alternatywnie: pobierz „VS17 x64 Non Thread Safe” Zip z https://windows.php.net/download, rozpakuj do `C:\php` i dodaj `C:\php` do zmiennej środowiskowej PATH. |
| **macOS** | `brew install php` (wymaga Homebrew: https://brew.sh) |
| **Linux (Ubuntu/Debian)** | `sudo apt install php-cli php-sqlite3 php-mbstring` |

### Krok 3. Uruchom
**Windows:** kliknij dwukrotnie plik **`start.bat`** w folderze projektu
(albo w terminalu w folderze projektu: `.\start.bat`).

**macOS / Linux:** w terminalu w folderze projektu:
```
sh start.sh
```

Skrypt automatycznie:
1. sprawdza wersję PHP i wymagane rozszerzenia (na Windows w razie potrzeby włącza je tylko dla tego uruchomienia),
2. tworzy plik konfiguracyjny `.env` z losowymi kluczami w **trybie lokalnym**,
3. tworzy bazę SQLite w `data/`, konto administratora `admin@example.com` i wczytuje **dane przykładowe** (25 innowacji, zapotrzebowania, głosy, oceny eksperckie, kontakty),
4. uruchamia serwer i otwiera przeglądarkę na **http://localhost:8000**.

Serwer zatrzymasz w terminalu skrótem **Ctrl+C**. Kolejne uruchomienia niczego nie nadpisują.

<details><summary>Uruchomienie ręczne (bez skryptów)</summary>

```
php setup.php
php -S localhost:8000 dev-router.php
```
Inny port: `php setup.php --port=8080` i `php -S localhost:8080 dev-router.php`. Bez danych przykładowych: `php setup.php --bez-demo`.
</details>

### Krok 4. Zaloguj się jako administrator
1. Kliknij **Zaloguj się**, wpisz `admin@example.com`, odczekaj ok. 3 sekundy (zabezpieczenie przed botami) i kliknij **Wyślij link logowania**.
2. W trybie lokalnym nie jest potrzebna poczta – **link logowania pojawi się od razu na stronie**. Kliknij go, a potem **Zaloguj mnie**.
3. W menu pojawi się **Panel** (moderacja, oceny eksperckie, weryfikacja głosów, eksport CSV).

Aby zobaczyć platformę oczami zwykłego użytkownika, zaloguj się w drugiej przeglądarce (lub oknie prywatnym) dowolnym innym adresem, np. `gmina@example.org`.

### Proponowany scenariusz oceny (zgodny z wyzwaniem a–e)
1. **Strona główna** – innowacje posortowane według potencjału, z rekomendowaną ścieżką.
2. **Karta „Cyfrowy asystent sołtysa”** – uporządkowane informacje (b), tabela oceny 7 kryteriów z uzasadnieniami (c), rekomendowana ścieżka z krokami, **miernikami skuteczności** i źródłami wsparcia (d, e), podobne innowacje, linki do baz zewnętrznych.
3. **Karta „Inteligentny czujnik oblodzenia skrzydła drona”** – drugi przypadek: innowacja technologiczna, inna ścieżka (ochrona IP, komercjalizacja).
4. **Zgłoś innowację** (a) – podczas wpisywania opisu problemu pojawia się podpowiedź podobnych rozwiązań.
5. **Szukam rozwiązania** – organizacja opisuje potrzebę i dostaje dopasowane innowacje; twórca widzi pasujące potrzeby i może odpowiedzieć.
6. **Jak oceniamy** – jawne kryteria, wagi, reguły rekomendacji, źródła danych i ograniczenia.
7. **Panel** (jako admin) – „Oceń jako ekspert” na dowolnej karcie zmienia ocenę i przelicza rekomendację; sekcja głosów do weryfikacji pokazuje wyłapaną przykładową „farmę” kont.

### Rozwiązywanie problemów
| Problem | Rozwiązanie |
|---|---|
| `php` nie jest rozpoznawany | PHP nie jest w zmiennej PATH. Po instalacji zamknij i otwórz terminal ponownie. |
| `Brak rozszerzeń PHP: pdo_sqlite, mbstring` | Windows: użyj `start.bat` (włącza je automatycznie) albo w pliku `php.ini` (obok `php.exe`; jeśli go nie ma, skopiuj `php.ini-development` jako `php.ini`) usuń średnik przed `extension=pdo_sqlite` i `extension=mbstring`. Linux: `sudo apt install php-sqlite3 php-mbstring`. |
| `Address already in use` / port zajęty | Uruchom na innym porcie: `start.bat 8080` lub `sh start.sh 8080`. |
| Strona wyświetla „Brak SECRET_KEY” | Nie utworzono `.env` – uruchom `php setup.php`. |
| Chcę zacząć od zera | Zatrzymaj serwer, usuń plik `data/radar.db` (i ewentualnie `.env`) i uruchom ponownie. |

## Wdrożenie (hosting z Apache + PHP)
Wgraj zawartość repozytorium do katalogu domeny, utwórz `.env` na podstawie `.env.example`. Plik `.htaccess` kieruje ruch do `index.php` i blokuje dostęp do `lib/`, `templates/`, `data/` i `.env`. Baza SQLite tworzy się i aktualizuje automatycznie w `data/`.

## Struktura
```
index.php            router, strony, API, panel
lib/core.php         konfiguracja, baza, bezpieczeństwo, poczta, ochrona głosowania
lib/innovation.php   kryteria oceny, rekomendacje, podobieństwo, dopasowania
lib/demo.php         dane demonstracyjne (+ demo_more.php)
lib/schema.sql       schemat bazy
templates/           widoki
static/              CSS, JS (proof-of-work w Web Workerze), fonty
```

## Ograniczenia
- Ocena wstępna opiera się na deklaracjach zgłaszającego – dlatego może ją skorygować ekspert.
- Podobieństwo tekstów nie rozumie znaczenia (synonimy) – decyzję podejmuje człowiek.
- Wyszukiwanie w bazach zewnętrznych nie jest jeszcze automatyczne (słowa kluczowe + linki).
