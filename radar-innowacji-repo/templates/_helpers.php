<?php
/* Wspólne fragmenty widoków. */

function bot_traps(): void { ?>
  <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf']) ?>">
  <input type="hidden" name="ft" value="<?= h(form_timer()) ?>">
  <div class="hp" aria-hidden="true"><label>Strona WWW <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  <?php if (turnstile_enabled()): ?><div class="cf-turnstile" data-sitekey="<?= h(cfg('TURNSTILE_SITE_KEY')) ?>" data-language="pl"></div><?php endif;
}

function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . h($_SESSION['csrf']) . '">'; }

function vote_button(array $p, bool $voted, int $left): void {
    global $PATH;
    $st = voting_state();
    if ($voted) echo '<button class="btn btn-voted" disabled>✓ Twój głos</button>';
    elseif ($st === 'before') echo '<button class="btn" disabled>Głosowanie wkrótce</button>';
    elseif ($st === 'after') echo '<button class="btn" disabled>Głosowanie zakończone</button>';
    elseif (!current_user()) echo '<a class="btn" href="/logowanie?next=' . h(urlencode($PATH)) . '">Zaloguj, aby głosować</a>';
    elseif ($left <= 0) echo '<button class="btn" disabled>Brak wolnych głosów</button>';
    else echo '<button class="btn vote-btn" data-project="' . (int)$p['id'] . '">Głosuję</button>';
}

function nav_active(string ...$paths): string {
    global $PATH;
    foreach ($paths as $p) if ($PATH === $p || ($p !== '/' && str_starts_with($PATH, $p . '/'))) return 'active';
    return '';
}

function score_ring(int $total, int $size = 64): string {
    $r = 26; $c = 2 * M_PI * $r; $len = round($c * $total / 100, 1);
    $cls = $total >= 75 ? 's-hi' : ($total >= 55 ? 's-good' : ($total >= 35 ? 's-mid' : 's-low'));
    return '<svg class="ring-score ' . $cls . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 64 64" role="img" aria-label="Potencjał ' . $total . ' na 100">'
         . '<circle cx="32" cy="32" r="' . $r . '" class="rs-bg"/>'
         . '<circle cx="32" cy="32" r="' . $r . '" class="rs-fg" stroke-dasharray="' . $len . ' ' . round($c, 1) . '" transform="rotate(-90 32 32)"/>'
         . '<text x="32" y="37" text-anchor="middle">' . $total . '</text></svg>';
}

function fmt_score($v): string { return rtrim(rtrim(number_format((float)$v, 1, ',', ''), '0'), ','); }

function pipeline_steps(string $current): void {
    $keys = array_keys(PIPELINE); $idx = array_search($current, $keys, true);
    echo '<ol class="pipeline">';
    foreach (PIPELINE as $k => [$label, $desc]) {
        $i = array_search($k, $keys, true);
        $cls = $i < $idx ? 'done' : ($i === $idx ? 'current' : '');
        echo '<li class="' . $cls . '" title="' . h($desc) . '"><span>' . h($label) . '</span></li>';
    }
    echo '</ol>';
}

function checkbox_group(string $name, array $options, array $selected): void {
    echo '<div class="checks">';
    foreach ($options as $val => $label) {
        if (is_int($val)) $val = $label;
        echo '<label class="chip"><input type="checkbox" name="' . h($name) . '[]" value="' . h($val) . '"' . (in_array($val, $selected, true) ? ' checked' : '') . '><span>' . h($label) . '</span></label>';
    }
    echo '</div>';
}

function radio_group(string $name, array $options, ?string $selected): void {
    echo '<div class="radios">';
    foreach ($options as $val => $label)
        echo '<label class="radio"><input type="radio" name="' . h($name) . '" value="' . h($val) . '"' . ($selected === $val ? ' checked' : '') . ' required><span>' . h($label) . '</span></label>';
    echo '</div>';
}

function delete_button(string $action, string $what, string $label = 'Usuń'): string {
    return '<form method="post" action="' . h($action) . '" class="inline" data-confirm="' . h("Na pewno usunąć $what? Tej operacji nie można cofnąć.") . '">'
         . csrf_field() . '<button class="btn btn-sm btn-danger">' . h($label) . '</button></form>';
}
