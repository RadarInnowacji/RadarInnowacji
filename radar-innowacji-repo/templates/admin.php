<div class="admin-head">
  <h1>Panel moderatora</h1>
  <div class="admin-actions">
    <a class="btn btn-ghost" href="/admin/eksport.csv">Eksport CSV (oceny, ścieżki, głosy)</a>
    <?php if ($demo_count): ?>
      <form method="post" action="/admin/demo/usun" class="inline"><?= csrf_field() ?><button class="btn btn-ghost">Usuń dane przykładowe (<?= $demo_count ?>)</button></form>
    <?php else: ?>
      <form method="post" action="/admin/demo/wczytaj" class="inline"><?= csrf_field() ?><button class="btn btn-ghost">Wczytaj dane przykładowe</button></form>
    <?php endif; ?>
  </div>
</div>

<div class="stats">
  <div><b><?= (int)$stats['users'] ?></b><span>kont</span></div>
  <div><b><?= (int)$stats['p_approved'] ?></b><span>innowacji</span></div>
  <div class="warn"><b><?= (int)$stats['p_pending'] ?></b><span>innowacji do moderacji</span></div>
  <div><b><?= (int)$stats['v_counted'] ?></b><span>głosów zaliczonych</span></div>
  <div class="warn"><b><?= (int)$stats['v_pending'] ?></b><span>głosów do weryfikacji</span></div>
  <div><b><?= (int)$stats['v_rejected'] ?></b><span>głosów odrzuconych</span></div>
</div>

<h2>Innowacje do oceny eksperckiej</h2>
<?php if ($to_assess): ?>
<table class="tbl">
  <thead><tr><th>Innowacja</th><th>Etap</th><th>Ocen ekspertów</th><th>Akcje</th></tr></thead>
  <tbody>
  <?php foreach ($to_assess as $a): ?>
    <tr><td><a href="/projekt/<?= (int)$a['id'] ?>"><?= h($a['title']) ?></a><?= $a['is_demo'] ? ' <span class="tag tag-demo">Przykład</span>' : '' ?><br><span class="meta"><?= h($a['institution']) ?></span></td>
      <td><?= h(PIPELINE[$a['pipeline']][0] ?? '') ?></td><td><?= (int)$a['n_ass'] ?></td>
      <td class="nowrap"><a class="btn btn-sm" href="/admin/ocena/<?= (int)$a['id'] ?>"><?= $a['n_ass'] ? 'Zmień ocenę' : 'Oceń' ?></a>
        <?= delete_button('/admin/projekt/' . (int)$a['id'] . '/usun', 'innowację „' . $a['title'] . '” wraz z głosami i ocenami') ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?><p class="meta">Brak opublikowanych innowacji.</p><?php endif; ?>

<h2>Zapotrzebowania do moderacji</h2>
<?php foreach ($pending_needs as $n): ?>
<details class="mod">
  <summary><b><?= h($n['title']) ?></b> — <?= h($n['org_name']) ?> <span class="tag"><?= h($n['org_type']) ?></span> <span class="meta"><?= dt($n['created_at']) ?></span></summary>
  <div class="prose"><?= h($n['description']) ?></div>
  <p class="meta">Kontakt: <?= h($n['contact_email']) ?></p>
  <div class="mod-actions">
    <form method="post" action="/admin/potrzeba/<?= (int)$n['id'] ?>/zatwierdz"><?= csrf_field() ?><button class="btn btn-sm">Opublikuj</button></form>
    <form method="post" action="/admin/potrzeba/<?= (int)$n['id'] ?>/odrzuc"><?= csrf_field() ?><button class="btn btn-sm btn-danger">Odrzuć</button></form>
  </div>
