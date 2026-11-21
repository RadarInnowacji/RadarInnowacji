<article class="detail">
  <p><a href="/zapotrzebowania">← Szukam rozwiązania</a></p>
  <?php if ($n['status'] !== 'approved'): ?><div class="flash flash-info">To zapotrzebowanie czeka na weryfikację moderatora.</div><?php endif; ?>
  <p class="tags"><span class="tag"><?= h($n['org_type']) ?></span><?php foreach (csv_list($n['areas']) as $a): ?><span class="tag tag-outline"><?= h($a) ?></span><?php endforeach; ?><?= $n['is_demo'] ? '<span class="tag tag-demo">Przykład</span>' : '' ?></p>
  <h1><?= h($n['title']) ?></h1>
  <p class="meta"><?= h($n['org_name']) ?> · dodano <?= dt($n['created_at']) ?> · <span class="private-note">widoczne tylko dla autora, moderatorów i twórców pasujących innowacji</span></p>
  <?php if ($user['is_admin']): ?><p><?= delete_button('/admin/potrzeba/' . (int)$n['id'] . '/usun', 'zapotrzebowanie „' . $n['title'] . '”', 'Usuń zapotrzebowanie') ?></p><?php endif; ?>
  <div class="prose lead"><?= h($n['description']) ?></div>
  <?php if ($n['offer']): ?>
    <h3>Co oferuje</h3>
    <ul><?php foreach (csv_list($n['offer']) as $o): ?><li><?= h(OFFERS[$o] ?? $o) ?></li><?php endforeach; ?></ul>
  <?php endif; ?>

  <?php if ($is_owner || $user['is_admin']): ?>
  <section class="block">
    <h2>Gotowe rozwiązania dla Ciebie</h2>
    <?php if ($matches): ?>
      <ul class="match-list">
      <?php foreach ($matches as $m): ?>
        <li><a href="/projekt/<?= $m['id'] ?>"><?= h($m['title']) ?></a>
          <span class="meta"><?= h($m['row']['institution']) ?> · <?= h($m['row']['location']) ?></span>
          <span class="match">dopasowanie <?= min(99, (int)round($m['score'] * 100)) ?>% · wspólne słowa: <?= h(implode(', ', $m['shared'])) ?></span></li>
      <?php endforeach; ?>
      </ul>
      <p class="meta">Otwórz kartę innowacji i użyj formularza „Chcesz wdrożyć, rozwinąć lub sfinansować?”, żeby napisać do twórców.</p>
    <?php else: ?><p class="meta">Na razie brak pasujących innowacji. Gdy pojawią się nowe zgłoszenia, zobaczysz je tutaj.</p><?php endif; ?>
  </section>

  <section class="block">
    <h2>Odpowiedzi od twórców</h2>
    <?php if ($responses): ?>
      <ul class="match-list">
      <?php foreach ($responses as $r): ?>
        <li><b><?= $r['ptitle'] ? h($r['ptitle']) : 'Innowacja usunięta' ?></b>
          <span class="meta"><?= dt($r['created_at']) ?> · kontakt: <?= h($r['email']) ?></span>
          <span class="prose"><?= h($r['message']) ?></span></li>
      <?php endforeach; ?>
      </ul>
    <?php else: ?><p class="meta">Jeszcze nikt nie odpowiedział.</p><?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($my_matches): ?>
  <section class="block contact-box">
    <h2>Twoja innowacja pasuje do tej potrzeby</h2>
    <p>Napisz do <?= h($n['org_name']) ?> – dostaną wiadomość z linkiem do karty Twojej innowacji i odpowiedzą na Twój adres e-mail.</p>
    <form method="post" action="/zapotrzebowanie/<?= (int)$n['id'] ?>" class="form form-plain">
      <?= csrf_field() ?>
      <label>Twoja innowacja
        <select name="project_id" required>
          <?php foreach ($my_matches as $mm): ?><option value="<?= (int)$mm['id'] ?>"><?= h($mm['title']) ?> (dopasowanie <?= min(99, (int)round($mm['score'] * 100)) ?>%)</option><?php endforeach; ?>
        </select></label>
      <label>Wiadomość <textarea name="message" rows="4" required minlength="20" maxlength="2000" placeholder="Np. Nasze rozwiązanie działa od roku w 23 sołectwach – chętnie pomożemy we wdrożeniu."></textarea></label>
      <button class="btn">Wyślij</button>
    </form>
  </section>
  <?php endif; ?>
</article>
