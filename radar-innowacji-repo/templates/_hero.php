<section class="hero">
  <div class="hero-bg" aria-hidden="true"><div class="radar-scope"><span class="ring r1"></span><span class="ring r2"></span><span class="ring r3"></span><span class="hero-sweep"></span>
    <i class="dot d1"></i> <i class="dot d2"></i> <i class="dot d3 blue"></i> <i class="dot d4"></i> <i class="dot d5"></i> <i class="dot d6 blue"></i></div></div>
  <div class="wrap hero-in">
    <span class="hero-label">Kampania</span>
    <h1 class="hero-title">Radar Innowacji</h1>
    <p class="hero-lead">Odnajdujemy ukryte innowacje ze szkół, uczelni, samorządów i instytucji publicznych, oceniamy ich potencjał według jawnych kryteriów i łączymy je z tymi, którzy mogą je wdrożyć, rozwinąć lub sfinansować.</p>
    <div class="hero-cta">
      <a class="btn btn-light btn-lg" href="/zglos">Zgłoś innowację</a>
      <a class="btn btn-outline-light btn-lg" href="/zapotrzebowania">Szukam rozwiązania</a>
    </div>
  </div>
</section>
<section class="stats-strip">
  <div class="wrap stats-in">
    <div><b><?= (int)$totals['projects'] ?></b><span>innowacji na radarze</span></div>
    <div><b><?= (int)$totals['needs'] ?></b><span>organizacji szuka rozwiązań</span></div>
    <div><b><?= (int)$totals['contacts'] ?></b><span>nawiązanych kontaktów</span></div>
    <div><b><?= (int)$totals['places'] ?></b><span><?= (int)$totals['places'] === 1 ? 'miejscowość' : 'miejscowości' ?></span></div>
  </div>
</section>