</details>
<?php endforeach; if (!$pending_needs): ?><p class="meta">Brak zapotrzebowań czekających na moderację.</p><?php endif; ?>
<?php if ($published_needs): ?>
<details class="mod"><summary>Opublikowane zapotrzebowania (<?= count($published_needs) ?>)</summary>
<table class="tbl"><tbody>
  <?php foreach ($published_needs as $n): ?>
    <tr><td><a href="/zapotrzebowanie/<?= (int)$n['id'] ?>"><?= h($n['title']) ?></a><?= $n['is_demo'] ? ' <span class="tag tag-demo">Przykład</span>' : '' ?><br><span class="meta"><?= h($n['org_name']) ?></span></td>
      <td class="nowrap"><?= delete_button('/admin/potrzeba/' . (int)$n['id'] . '/usun', 'zapotrzebowanie „' . $n['title'] . '”') ?></td></tr>
  <?php endforeach; ?>
</tbody></table></details>
<?php endif; ?>

<h2>Zgłoszenia innowacji do moderacji</h2>
<?php foreach ($pending_projects as $p): ?>
<details class="mod">
  <summary><b><?= h($p['title']) ?></b> — <?= h($p['institution']) ?>, <?= h($p['location']) ?> <span class="tag"><?= h($p['category']) ?></span>
    <span class="meta"><?= dt($p['created_at']) ?> · <?= h($p['submitter']) ?></span></summary>
  <p><em><?= h($p['summary']) ?></em></p>
  <div class="prose"><?= h($p['description']) ?></div>
  <p><b>Potencjał:</b></p><div class="prose"><?= h($p['impact']) ?></div>
  <p class="meta">Kontakt: <?= h($p['contact_email']) ?><?php if ($p['link']): ?> · <a href="<?= h($p['link']) ?>" rel="noopener nofollow" target="_blank"><?= h($p['link']) ?></a><?php endif; ?></p>
  <div class="mod-actions">
    <form method="post" action="/admin/projekt/<?= (int)$p['id'] ?>/zatwierdz"><?= csrf_field() ?><button class="btn btn-sm">Zatwierdź</button></form>
    <?= delete_button('/admin/projekt/' . (int)$p['id'] . '/usun', 'zgłoszenie „' . $p['title'] . '”') ?>
    <form method="post" action="/admin/projekt/<?= (int)$p['id'] ?>/odrzuc" class="reject"><?= csrf_field() ?>
      <input name="note" placeholder="Powód odrzucenia (widoczny dla zgłaszającego)">
      <button class="btn btn-sm btn-danger">Odrzuć</button></form>
  </div>
</details>
<?php endforeach; if (!$pending_projects): ?><p class="meta">Brak zgłoszeń czekających na moderację.</p><?php endif; ?>

<h2 id="glosy">Głosy do weryfikacji</h2>
<p class="meta">Głosy z wynikiem ryzyka ≥ <?= (int)$threshold ?> nie liczą się do wyników, dopóki ich nie zatwierdzisz. Głosujący tego nie widzi.
  Grupa = ta sama podsieć + ten sam projekt. Duża grupa z <b>wieloma różnymi urządzeniami</b> rozłożona w czasie to zwykle szkoła lub urząd (zatwierdź);
  wiele kont z <b>jednego urządzenia</b> w kilka sekund to boty (odrzuć).</p>
<?php if ($groups): ?>
<table class="tbl">
  <thead><tr><th>Projekt</th><th>Głosów</th><th>Różnych urządzeń</th><th>Śr. ryzyko</th><th>Okres</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($groups as $g): ?>
    <tr>
      <td><?= h($g['title']) ?></td><td><?= (int)$g['n'] ?></td><td><?= (int)$g['fps'] ?></td><td><?= (int)$g['avg_score'] ?></td>
      <td class="meta"><?= dt($g['first']) ?> – <?= dt($g['last']) ?></td>
      <td class="nowrap">
        <?php foreach ([['zatwierdz', 'Zatwierdź grupę', ''], ['odrzuc', 'Odrzuć grupę', 'btn-danger']] as [$act, $label, $cls]): ?>
        <form method="post" action="/admin/grupa/<?= $act ?>" class="inline"><?= csrf_field() ?>
          <input type="hidden" name="subnet" value="<?= h($g['subnet_hash']) ?>">
          <input type="hidden" name="project_id" value="<?= (int)$g['project_id'] ?>">
          <button class="btn btn-sm <?= $cls ?>"><?= $label ?></button></form>
        <?php endforeach; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<details class="mod"><summary>Pojedyncze głosy (<?= count($pending_votes) ?>)</summary>
