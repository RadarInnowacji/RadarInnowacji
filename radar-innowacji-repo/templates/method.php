<div class="narrow prose-page">
  <h1>Jak oceniamy innowacje</h1>
  <p class="lead">Radar nie jest „czarną skrzynką”. Każda ocena i rekomendacja wynika z jawnych kryteriów i reguł opisanych na tej stronie, a na karcie innowacji widać uzasadnienie każdej liczby.</p>

  <h2>1. Kryteria i wagi</h2>
  <p>Każde kryterium oceniamy w skali 1–5. Wynik końcowy (0–100) to suma ważona. Ocena 1 daje 0 punktów, ocena 5 daje pełną wagę.</p>
  <table class="crit">
    <thead><tr><th>Kryterium</th><th>Waga</th><th>Co mierzy</th><th>Jak liczymy ocenę wstępną</th></tr></thead>
    <tbody>
      <tr><th>Skala problemu</th><td>20%</td><td><?= h(CRITERIA['skala'][2]) ?></td><td class="meta">Problem dotyczy: naszej instytucji → 2, instytucji w regionie → 4, w Polsce/za granicą → 5.</td></tr>
      <tr><th>Nowatorstwo</th><td>15%</td><td><?= h(CRITERIA['nowatorstwo'][2]) ?></td><td class="meta">5 minus podobieństwo do najbliższej innowacji w bazie (im bardziej podobna, tym niżej).</td></tr>
      <tr><th>Dojrzałość</th><td>15%</td><td><?= h(CRITERIA['dojrzalosc'][2]) ?></td><td class="meta">Pomysł → 1, prototyp/pilotaż → 3, działa u nas → 4, działa w kilku miejscach → 5.</td></tr>
      <tr><th>Potwierdzone efekty</th><td>15%</td><td><?= h(CRITERIA['efekty'][2]) ?></td><td class="meta">Brak pomiarów → 1, obserwacje → 3, mierzalne wyniki → 5. +1, gdy w opisie efektów są konkretne liczby.</td></tr>
      <tr><th>Łatwość transferu</th><td>20%</td><td><?= h(CRITERIA['transfer'][2]) ?></td><td class="meta">Koszt niski → 5, średni → 3, wysoki → 2. +1 gdy działa w kilku miejscach, −1 na etapie pomysłu.</td></tr>
      <tr><th>Potencjał komercyjny</th><td>10%</td><td><?= h(CRITERIA['komercja'][2]) ?></td><td class="meta">Nie → 1, być może → 3, tak → 4 (5 dla innowacji technologicznej).</td></tr>
      <tr><th>Poparcie społeczności</th><td>5%</td><td><?= h(CRITERIA['spolecznosc'][2]) ?></td><td class="meta">1 + 4 × (głosy innowacji ÷ głosy najpopularniejszej). Brak głosów w bazie → 3.</td></tr>
    </tbody>
  </table>
  <p><b>Ocena ekspercka.</b> Moderatorzy i eksperci oceniają sześć pierwszych kryteriów. Ich ocena (średnia, jeśli ocenia kilka osób) zastępuje ocenę wstępną w danym kryterium. Na karcie widać obie wartości.</p>

  <h2>2. Rekomendacja ścieżki</h2>
  <p>Dla każdej z pięciu ścieżek liczymy dopasowanie (0–100%) z ocen kryteriów. Pokazujemy główną rekomendację i do dwóch uzupełniających (powyżej 35%).</p>
  <ul>
    <li><b>Transfer i replikacja</b>: łatwość transferu 35% + skala problemu 25% + potwierdzone efekty 25% + dojrzałość 15%.</li>
    <li><b>Komercjalizacja</b>: potencjał komercyjny 45% + dojrzałość 25% + nowatorstwo 15% + skala 15%. Gdy potencjał komercyjny ma ocenę 1, wynik jest obniżany do 30%.</li>
    <li><b>Rozwój i pilotaż</b>: (niska dojrzałość) 50% + (brak pomiarów efektów) 30% + nowatorstwo 20%.</li>
    <li><b>Ochrona własności intelektualnej</b> (tylko innowacje technologiczne): nowatorstwo 50% + potencjał komercyjny 30% + dojrzałość 20%.</li>
    <li><b>Pozyskanie finansowania</b>: zgłoszona potrzeba finansowania 40% + wysoki koszt wdrożenia 30% + skala problemu 30%, razy 0,85. To zwykle ścieżka wspierająca, więc wygrywa tylko wtedy, gdy inne pasują słabiej.</li>
  </ul>
  <p>Każda ścieżka ma przypisane konkretne następne kroki, <b>mierniki skuteczności</b> (po czym poznamy, że działa) oraz typowe źródła wsparcia.</p>

  <h2>3. Podobne innowacje i dopasowanie do zapotrzebowań</h2>
  <p>Teksty zgłoszeń porównujemy metodą TF-IDF z podobieństwem cosinusowym: rzadkie, charakterystyczne słowa ważą więcej niż popularne. Polskie słowa skracamy do rdzenia, żeby różne formy liczyły się jako to samo słowo. Wspólny obszar tematyczny daje premię przy dopasowaniu do zapotrzebowań. Przy każdym dopasowaniu pokazujemy wspólne słowa, więc wiadomo, skąd się wzięło.</p>
  <p>Podobne innowacje podpowiadamy już w trakcie wypełniania formularza, żeby ograniczyć dublowanie prac.</p>
  <p><b>Zapotrzebowania nie są publiczne.</b> Opis potrzeby i dopasowane rozwiązania widzi tylko organizacja, która go dodała, moderatorzy oraz twórcy innowacji, które do niego pasują. Obie strony mogą się do siebie odezwać, a osoby postronne nie widzą niczego.</p>

  <h2>4. Źródła danych</h2>
  <ul>
    <li>Zgłoszenia twórców i osób, które znają innowację (formularz z pytaniami zamkniętymi i otwartymi).</li>
    <li>Oceny ekspertów i moderatorów.</li>
    <li>Zapotrzebowania instytucji, firm i inwestorów.</li>
    <li>Głosy zalogowanych użytkowników.</li>
    <li>Na karcie każdej innowacji: słowa kluczowe i linki do baz patentowych (UPRP, EPO), publikacji naukowych (PBN) i projektów (Interreg, NCBR) do ręcznego sprawdzenia stanu techniki.</li>
  </ul>

  <h2>5. Ograniczenia</h2>
  <ul>
    <li>Ocena wstępna opiera się na deklaracjach zgłaszającego. Dlatego jest „wstępna”, a ekspert może ją skorygować.</li>
    <li>Podobieństwo tekstów nie rozumie znaczenia: dwa opisy tego samego pomysłu innymi słowami mogą mieć niski wynik. Ostateczną decyzję podejmuje człowiek.</li>
    <li>Wyniki porównań zależą od liczby zgłoszeń w bazie. Im więcej innowacji, tym trafniejsze nowatorstwo i dopasowania.</li>
    <li>Wyszukiwanie w zewnętrznych bazach nie jest jeszcze zautomatyzowane: podajemy słowa kluczowe i linki.</li>
  </ul>

  <h2>6. Co dzieje się z innowacją po zgłoszeniu</h2>
  <ol>
    <?php foreach (PIPELINE as [$label, $desc]): ?><li><b><?= h($label) ?></b>: <?= h($desc) ?></li><?php endforeach; ?>
  </ol>
</div>
