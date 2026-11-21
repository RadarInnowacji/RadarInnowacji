<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ? h($title) . ' – ' : '' ?><?= h(site_name()) ?></title>
  <meta name="description" content="Zgłaszaj lokalne innowacje ze szkół, uczelni, samorządów i instytucji publicznych i głosuj na najciekawsze.">
  <link rel="preload" href="<?= asset('fonts/lato-bold.woff') ?>" as="font" type="font/woff" crossorigin>
  <link rel="stylesheet" href="<?= asset('style.css') ?>">
  <link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
  <script src="<?= asset('app.js') ?>" defer></script>
  <?php if (turnstile_enabled()): ?><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script><?php endif; ?>
</head>
<body data-csrf="<?= h($csrf) ?>">
<div class="topline"></div>
<header class="top">
  <div class="wrap top-in">
    <a class="brand" href="/" aria-label="<?= h(site_name()) ?> – strona główna">
      <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
        <circle cx="24" cy="24" r="21" fill="none" stroke="currentColor" stroke-width="3"/>
        <circle cx="24" cy="24" r="13" fill="none" stroke="currentColor" stroke-width="2.5" opacity=".55"/>
        <path class="sweep" d="M24 24 L24 3 A21 21 0 0 1 42.2 13.5 Z"/>
        <circle cx="24" cy="24" r="3.2" fill="currentColor"/>
        <circle class="blip" cx="34" cy="14" r="3.2"/>
      </svg>
      <span class="brand-text"><strong>Radar</strong><strong>Innowacji</strong></span>
      <span class="brand-sub">kampania<br>innowacji lokalnych</span>
    </a>
    <input type="checkbox" id="menu-toggle" class="menu-toggle" aria-label="Menu">
    <label for="menu-toggle" class="burger" aria-hidden="true"><span></span></label>
    <nav class="nav">
      <a href="/" class="<?= nav_active('/', '/projekt') ?>">Innowacje</a>
      <a href="/zapotrzebowania" class="<?= nav_active('/zapotrzebowania', '/zapotrzebowanie') ?>">Szukam rozwiązania</a>
      <a href="/wyniki" class="<?= nav_active('/wyniki') ?>">Ranking</a>
      <a href="/jak-oceniamy" class="<?= nav_active('/jak-oceniamy') ?>">Jak oceniamy</a>
      <?php if ($user): ?>
        <?php if ($user['is_admin']): ?><a href="/admin" class="<?= nav_active('/admin') ?>">Panel</a><?php endif; ?>
        <a href="/moje" class="<?= nav_active('/moje') ?>">Moje konto</a>
      <?php else: ?>
        <a href="/logowanie" class="<?= nav_active('/logowanie') ?>">Zaloguj się</a>
      <?php endif; ?>
      <a class="btn" href="/zglos">Zgłoś innowację</a>
    </nav>
  </div>
</header>

<?php if (!empty($hero)) include ROOT . '/templates/_hero.php'; ?>

<main class="wrap main">
  <?php foreach (take_flashes() as [$cat, $msg]): ?>
    <div class="flash flash-<?= h($cat) ?>" role="status"><?= h($msg) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<footer class="foot">
  <div class="wrap foot-grid">
    <div>
      <p class="foot-title"><?= h(site_name()) ?></p>
      <p>Odnajdujemy innowacje powstające w szkołach, uczelniach, samorządach i instytucjach publicznych, oceniamy ich potencjał i łączymy twórców z tymi, którzy mogą je wdrożyć, rozwinąć lub sfinansować.</p>
    </div>
    <div>
      <p class="foot-title">Na skróty</p>
      <ul><li><a href="/">Innowacje</a></li><li><a href="/zglos">Zgłoś innowację</a></li><li><a href="/zapotrzebowania">Szukam rozwiązania</a></li><li><a href="/wyniki">Ranking</a></li></ul>
    </div>
    <div>
      <p class="foot-title">Informacje</p>
      <ul><li><a href="/jak-oceniamy">Jak oceniamy innowacje</a></li><li><a href="/zasady">Zasady</a></li><li><a href="/zasady#prywatnosc">Prywatność</a></li></ul>
    </div>
  </div>
  <div class="foot-bottom"><div class="wrap">© <?= date('Y') ?> <?= h(site_name()) ?></div></div>
</footer>
</body>
</html>
