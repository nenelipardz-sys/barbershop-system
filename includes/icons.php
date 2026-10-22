<?php
/**
 * Inline SVG line icons (Lucide-style, 24×24, stroke = currentColor).
 */
function icon(string $name, string $class = '', int $size = 20, float $stroke = 1.8): string
{
    static $paths = null;
    if ($paths === null) {
        $cal = '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>';
        $hex = '<path d="M12 2.5 20.2 7v10L12 21.5 3.8 17V7z"/>';
        $paths = [
            'home'           => '<path d="m3 9.5 9-7 9 7V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
            'home-fill'      => '<path d="m3 9.5 9-7 9 7V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" fill="currentColor"/><path d="M9 22V13h6v9" stroke="#0c1a2e"/>',
            'calendar'       => $cal,
            'calendar-days'  => $cal . '<path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>',
            'calendar-check' => $cal . '<path d="m9 16 2 2 4-4"/>',
            'calendar-plus'  => $cal . '<path d="M12 13v6M9 16h6"/>',
            'calendar-x'     => $cal . '<path d="m10 14 4 4M14 14l-4 4"/>',
            'user'           => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'user-round'     => '<circle cx="12" cy="8" r="4.5"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'users'          => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'users-round'    => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 4.2a4 4 0 0 1 0 7.6M22 21a7 7 0 0 0-4.5-6.5"/>',
            'user-plus'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
            'scissors'       => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M8.12 8.12 12 12M20 4 8.12 15.88M14.8 14.8 20 20"/>',
            'badge'          => $hex . '<circle cx="12" cy="12" r="3.2"/>',
            'badge-chart'    => $hex . '<path d="M9 15v-2M12 15V9.5M15 15v-4"/>',
            'settings'       => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
            'bar-chart'      => '<path d="M12 20V10M18 20V4M6 20v-4"/>',
            'chart-bars'     => '<path d="M3 3v18h18"/><path d="M8 17v-4M12 17V8M16 17v-7M20 17V5"/>',
            'log-out'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
            'log-in'         => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5M15 12H3"/>',
            'bell'           => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
            'clock'          => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            'history'        => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/>',
            'heart'          => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
            'heart-fill'     => '<path fill="currentColor" d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
            'search'         => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
            'plus'           => '<path d="M5 12h14M12 5v14"/>',
            'pencil'         => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>',
            'trash'          => '<path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
            'x'              => '<path d="M18 6 6 18M6 6l12 12"/>',
            'check'          => '<path d="M20 6 9 17l-5-5"/>',
            'check-circle'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>',
            'x-circle'       => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
            'chevron-right'  => '<path d="m9 18 6-6-6-6"/>',
            'chevron-left'   => '<path d="m15 18-6-6 6-6"/>',
            'chevron-down'   => '<path d="m6 9 6 6 6-6"/>',
            'arrow-right'    => '<path d="M5 12h14M12 5l7 7-7 7"/>',
            'arrow-left'     => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
            'eye'            => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off'        => '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61M2 2l20 20"/>',
            'lock'           => '<rect x="4" y="10.5" width="16" height="11" rx="2"/><path d="M7.5 10.5V7a4.5 4.5 0 0 1 9 0v3.5"/><path d="M12 15v2.5"/>',
            'mail'           => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
            'phone'          => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
            'star'           => '<path fill="currentColor" d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/>',
            'crown'          => '<path fill="currentColor" d="m2 6 4.5 4L12 3l5.5 7L22 6l-2 12H4z"/><path d="M4 21h16"/>',
            'menu'           => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'clipboard'      => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M12 11h4M12 16h4M8 11h.01M8 16h.01"/>',
            'clipboard-pen'  => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M10.4 22H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v5"/><path d="M20.4 14.6a1.9 1.9 0 0 1 2.7 2.7L18 22.4l-3.5.9.9-3.5z"/>',
            'note'           => '<path d="M15.5 3H5a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V8.5L15.5 3Z"/><path d="M15 3v6h6M8 13h8M8 17h5"/>',
            'shield-check'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>',
            'banknote'       => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
            'wallet'         => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
            'refresh'        => '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>',
            'alert'          => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
            'info'           => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
            'upload'         => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5M12 3v12"/>',
            'download'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/>',
            'printer'        => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
            'archive'        => '<rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8M10 12h4"/>',
            'rotate-ccw'     => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
            'map-pin'        => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'tag'            => '<path d="M12 2H2v10l9.29 9.29a2.41 2.41 0 0 0 3.42 0l6.58-6.58a2.41 2.41 0 0 0 0-3.42L12 2Z"/><path d="M7 7h.01"/>',
            'play'           => '<path d="m6 3 14 9-14 9z"/>',
            'hourglass'      => '<path d="M5 22h14M5 2h14M17 22v-4.17a2 2 0 0 0-.59-1.42L12 12l-4.41 4.41A2 2 0 0 0 7 17.83V22M7 2v4.17a2 2 0 0 0 .59 1.42L12 12l4.41-4.41A2 2 0 0 0 17 6.17V2"/>',
            'trending-up'    => '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
            'filter'         => '<path d="M22 3H2l8 9.46V19l4 2v-8.54z"/>',
            'more'           => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
            'image'          => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
            'peso'           => '<path d="M7 21V3h6a5 5 0 0 1 0 10H7M4 7h14M4 10h14"/>',
            'pause'          => '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>',
        ];
    }
    $body = $paths[$name] ?? $paths['info'];
    $cls = trim('icon ' . $class);
    return '<svg class="' . $cls . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $stroke . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

/** Gold crown-and-scissors emblem used in the sidebar and mobile header. */
function brand_emblem(int $size = 56): string
{
    static $n = 0;
    $id = "gEm" . (++$n);
    return str_replace("gEm", $id, '<svg class="brand-emblem" width="' . $size . '" height="' . $size . '" viewBox="0 0 64 64" aria-hidden="true">
      <defs><linearGradient id="gEm" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f6e2a6"/><stop offset=".55" stop-color="#d6ae5e"/><stop offset="1" stop-color="#a97c32"/></linearGradient></defs>
      <circle cx="32" cy="33" r="26" fill="none" stroke="url(#gEm)" stroke-width="1.6"/>
      <path d="M22 13.5 25.5 17 28.5 11 32 17 35.5 11 38.5 17 42 13.5 40.5 21H23.5z" fill="url(#gEm)"/>
      <rect x="29.6" y="22" width="4.8" height="17" rx="1.2" fill="#f4ecdc"/>
      <path d="M29.6 25.5 34.4 23.5M29.6 30 34.4 28M29.6 34.5 34.4 32.5" stroke="#c0392b" stroke-width="1.3"/>
      <path d="M21 25 37 45M43 25 27 45" stroke="url(#gEm)" stroke-width="2.4" stroke-linecap="round"/>
      <circle cx="25.5" cy="48.5" r="4" fill="none" stroke="url(#gEm)" stroke-width="2.2"/>
      <circle cx="38.5" cy="48.5" r="4" fill="none" stroke="url(#gEm)" stroke-width="2.2"/>
    </svg>');
}
