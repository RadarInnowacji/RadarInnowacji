<div class="narrow">
  <p><a href="/projekt/<?= (int)$p['id'] ?>">← Karta innowacji</a></p>
  <h1>Ocena ekspercka</h1>
  <p class="lead"><b><?= h($p['title']) ?></b> – <?= h($p['institution']) ?></p>
  <p class="meta">Oceń każde kryterium w skali 1–5. Podpowiedzią jest ocena automatyczna. Twoja ocena zastąpi ją w obliczeniach (przy kilku ekspertach liczymy średnią). Poparcie społeczności liczy się zawsze z głosów.</p>
  <form method="post" class="form" action="/admin/ocena/<?= (int)$p['id'] ?>">
    <?= csrf_field() ?>
    <?php foreach (EXPERT_CRITERIA as $c): [$label, $w, $desc] = CRITERIA[$c]; $cur = $mine[$c] ?? null; ?>
    <div class="field assess-row">
      <span class="field-label"><?= h($label) ?> <small>(waga <?= $w ?>%)</small></span>
      <p class="meta"><?= h($desc) ?> Auto: <b><?= (int)$ev['auto'][$c] ?></b> – <?= h($ev['why'][$c]) ?></p>
      <div class="scale">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <label><input type="radio" name="c_<?= $c ?>" value="<?= $i ?>" required <?= ($cur ?? $ev['auto'][$c]) == $i ? 'checked' : '' ?>><span><?= $i ?></span></label>
        <?php endfor; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <label>Komentarz (widoczny na karcie innowacji) <textarea name="comment" rows="4" maxlength="2000"><?= h($mine_comment) ?></textarea></label>
    <button class="btn btn-lg">Zapisz ocenę</button>
  </form>
</div>