<table class="tbl">
  <thead><tr><th>Czas</th><th>Konto</th><th>Projekt</th><th>Ryzyko</th><th>Powody</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($pending_votes as $v): ?>
    <tr>
      <td class="meta nowrap"><?= dt($v['created_at']) ?></td><td><?= h($v['email']) ?></td><td><?= h($v['title']) ?></td>
      <td><b><?= (int)$v['risk_score'] ?></b></td>
      <td class="meta"><?php foreach (json_decode($v['risk_reasons'] ?: '[]', true) as $r): ?><?= h($r) ?><br><?php endforeach; ?></td>
      <td class="nowrap">
        <form method="post" action="/admin/glos/<?= (int)$v['id'] ?>/zatwierdz" class="inline"><?= csrf_field() ?><button class="btn btn-sm">✓</button></form>
        <form method="post" action="/admin/glos/<?= (int)$v['id'] ?>/odrzuc" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-danger">✗</button></form>
        <form method="post" action="/admin/uzytkownik/<?= (int)$v['user_id'] ?>/blokuj" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-danger" title="Zablokuj konto i odrzuć wszystkie jego głosy">Blokuj</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</details>
<?php else: ?><p class="meta">Brak podejrzanych głosów.</p><?php endif; ?>

<h2>Nawiązane kontakty</h2>
<?php if ($contacts): ?>
<table class="tbl">
  <thead><tr><th>Czas</th><th>Innowacja</th><th>Kto</th><th>Zainteresowanie</th></tr></thead>
  <tbody><?php foreach ($contacts as $c): ?>
    <tr><td class="meta nowrap"><?= dt($c['created_at']) ?></td><td><a href="/projekt/<?= (int)$c['project_id'] ?>"><?= h($c['title']) ?></a></td>
      <td><?= h($c['email']) ?><?= $c['organization'] ? '<br><span class="meta">' . h($c['organization']) . '</span>' : '' ?></td><td><?= h($c['role']) ?></td></tr>
  <?php endforeach; ?></tbody>
</table>
<?php else: ?><p class="meta">Jeszcze nikt nie skorzystał z formularza kontaktu.</p><?php endif; ?>

<h2>Odpowiedzi twórców na zapotrzebowania</h2>
<?php if ($need_responses): ?>
<table class="tbl">
  <thead><tr><th>Czas</th><th>Zapotrzebowanie</th><th>Innowacja</th><th>Twórca</th></tr></thead>
  <tbody><?php foreach ($need_responses as $r): ?>
    <tr><td class="meta nowrap"><?= dt($r['created_at']) ?></td><td><a href="/zapotrzebowanie/<?= (int)$r['need_id'] ?>"><?= h($r['ntitle']) ?></a></td><td><?= h($r['ptitle'] ?? '—') ?></td><td><?= h($r['email']) ?></td></tr>
  <?php endforeach; ?></tbody>
</table>
<?php else: ?><p class="meta">Brak odpowiedzi.</p><?php endif; ?>

<h2>Dziennik działań</h2>
<table class="tbl">
  <tbody><?php foreach ($log as $a): ?>
    <tr><td class="meta nowrap"><?= dt($a['ts']) ?></td><td><?= h($a['email'] ?? '—') ?></td><td><?= h($a['action']) ?></td><td class="meta"><?= h($a['details'] ?? '') ?></td></tr>
  <?php endforeach; if (!$log): ?><tr><td class="meta">Pusto.</td></tr><?php endif; ?></tbody>
</table>
