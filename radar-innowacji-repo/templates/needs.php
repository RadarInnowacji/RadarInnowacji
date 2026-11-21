<div class="admin-head">
  <h1>Szukam rozwiązania</h1>
  <a class="btn" href="/zapotrzebowania/nowe">Opisz, czego szukasz</a>
</div>
<p class="lead">Masz problem, który ktoś mógł już rozwiązać? Zamiast budować od zera, opisz swoją potrzebę. Radar sam znajdzie innowacje, które już ją rozwiązują, i połączy Cię z ich twórcami.</p>

<ol class="steps-grid steps-3 how">
  <li><span class="step-no">01</span><h3>Opisz potrzebę</h3><p>Np. „szukamy taniego systemu zgłaszania usterek dla mieszkańców wsi”. Możesz też zaznaczyć, co oferujesz: wdrożenie, partnerstwo, finansowanie lub miejsce na testy.</p></li>
  <li><span class="step-no">02</span><h3>Zobacz gotowe rozwiązania</h3><p>Po weryfikacji moderatora pokażemy Ci pasujące innowacje z całego regionu. Widzisz je tylko Ty – Twoje zapotrzebowanie nie jest publiczne.</p></li>
  <li><span class="step-no">03</span><h3>Nawiąż kontakt</h3><p>Piszesz do twórców przez kartę innowacji. Twórcy pasujących rozwiązań też zobaczą Twoją potrzebę i mogą odezwać się sami.</p></li>
</ol>

<?php if (!$user): ?>
  <div class="empty"><a href="/logowanie?next=/zapotrzebowania">Zaloguj się</a>, aby opisać swoją potrzebę i zobaczyć dopasowane rozwiązania.</div>
<?php else: ?>

  <?php if ($for_me): ?>
  <section class="block">
    <h2>Kto szuka rozwiązań takich jak Twoje</h2>
    <p class="meta">Te organizacje opisały potrzebę, która pasuje do Twojej innowacji. Widzisz je, bo jesteś jej twórcą.</p>
    <div class="needs-list">
      <?php foreach ($for_me as $nid => $x): $n = $x['need']; ?>
      <article class="need-card">
        <p class="card-kicker"><?= h($n['org_type']) ?></p>
        <h3><a href="/zapotrzebowanie/<?= (int)$nid ?>"><?= h($n['title']) ?></a></h3>
        <p class="meta"><?= h($n['org_name']) ?> · pasuje do: <?= h(implode(', ', array_map(fn($p) => '„' . $p['title'] . '” (' . min(99, (int)round($p['score'] * 100)) . '%)', $x['projects']))) ?></p>
        <a class="btn btn-sm" href="/zapotrzebowanie/<?= (int)$nid ?>">Zobacz i odpowiedz</a>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="block">
    <h2>Twoje zapotrzebowania</h2>
    <?php if ($mine): ?>
    <div class="needs-list">
      <?php foreach ($mine as $n): ?>
      <article class="need-card">
        <p class="card-kicker"><?= h($n['org_type']) ?> · <span class="status s-<?= h($n['status']) ?>"><?= ['pending' => 'czeka na weryfikację', 'approved' => 'aktywne', 'rejected' => 'odrzucone'][$n['status']] ?? '' ?></span><?= $n['is_demo'] ? ' <span class="tag tag-demo">Przykład</span>' : '' ?></p>
        <h3><a href="/zapotrzebowanie/<?= (int)$n['id'] ?>"><?= h($n['title']) ?></a></h3>
        <?php if ($n['matches']): ?>
          <div class="need-matches"><b>Gotowe rozwiązania dla Ciebie:</b>
            <?php foreach ($n['matches'] as $m): ?><a href="/projekt/<?= $m['id'] ?>"><?= h($m['title']) ?> <span>(<?= min(99, (int)round($m['score'] * 100)) ?>%)</span></a><?php endforeach; ?>
          </div>
        <?php elseif ($n['status'] === 'approved'): ?><p class="meta">Na razie brak pasujących innowacji – powiadomimy Cię, gdy twórcy się odezwą.</p><?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty">Nie masz jeszcze zapotrzebowań. <a href="/zapotrzebowania/nowe">Opisz, czego szukasz</a>.</div>
    <?php endif; ?>
  </section>

  <?php if ($user['is_admin'] && $all): ?>
  <section class="block">
    <h2>Wszystkie zapotrzebowania <small class="meta">(widok administratora)</small></h2>
    <div class="needs-list">
      <?php foreach ($all as $n): ?>
      <article class="need-card">
        <p class="card-kicker"><?= h($n['org_type']) ?> · <?= h($n['org_name']) ?> · <span class="status s-<?= h($n['status']) ?>"><?= h($n['status']) ?></span><?= $n['is_demo'] ? ' <span class="tag tag-demo">Przykład</span>' : '' ?></p>
        <h3><a href="/zapotrzebowanie/<?= (int)$n['id'] ?>"><?= h($n['title']) ?></a></h3>
        <?php if ($n['matches']): ?><div class="need-matches"><b>Dopasowania:</b>
          <?php foreach ($n['matches'] as $m): ?><a href="/projekt/<?= $m['id'] ?>"><?= h($m['title']) ?> <span>(<?= min(99, (int)round($m['score'] * 100)) ?>%)</span></a><?php endforeach; ?></div><?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
<?php endif; ?>
