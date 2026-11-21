<?php $v = fn($k) => h($form[$k] ?? ''); $sel = fn($k) => $form[$k] ?? null; ?>
<div class="narrow">
  <h1>Zgłoś innowację</h1>
  <?php if ($closed): ?>
    <div class="empty">Nabór zgłoszeń został zakończony.</div>
  <?php else: ?>
  <p class="lead">Innowacja nie musi być technologią – może to być nowy sposób organizacji pracy, metoda nauczania czy usługa dla mieszkańców. Wypełnienie zajmie ok. 10 minut. Na podstawie odpowiedzi od razu przygotujemy wstępną ocenę potencjału i rekomendację dalszej ścieżki.</p>
  <form method="post" class="form form-sections" action="/zglos" id="submit-form">
    <?php bot_traps(); ?>

    <fieldset>
      <legend><span>1</span> Podstawowe informacje</legend>
      <label>Nazwa innowacji <input name="title" value="<?= $v('title') ?>" required minlength="5" maxlength="120" class="sim-src"></label>
      <label>Opis w jednym zdaniu <textarea name="summary" rows="2" required minlength="20" maxlength="280" class="sim-src"><?= $v('summary') ?></textarea></label>
      <div class="row">
        <label>Instytucja <input name="institution" value="<?= $v('institution') ?>" required maxlength="160" placeholder="np. LO nr 3 w Rzeszowie"></label>
        <label>Miejscowość / powiat <input name="location" value="<?= $v('location') ?>" required maxlength="80"></label>
      </div>
      <div class="row">
        <label>Rodzaj instytucji
          <select name="category" required><option value="">— wybierz —</option>
            <?php foreach (CATEGORIES as $c): ?><option <?= ($form['category'] ?? '') === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
          </select></label>
        <label>Rodzaj innowacji
          <select name="innovation_type" required><option value="">— wybierz —</option>
            <?php foreach (INNO_TYPES as $k => $l): ?><option value="<?= $k ?>" <?= $sel('innovation_type') === $k ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
          </select></label>
      </div>
      <div class="field"><span class="field-label">Obszary (możesz wybrać kilka)</span><?php checkbox_group('areas', AREAS, csv_list($form['areas'] ?? '')); ?></div>
    </fieldset>

    <fieldset>
      <legend><span>2</span> Problem i rozwiązanie</legend>
      <label>Jaki problem rozwiązuje? Kogo dotyczy? <textarea name="problem" rows="4" required minlength="30" maxlength="2000" class="sim-src"><?= $v('problem') ?></textarea></label>
      <div id="similar-box" class="similar-box" hidden aria-live="polite">
        <b>Podobne innowacje są już na radarze</b> – sprawdź je, może warto połączyć siły zamiast zaczynać od zera:
        <ul></ul>
      </div>
      <label>Na czym polega rozwiązanie? <textarea name="description" rows="6" required minlength="80" maxlength="5000" class="sim-src"><?= $v('description') ?></textarea></label>
      <label>Jakie efekty przyniosło? Podaj liczby, jeśli je macie <textarea name="results" rows="3" required minlength="10" maxlength="2000" placeholder="np. czas obsługi spadł z 19 do 6 dni, korzysta 300 osób, koszt 350 zł"><?= $v('results') ?></textarea></label>
      <label>Kto jeszcze mógłby z tego skorzystać? <textarea name="impact" rows="3" required minlength="20" maxlength="2000"><?= $v('impact') ?></textarea></label>
    </fieldset>

    <fieldset>
      <legend><span>3</span> Ocena wstępna</legend>
      <p class="meta">Te odpowiedzi służą do wstępnej oceny potencjału i wyboru ścieżki. Odpowiadaj szczerze – eksperci i tak to zweryfikują.</p>
      <div class="field"><span class="field-label">Na jakim etapie jest innowacja?</span><?php radio_group('stage', STAGES, $sel('stage')); ?></div>
      <div class="field"><span class="field-label">Czy efekty są zmierzone?</span><?php radio_group('evidence', EVIDENCE, $sel('evidence')); ?></div>
      <div class="field"><span class="field-label">Problem, który rozwiązuje, dotyczy…</span><?php radio_group('scope', SCOPES, $sel('scope')); ?></div>
      <div class="field"><span class="field-label">Ile kosztowałoby wdrożenie w innej instytucji?</span><?php radio_group('cost', COSTS, $sel('cost')); ?></div>
      <div class="field"><span class="field-label">Czy ktoś mógłby za to płacić?</span><?php radio_group('commercial', COMMERCIAL, $sel('commercial')); ?></div>
      <div class="field"><span class="field-label">Czego szukacie? (opcjonalnie)</span><?php checkbox_group('needs', NEEDS, csv_list($form['needs'] ?? '')); ?></div>
    </fieldset>

    <fieldset>
      <legend><span>4</span> Twórcy i kontakt</legend>
      <label>Zespół / twórcy (opcjonalnie, widoczne publicznie) <input name="creators" value="<?= $v('creators') ?>" maxlength="300" placeholder="np. nauczyciel fizyki i 8 uczniów klasy 2b"></label>
      <div class="row">
        <label>Link (opcjonalnie) <input name="link" type="url" value="<?= $v('link') ?>" maxlength="500" placeholder="https://"></label>
        <label>E-mail kontaktowy (niepubliczny) <input name="contact_email" type="email" value="<?= h($form['contact_email'] ?? ($user['email'] ?? '')) ?>" required></label>
      </div>
      <label class="check"><input type="checkbox" name="consent" value="1" required> <span>Akceptuję <a href="/zasady" target="_blank">zasady</a> i potwierdzam, że mam prawo zgłosić tę innowację.</span></label>
    </fieldset>
    <button class="btn btn-lg">Wyślij zgłoszenie</button>
  </form>
  <?php endif; ?>
</div>
