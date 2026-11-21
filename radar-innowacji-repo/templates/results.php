<div class="admin-head">
  <h1>Ranking innowacji</h1>
  <?php if (results_visible()): ?>
  <div class="seg">
    <a href="/wyniki" class="<?= $by === 'potencjal' ? 'on' : '' ?>">wg potencjału</a>
    <a href="/wyniki?wg=glosy" class="<?= $by === 'glosy' ? 'on' : '' ?>">wg głosów społeczności</a>
  </div>
  <?php endif; ?>
</div>
<p class="lead">Potencjał liczymy według <a href="/jak-oceniamy">7 jawnych kryteriów</a>. Głosy społeczności to jedno z nich (5%).</p>
<?php if (!$projects): ?>
  <div class="empty">Brak innowacji.</div>
<?php else: ?>
  <ol class="ranking">
    <?php foreach ($projects as $p): ?>
    <li>
      <div class="rank-head">
        <a href="/projekt/<?= (int)$p['id'] ?>"><?= h($p['title']) ?></a>
        <strong><?= $by === 'glosy' ? (int)$p['votes'] . ' ' . glosy($p['votes']) : (int)$p['total'] . '/100' ?></strong>
      </div>
      <progress class="bar" max="100" value="<?= $by === 'glosy' ? (int)min(100, $p['votes'] * 100 / max(1, max(array_column($projects, 'votes')))) : (int)$p['total'] ?>"></progress>
      <small class="meta"><?= h($p['institution']) ?> · <?= h(INNO_TYPES_SHORT[$p['innovation_type']] ?? '') ?> · rekomendacja: <?= h($p['path']) ?><?= $p['experts'] ? ' · ocena ekspercka' : '' ?></small>
    </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>
