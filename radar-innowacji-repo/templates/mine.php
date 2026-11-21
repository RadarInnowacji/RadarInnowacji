<h1>Moje konto</h1>
<p class="meta"><?= h($user['email']) ?> · pozostałe głosy: <strong><?= (int)$votes_left ?></strong></p>
<form method="post" action="/wyloguj" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost">Wyloguj się</button></form>

<h2>Moje głosy</h2>
<?php if ($votes): ?>
  <ul class="list"><?php foreach ($votes as $v): ?><li><a href="/projekt/<?= (int)$v['id'] ?>"><?= h($v['title']) ?></a> <span class="meta"><?= dt($v['created_at']) ?></span></li><?php endforeach; ?></ul>
<?php else: ?><p class="meta">Jeszcze nie głosowałeś/aś.</p><?php endif; ?>

<h2>Moje innowacje</h2>
<?php if ($projects): ?>
  <ul class="list"><?php foreach ($projects as $p): ?>
    <li><a href="/projekt/<?= (int)$p['id'] ?>"><?= h($p['title']) ?></a>
      <span class="status s-<?= h($p['status']) ?>"><?= ['pending' => 'w moderacji', 'approved' => 'opublikowany', 'rejected' => 'odrzucony'][$p['status']] ?? h($p['status']) ?></span>
      <?php if ($p['review_note']): ?><span class="meta">– <?= h($p['review_note']) ?></span><?php endif; ?></li>
  <?php endforeach; ?></ul>
<?php else: ?><p class="meta">Brak zgłoszeń. <a href="/zglos">Zgłoś innowację</a></p><?php endif; ?>

<?php if ($for_me): ?>
<h2>Kto szuka rozwiązań takich jak Twoje</h2>
<ul class="list"><?php foreach ($for_me as $nid => $x): ?>
  <li><a href="/zapotrzebowanie/<?= (int)$nid ?>"><?= h($x['need']['title']) ?></a> <span class="meta">– <?= h($x['need']['org_name']) ?>, pasuje do: <?= h(implode(', ', array_column($x['projects'], 'title'))) ?></span></li>
<?php endforeach; ?></ul>
<?php endif; ?>

<h2>Moje zapotrzebowania</h2>
<?php if ($needs): ?>
  <ul class="list"><?php foreach ($needs as $n): ?>
    <li><a href="/zapotrzebowanie/<?= (int)$n['id'] ?>"><?= h($n['title']) ?></a>
      <span class="status s-<?= h($n['status']) ?>"><?= ['pending' => 'w moderacji', 'approved' => 'opublikowane', 'rejected' => 'odrzucone'][$n['status']] ?? h($n['status']) ?></span></li>
  <?php endforeach; ?></ul>
<?php else: ?><p class="meta">Szukasz rozwiązania dla swojej instytucji? <a href="/zapotrzebowania/nowe">Dodaj zapotrzebowanie</a></p><?php endif; ?>
