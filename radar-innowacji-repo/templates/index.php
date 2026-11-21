<section class="steps">
  <h2 class="section-title">Od pomysłu do wdrożenia</h2>
  <ol class="steps-grid steps-4">
    <li><span class="step-no">01</span><h3>Zgłoszenie</h3><p>Twórca lub ktoś, kto zna innowację, wypełnia uporządkowaną kartę. System od razu podpowiada podobne rozwiązania.</p></li>
    <li><span class="step-no">02</span><h3>Ocena potencjału</h3><p>Wstępna ocena automatyczna według 7 jawnych kryteriów, uzupełniana przez ekspertów.</p></li>
    <li><span class="step-no">03</span><h3>Rekomendacja ścieżki</h3><p>Transfer, komercjalizacja, rozwój, ochrona IP lub finansowanie – z uzasadnieniem, krokami i miernikami.</p></li>
    <li><span class="step-no">04</span><h3>Połączenie</h3><p>Dopasowujemy innowacje do zapotrzebowań instytucji, firm i inwestorów oraz umożliwiamy bezpośredni kontakt.</p></li>
  </ol>
  <p class="steps-more"><a href="/jak-oceniamy">Zobacz, jak dokładnie oceniamy innowacje →</a></p>
</section>

<section id="projekty">
  <h2 class="section-title">Innowacje na radarze</h2>
  <form class="filters" method="get" action="/#projekty">
    <input type="search" name="q" value="<?= h($q) ?>" placeholder="Szukaj innowacji, instytucji, problemu…" aria-label="Szukaj">
    <select name="obszar" aria-label="Obszar">
      <option value="">Wszystkie obszary</option>
      <?php foreach (AREAS as $a): ?><option <?= $a === $area ? 'selected' : '' ?>><?= h($a) ?></option><?php endforeach; ?>
    </select>
    <select name="typ" aria-label="Rodzaj innowacji">
      <option value="">Każdy rodzaj</option>
      <?php foreach (INNO_TYPES_SHORT as $k => $l): ?><option value="<?= $k ?>" <?= $k === $type ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
    </select>
    <select name="kategoria" aria-label="Instytucja">
      <option value="">Każda instytucja</option>
      <?php foreach (CATEGORIES as $c): ?><option <?= $c === $cat ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
    </select>
    <select name="sort" aria-label="Sortowanie">
      <option value="potencjal" <?= $sort === 'potencjal' ? 'selected' : '' ?>>Największy potencjał</option>
      <option value="nowe" <?= $sort === 'nowe' ? 'selected' : '' ?>>Najnowsze</option>
      <?php if (results_visible()): ?><option value="popularne" <?= $sort === 'popularne' ? 'selected' : '' ?>>Najwięcej głosów</option><?php endif; ?>
    </select>
    <button class="btn">Szukaj</button>
  </form>

  <?php if ($projects): ?>
  <div class="grid">
    <?php foreach ($projects as $p): $ci = array_search($p['category'], CATEGORIES, true); ?>
    <article class="card c-<?= $ci === false ? 0 : $ci ?>">
      <a class="card-head" href="/projekt/<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
        <span class="card-score"><?= score_ring($p['total'], 58) ?></span>
        <span class="card-label"><?= h($p['category']) ?></span>
        <?php if ($p['is_demo']): ?><span class="demo-badge">Przykład</span><?php endif; ?>
      </a>
      <div class="card-body">
        <p class="card-kicker"><?= h(INNO_TYPES_SHORT[$p['innovation_type']] ?? '') ?> · <?= h(STAGES[$p['stage']] ?? '') ?></p>
        <h3><a href="/projekt/<?= (int)$p['id'] ?>"><?= h($p['title']) ?></a></h3>
        <p class="meta"><?= h($p['institution']) ?> · <?= h($p['location']) ?></p>
        <p class="card-summary"><?= h($p['summary']) ?></p>
        <?php if ($p['path']): ?><p class="card-path"><span>Rekomendacja:</span> <?= h($p['path']) ?></p><?php endif; ?>
      </div>
      <div class="card-actions">
        <?php if (results_visible()): ?><span class="count"><b><?= (int)$p['votes'] ?></b> <?= glosy($p['votes']) ?></span>
        <?php else: ?><a class="more" href="/projekt/<?= (int)$p['id'] ?>">Karta innowacji</a><?php endif; ?>
        <?php vote_button($p, isset($voted[$p['id']]), $votes_left); ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty">Brak innowacji<?= ($q !== '' || $cat !== '' || $type !== '' || $area !== '') ? ' pasujących do filtrów' : '' ?>. <a href="/zglos">Zgłoś pierwszą!</a></div>
  <?php endif; ?>
</section>
