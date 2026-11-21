<div class="narrow">
  <h1>Opisz, czego szukasz</h1>
  <p class="lead">Opisz, jakiego rozwiązania szuka Twoja instytucja lub firma, i co możesz zaoferować twórcom (wdrożenie, partnerstwo, finansowanie, miejsce na testy). Po weryfikacji pokażemy Ci pasujące innowacje. Twój opis zobaczą tylko moderatorzy i twórcy pasujących rozwiązań – nie jest publiczny.</p>
  <form method="post" class="form" action="/zapotrzebowania/nowe">
    <?php bot_traps(); ?>
    <div class="row">
      <label>Nazwa organizacji <input name="org_name" value="<?= h($form['org_name'] ?? '') ?>" required maxlength="160"></label>
      <label>Rodzaj organizacji
        <select name="org_type" required><option value="">— wybierz —</option>
          <?php foreach (ORG_TYPES as $o): ?><option <?= ($form['org_type'] ?? '') === $o ? 'selected' : '' ?>><?= h($o) ?></option><?php endforeach; ?>
        </select></label>
    </div>
    <label>Czego szukacie? (tytuł) <input name="title" value="<?= h($form['title'] ?? '') ?>" required minlength="10" maxlength="160" placeholder="np. Szukamy taniego systemu zgłaszania usterek dla mieszkańców"></label>
    <label>Opis potrzeby <textarea name="description" rows="6" required minlength="50" maxlength="3000"><?= h($form['description'] ?? '') ?></textarea></label>
    <div class="field"><span class="field-label">Obszary</span><?php checkbox_group('areas', AREAS, csv_list($form['areas'] ?? '')); ?></div>
    <div class="field"><span class="field-label">Co możecie zaoferować?</span><?php checkbox_group('offer', OFFERS, csv_list($form['offer'] ?? '')); ?></div>
    <label>E-mail kontaktowy (niepubliczny) <input name="contact_email" type="email" value="<?= h($form['contact_email'] ?? ($user['email'] ?? '')) ?>" required></label>
    <button class="btn btn-lg">Znajdź rozwiązania</button>
  </form>
</div>
