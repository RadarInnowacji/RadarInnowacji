<div class="narrow center">
  <h1>Potwierdź logowanie</h1>
  <form method="post" class="form" id="auth-form" action="/auth/<?= h($token) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= h($next) ?>">
    <input type="hidden" name="fp" class="fp-field">
    <button class="btn btn-lg">Zaloguj mnie</button>
  </form>
</div>
