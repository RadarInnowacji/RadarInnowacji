<?php $admin = $user && $user['is_admin']; $areas = csv_list($p['areas']); $needsList = csv_list($p['needs']); ?>
<article class="detail-wide">
  <?php if ($p['status'] !== 'approved'): ?><div class="flash flash-info">Ta karta czeka na weryfikację moderatora – na razie widzisz ją tylko Ty.</div><?php endif; ?>

  <header class="inno-head">
    <div>
      <p class="tags">
        <span class="tag"><?= h($p['category']) ?></span>
        <span class="tag tag-light"><?= h(INNO_TYPES_SHORT[$p['innovation_type']] ?? '') ?></span>
        <?php foreach ($areas as $a): ?><a class="tag tag-outline" href="/?obszar=<?= urlencode($a) ?>#projekty"><?= h($a) ?></a><?php endforeach; ?>
        <?php if ($p['is_demo']): ?><span class="tag tag-demo">Przykład</span><?php endif; ?>
      </p>
      <h1><?= h($p['title']) ?></h1>
      <p class="meta"><?= h($p['institution']) ?> · <?= h($p['location']) ?><?= $p['creators'] ? ' · ' . h($p['creators']) : '' ?></p>
      <p class="lead"><?= h($p['summary']) ?></p>
    </div>
    <aside class="score-box">
      <?= score_ring($ev['total'], 110) ?>
      <p class="score-label"><?= score_label($ev['total']) ?></p>
      <p class="meta"><?= $ev['experts'] ? 'Ocena ekspercka (' . $ev['experts'] . ') + automatyczna' : 'Wstępna ocena automatyczna' ?></p>
      <a class="small-link" href="#ocena">Szczegóły oceny ↓</a>
    </aside>
  </header>

  <section class="block">
    <h2>Etap procesu</h2>
    <?php pipeline_steps($p['pipeline']); ?>
    <?php if ($admin): ?>
      <form method="post" action="/admin/projekt/<?= (int)$p['id'] ?>/etap" class="inline-form"><?= csrf_field() ?>
        <select name="pipeline"><?php foreach (PIPELINE as $k => [$l]): ?><option value="<?= $k ?>" <?= $k === $p['pipeline'] ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select>
        <button class="btn btn-sm btn-ghost">Zmień etap</button>
        <a class="btn btn-sm" href="/admin/ocena/<?= (int)$p['id'] ?>">Oceń jako ekspert</a>
      </form>
      <div class="inline-form"><?= delete_button('/admin/projekt/' . (int)$p['id'] . '/usun', 'innowację „' . $p['title'] . '” wraz z głosami i ocenami', 'Usuń innowację') ?></div>
    <?php endif; ?>
  </section>

  <div class="two-col">
    <div>
      <section class="block">
        <h2>Problem</h2>
        <div class="prose"><?= h($p['problem']) ?></div>
        <h2>Rozwiązanie</h2>
        <div class="prose"><?= h($p['description']) ?></div>
        <h2>Efekty i mierniki</h2>
        <div class="prose"><?= h($p['results']) ?></div>
        <h2>Kto jeszcze może skorzystać</h2>
        <div class="prose"><?= h($p['impact']) ?></div>
        <?php if ($p['link']): ?><p><a href="<?= h($p['link']) ?>" rel="nofollow noopener ugc" target="_blank">Więcej o innowacji ↗</a></p><?php endif; ?>
      </section>
    </div>
    <aside>
      <dl class="facts">
        <dt>Etap rozwoju</dt><dd><?= h(STAGES[$p['stage']] ?? '') ?></dd>
        <dt>Efekty</dt><dd><?= h(EVIDENCE[$p['evidence']] ?? '') ?></dd>
        <dt>Problem dotyczy</dt><dd><?= h(SCOPES[$p['scope']] ?? '') ?></dd>
        <dt>Koszt wdrożenia gdzie indziej</dt><dd><?= h(explode(' – ', COSTS[$p['cost']] ?? '')[0]) ?></dd>
        <dt>Potencjał komercyjny</dt><dd><?= h(explode(' – ', COMMERCIAL[$p['commercial']] ?? '')[0]) ?></dd>
        <?php if ($needsList): ?><dt>Twórcy szukają</dt><dd><ul class="plain"><?php foreach ($needsList as $n): ?><li><?= h(NEEDS[$n] ?? $n) ?></li><?php endforeach; ?></ul></dd><?php endif; ?>
      </dl>
      <div class="vote-box">
        <?php if (results_visible()): ?><div><span class="big"><?= (int)$p['votes'] ?></span> <?= glosy($p['votes']) ?></div><?php else: ?><div>Poprzyj</div><?php endif; ?>
        <?php vote_button($p, $voted, $votes_left); ?>
      </div>
    </aside>
  </div>

  <section class="block" id="ocena">
    <h2>Ocena potencjału: <?= (int)$ev['total'] ?>/100</h2>
    <p class="meta">Każde kryterium w skali 1–5, z wagą. <?= $ev['experts'] ? 'Kryteria ocenione przez ekspertów zastępują ocenę automatyczną.' : 'To ocena wstępna – eksperci mogą ją skorygować.' ?> <a href="/jak-oceniamy">Jak liczymy?</a></p>
    <table class="crit">
      <thead><tr><th>Kryterium</th><th>Waga</th><th>Ocena</th><th></th><th>Uzasadnienie</th></tr></thead>
      <tbody>
      <?php foreach (CRITERIA as $c => [$label, $w, $desc]): ?>
        <tr>
          <th scope="row"><?= h($label) ?></th>
          <td><?= $w ?>%</td>
          <td class="nowrap"><b><?= fmt_score($ev['scores'][$c]) ?></b>/5</td>
          <td class="bar-cell"><meter min="1" max="5" low="2.5" high="3.9" optimum="5" value="<?= h($ev['scores'][$c]) ?>"></meter></td>
          <td class="meta"><?= h($ev['why'][$c] ?? '') ?><?php if ($ev['source'][$c] === 'ekspert'): ?> <span class="src">Ekspert: <?= fmt_score($ev['scores'][$c]) ?> (auto: <?= (int)$ev['auto'][$c] ?>)</span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php foreach ($ev['comments'] as $cm): ?><blockquote class="expert-note"><b>Komentarz eksperta:</b> <?= h($cm['text']) ?></blockquote><?php endforeach; ?>
  </section>

  <section class="block" id="rekomendacja">
    <h2>Rekomendowana ścieżka</h2>
    <div class="paths">
      <?php foreach ($paths as $i => $path): ?>
      <div class="path <?= $i === 0 ? 'primary' : '' ?>">
        <div class="path-head">
          <span class="path-rank"><?= $i === 0 ? 'Główna rekomendacja' : 'Ścieżka uzupełniająca' ?></span>
          <span class="path-fit">dopasowanie <?= (int)$path['fit'] ?>%</span>
        </div>
        <h3><?= h($path['name']) ?></h3>
        <p><?= h($path['desc']) ?></p>
        <p class="why"><b>Dlaczego:</b> <?= h(implode(' · ', $path['reasons'])) ?></p>
        <?php if ($i === 0): ?>
        <div class="path-cols">
          <div><h4>Następne kroki</h4><ol><?php foreach ($path['steps'] as $st): ?><li><?= h($st) ?></li><?php endforeach; ?></ol></div>
          <div><h4>Mierniki skuteczności</h4><ul><?php foreach ($path['kpi'] as $k): ?><li><?= h($k) ?></li><?php endforeach; ?></ul></div>
          <div><h4>Gdzie szukać wsparcia</h4><ul><?php foreach ($path['support'] as $k): ?><li><?= h($k) ?></li><?php endforeach; ?></ul></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="two-col <?= $needs !== null ? 'even' : 'single' ?>">
    <?php if ($needs !== null): ?>
    <section class="block">
      <h2>Kto szuka takiego rozwiązania</h2>
      <p class="meta private-note">Widzisz to jako <?= $user['is_admin'] ? 'administrator' : 'twórca' ?> – inni użytkownicy tej sekcji nie widzą.</p>
      <?php if ($needs): ?>
        <ul class="match-list">
        <?php foreach ($needs as $n): ?>
          <li><a href="/zapotrzebowanie/<?= $n['id'] ?>"><?= h($n['title']) ?></a>
            <span class="meta"><?= h($n['row']['org_name']) ?> · <?= h($n['row']['org_type']) ?></span>
            <span class="match">dopasowanie <?= min(99, (int)round($n['score'] * 100)) ?>% · wspólne: <?= h(implode(', ', array_merge($n['areas'] ?? [], array_slice($n['shared'], 0, 3)))) ?></span></li>
        <?php endforeach; ?>
        </ul>
        <p class="meta">Otwórz zapotrzebowanie, żeby odpowiedzieć organizacji.</p>
      <?php else: ?><p class="meta">Na razie nikt nie szuka podobnego rozwiązania. Gdy ktoś opisze taką potrzebę, zobaczysz ją tutaj i w „Moim koncie”.</p><?php endif; ?>
    </section>
    <?php endif; ?>
    <section class="block">
      <h2>Podobne innowacje</h2>
      <?php if ($similar): ?>
        <ul class="match-list">
        <?php foreach ($similar as $s): ?>
          <li><a href="/projekt/<?= $s['id'] ?>"><?= h($s['title']) ?></a>
            <span class="meta"><?= h($s['row']['institution']) ?> · <?= h($s['row']['location']) ?></span>
            <span class="match">podobieństwo <?= (int)round($s['score'] * 100) ?>% · wspólne słowa: <?= h(implode(', ', $s['shared'])) ?></span></li>
        <?php endforeach; ?>
        </ul>
        <p class="meta">Zanim zaczniecie rozwijać rozwiązanie od nowa – porozmawiajcie z autorami podobnych.</p>
      <?php else: ?><p class="meta">Nie znaleziono podobnych innowacji w bazie – to dobry znak dla nowatorstwa.</p><?php endif; ?>
    </section>
  </div>

  <section class="block">
    <h2>Sprawdź w zewnętrznych bazach</h2>
    <p class="meta">Słowa kluczowe do wyszukiwania: <code class="kw"><?= h($ext['keywords']) ?></code></p>
    <ul class="ext-links">
      <?php foreach ($ext['links'] as [$name, $url, $desc]): ?><li><a href="<?= h($url) ?>" target="_blank" rel="noopener"><?= h($name) ?> ↗</a><span class="meta"><?= h($desc) ?></span></li><?php endforeach; ?>
    </ul>
  </section>

  <section class="block contact-box" id="kontakt">
    <h2>Chcesz wdrożyć, rozwinąć lub sfinansować tę innowację?</h2>
    <p>Napisz do twórców – przekażemy wiadomość, a oni odpowiedzą bezpośrednio na Twój adres. <?= $contacts ? "Do tej pory zainteresowanie zgłosiło: <b>$contacts</b>." : '' ?></p>
    <?php if ($user): ?>
    <form method="post" action="/projekt/<?= (int)$p['id'] ?>/kontakt" class="form form-plain">
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <div class="row">
        <label>Rodzaj zainteresowania
          <select name="role" required>
            <option value="">— wybierz —</option>
            <option value="wdrozenie">Chcę wdrożyć to rozwiązanie u siebie</option>
            <option value="partner">Chcę współpracować / rozwijać</option>
            <option value="inwestor">Mogę sfinansować / zainwestować</option>
            <option value="mentor">Mogę pomóc jako ekspert / mentor</option>
            <option value="podobne">Pracujemy nad podobnym rozwiązaniem</option>
          </select></label>
        <label>Organizacja (opcjonalnie) <input name="organization" maxlength="160"></label>
      </div>
      <label>Wiadomość <textarea name="message" rows="4" required minlength="20" maxlength="2000"></textarea></label>
      <button class="btn">Wyślij do twórców</button>
    </form>
    <?php else: ?><a class="btn" href="/logowanie?next=<?= urlencode('/projekt/' . $p['id'] . '#kontakt') ?>">Zaloguj się, aby napisać</a><?php endif; ?>
  </section>
</article>
