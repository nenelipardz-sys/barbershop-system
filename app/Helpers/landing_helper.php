<?php
/**
 * Landing helper — drawing helpers for the client landing page (client/about).
 */

/** Gold line icon for a service, chosen by its name. */
function landing_service_icon(string $name): string
{
    $n = strtolower($name);
    if (str_contains($n, 'beard')) {
        $p = '<path d="M7 4c0 4 1 6 5 6s5-2 5-6"/><path d="M5 9c0 6 3 11 7 11s7-5 7-11"/><path d="M10 14c.6.6 1.3.9 2 .9s1.4-.3 2-.9"/>';
    } elseif (str_contains($n, 'treatment')) {
        $p = '<path d="M9 3h6v3l1.5 2v12a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1V8L9 6z"/><path d="M9 6h6M8.5 12h7"/><path d="M12 15.2c.8 1 1.2 1.6 1.2 2.1a1.2 1.2 0 0 1-2.4 0c0-.5.4-1.1 1.2-2.1z"/>';
    } elseif (str_contains($n, 'towel')) {
        $p = '<path d="M4 7h16v4H4z"/><path d="M6 11v8h12v-8"/><path d="M9 3c0 1.5 1 1.5 1 3M12 3c0 1.5 1 1.5 1 3M15 3c0 1.5 1 1.5 1 3"/>';
    } elseif (str_contains($n, 'shave')) {
        $p = '<path d="M3 15 15 3l3 3L8 16"/><path d="M8 16l-2 5h3l2-3"/><path d="M12 9l2 2"/>';
    } else {
        $p = '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M8.12 8.12 12 12M20 4 8.12 15.88M14.8 14.8 20 20"/>';
    }
    return '<svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/**
 * Torn-paper edge: a jagged band filled with the colour of the section below it.
 * Same seed = same shape, so the page looks identical on every load.
 */
function torn_edge(int $seed, string $class = ''): string
{
    mt_srand($seed);
    $pts = [];
    $x = 0;
    while ($x < 1440) {
        $y = 22 + mt_rand(0, 22) + (mt_rand(0, 9) === 0 ? mt_rand(-14, 10) : 0);
        $pts[] = $x . ',' . $y;
        $x += mt_rand(6, 22);
    }
    $pts[] = '1440,' . (24 + mt_rand(0, 16));
    $fibers = [];
    foreach ($pts as $i => $p) {
        [$px, $py] = explode(',', $p);
        $fibers[] = $px . ',' . ($py + 6 + ($i % 3) * 2);
    }
    mt_srand();
    $edge = implode(' L', $pts);
    $fib = implode(' L', $fibers);
    return '<svg class="torn ' . e($class) . '" viewBox="0 0 1440 70" preserveAspectRatio="none" aria-hidden="true">'
        . '<path class="torn-fiber" d="M0,70 L0,' . explode(',', $fibers[0])[1] . ' L' . $fib . ' L1440,70 Z"/>'
        . '<path d="M0,70 L0,' . explode(',', $pts[0])[1] . ' L' . $edge . ' L1440,70 Z"/></svg>';
}

/** Ornamental divider: line – diamond – line (used above and below section titles). */
function ornament(): string
{
    return '<span class="orn" aria-hidden="true"><i></i><b></b><i></i></span>';
}
