<div class="narrow">
  <h1>Zaloguj się</h1>
  <p class="lead">Bez haseł – wyślemy Ci na e-mail link, którym zalogujesz się jednym kliknięciem.</p>
  <form method="post" class="form" action="/logowanie">
    <?php bot_traps(); ?>
    <input type="hidden" name="next" value="<?= h($next) ?>">
    <label>Adres e-mail
      <input type="email" name="email" value="<?= h($email) ?>" required autocomplete="email" maxlength="254" autofocus>
    </label>
    <button class="btn btn-lg">Wyślij link logowania</button>
  </form>
</div>
