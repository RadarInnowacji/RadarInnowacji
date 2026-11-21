<div class="narrow center">
  <h1>Sprawdź skrzynkę</h1>
  <p class="lead">Jeśli adres jest poprawny, za chwilę dostaniesz link logowania (ważny 30 minut). Zajrzyj też do folderu spam.</p>
  <?php if ($dev_link): ?><div class="flash flash-info">Tryb deweloperski: <a href="<?= h($dev_link) ?>"><?= h($dev_link) ?></a></div><?php endif; ?>
</div>
