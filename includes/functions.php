<?php
/**
 * Helper functions shared by all pages.
 */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** URL for a static asset with a cache-busting version. */
function asset(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 1;
    return url($path) . '?v=' . $v;
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^(https?:)?/#', $path) ? $path : url($path)));
    exit;
}

/** Redirect back to the referring page within the app (or a fallback). */
function back(string $fallback): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === $host) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

// ----- Flash messages & old input -----
function flash(string $type, $message): void
{
    foreach ((array) $message as $m) {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $m];
    }
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function keep_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['current_password'], $data['csrf']);
    $_SESSION['old'] = $data;
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

// ----- CSRF -----
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_response(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
        }
        flash('error', 'Your session expired. Please try again.');
        back('index.php');
    }
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// ----- Formatting -----
function peso($amount): string
{
    $amount = (float) $amount;
    $decimals = abs($amount - round($amount)) < 0.005 ? 0 : 2;
    return '₱' . number_format($amount, $decimals);
}

/** "01:00 PM" (padded, like the schedule tables) or "1:00 PM". */
function fmt_time(?string $time, bool $pad = false): string
{
    if (!$time) {
        return '';
    }
    return date($pad ? 'h:i A' : 'g:i A', strtotime('2000-01-01 ' . $time));
}

function fmt_date(?string $date, string $format = 'M j, Y'): string
{
    return $date ? date($format, strtotime($date)) : '';
}

function greeting(): string
{
    $h = (int) date('G');
    if ($h < 12) return 'Good Morning';
    if ($h < 18) return 'Good Afternoon';
    return 'Good Evening';
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min' . (floor($diff / 60) > 1 ? 's' : '') . ' ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    return fmt_date($datetime);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

/** Percentage change label, e.g. "+12%". */
function pct_change(float $now, float $before): string
{
    if ($before <= 0) {
        return $now > 0 ? '+100%' : '0%';
    }
    $pct = round(($now - $before) / $before * 100);
    return ($pct >= 0 ? '+' : '') . $pct . '%';
}

function media_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    return is_file(APP_ROOT . '/' . $path) ? url($path) : null;
}

/** Round avatar image, or initials when there is no photo. */
function avatar(?string $name, ?string $path = null, int $size = 40, string $class = ''): string
{
    $src = media_url($path);
    $style = "width:{$size}px;height:{$size}px;";
    if ($src) {
        return '<img class="avatar ' . e($class) . '" src="' . e($src) . '" alt="' . e($name) . '" style="' . $style . '">';
    }
    $fs = max(10, (int) round($size * 0.38));
    return '<span class="avatar avatar-initials ' . e($class) . '" style="' . $style . 'font-size:' . $fs . 'px">' . e(initials($name ?? '?')) . '</span>';
}

function service_img(?string $path, string $name, string $class = 'svc-img'): string
{
    $src = media_url($path);
    if ($src) {
        return '<img class="' . e($class) . '" src="' . e($src) . '" alt="' . e($name) . '">';
    }
    return '<span class="' . e($class) . ' svc-img-empty">' . icon('scissors', '', 22) . '</span>';
}

function status_badge(string $status, bool $staffView = false): string
{
    $label = Appointment::statusLabel($status, $staffView);
    $cls = $staffView && in_array($status, ['pending', 'confirmed'], true) ? 'upcoming' : $status;
    return '<span class="badge badge-' . e($cls) . '">' . e($label) . '</span>';
}

function discount_label(string $type): string
{
    return ['none' => 'Regular', 'senior' => 'Senior Citizen', 'pwd' => 'PWD'][$type] ?? 'Regular';
}

function day_names(): array
{
    return [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
}

/** Valid Y-m-d date or the fallback. */
function valid_date(?string $date, ?string $fallback = null): ?string
{
    $d = $date ? DateTime::createFromFormat('Y-m-d', $date) : false;
    return $d && $d->format('Y-m-d') === $date ? $date : $fallback;
}

/**
 * Save an uploaded image into uploads/<dir>/ and return its relative path.
 * Returns null when no file was sent.
 */
function upload_image(string $field, string $dir): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new ValidationException(['The image could not be uploaded. Please try again.']);
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new ValidationException(['Image must be 3 MB or smaller.']);
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new ValidationException(['Please upload a JPG, PNG, WEBP or GIF image.']);
    }
    $folder = APP_ROOT . '/uploads/' . $dir;
    if (!is_dir($folder)) {
        mkdir($folder, 0775, true);
    }
    $name = bin2hex(random_bytes(8)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], "$folder/$name")) {
        throw new ValidationException(['The image could not be saved.']);
    }
    return "uploads/$dir/$name";
}

/** Delete an uploaded file (never touches bundled assets). */
function delete_upload(?string $path): void
{
    if ($path && str_starts_with($path, 'uploads/') && is_file(APP_ROOT . '/' . $path)) {
        @unlink(APP_ROOT . '/' . $path);
    }
}

/** Sidebar navigation per role. */
function nav_items(string $role): array
{
    switch ($role) {
        case 'owner':
            return [
                ['dashboard', 'Dashboard', 'owner/dashboard.php', 'home'],
                ['appointments', 'Appointments', 'owner/appointments.php', 'calendar-days'],
                ['clients', 'Clients', 'owner/clients.php', 'user'],
                ['staff', 'Staff', 'owner/staff.php', 'user-round'],
                ['services', 'Services', 'owner/services.php', 'badge'],
                ['reports', 'Reports', 'owner/reports.php', 'badge-chart'],
                ['settings', 'Settings', 'owner/settings.php', 'settings'],
            ];
        case 'staff':
            return [
                ['dashboard', 'Dashboard', 'staff/dashboard.php', 'home'],
                ['schedule', 'My Schedule', 'staff/schedule.php', 'calendar-days'],
                ['appointments', 'Appointments', 'staff/appointments.php', 'calendar-check'],
                ['clients', 'Clients', 'staff/clients.php', 'users-round'],
                ['services', 'Services', 'staff/services.php', 'badge'],
                ['reports', 'Reports', 'staff/reports.php', 'badge-chart'],
            ];
        default:
            return [
                ['home', 'Home', 'client/home.php', 'home'],
                ['book', 'Book Appointment', 'client/book.php', 'calendar-plus'],
                ['appointments', 'My Appointments', 'client/appointments.php', 'calendar-check'],
                ['history', 'Visit History', 'client/history.php', 'clock'],
                ['favorites', 'Favorites', 'client/favorites.php', 'heart'],
                ['profile', 'Profile', 'client/profile.php', 'user'],
            ];
    }
}

/** Mobile bottom navigation per role (4 items). */
function bottom_nav_items(string $role): array
{
    switch ($role) {
        case 'owner':
            return [
                ['dashboard', 'Home', 'owner/dashboard.php', 'home'],
                ['appointments', 'Appointments', 'owner/appointments.php', 'calendar-days'],
                ['clients', 'Clients', 'owner/clients.php', 'users-round'],
                ['reports', 'Reports', 'owner/reports.php', 'bar-chart'],
            ];
        case 'staff':
            return [
                ['dashboard', 'Home', 'staff/dashboard.php', 'home'],
                ['schedule', 'Schedule', 'staff/schedule.php', 'calendar-days'],
                ['appointments', 'Appointments', 'staff/appointments.php', 'calendar-check'],
                ['clients', 'Clients', 'staff/clients.php', 'users-round'],
            ];
        default:
            return [
                ['home', 'Home', 'client/home.php', 'home-fill'],
                ['book', 'Book', 'client/book.php', 'calendar-days'],
                ['appointments', 'Appointments', 'client/appointments.php', 'calendar-check'],
                ['profile', 'Profile', 'client/profile.php', 'user'],
            ];
    }
}

/** JSON for the appointment details modal (data-fill). */
function appt_fill(array $a): string
{
    return json_encode([
        'd_client'  => $a['client_name'] ?? '',
        'd_phone'   => $a['client_phone'] ?? '—',
        'd_service' => $a['service_name'] ?? '',
        'd_barber'  => $a['barber_name'] ?? '',
        'd_date'    => fmt_date($a['appointment_date'], 'l, M j, Y'),
        'd_time'    => fmt_time($a['start_time']) . ' – ' . fmt_time($a['end_time']),
        'd_status'  => Appointment::statusLabel($a['status']),
        'd_rate'    => discount_label($a['discount_type'] ?? 'none'),
        'd_price'   => peso($a['total_price']) . ((float) ($a['discount_amount'] ?? 0) > 0 ? ' (−' . peso($a['discount_amount']) . ')' : ''),
        'd_notes'   => $a['notes'] ?: '—',
        'd_avatar'  => media_url($a['service_image'] ?? null) ?? '',
    ]);
}

/** JSON for the staff appointment modal (details + which actions are allowed). */
function staff_fill(array $a, string $clientNotes = ''): string
{
    $today = date('Y-m-d');
    return json_encode([
        'appointment_id' => $a['id'],
        'd_client'       => $a['client_name'],
        'd_phone'        => $a['client_phone'] ?? '—',
        'd_service'      => $a['service_name'],
        'd_when'         => fmt_date($a['appointment_date'], 'D, M j') . ' · ' . fmt_time($a['start_time']) . ' – ' . fmt_time($a['end_time']),
        'd_status'       => Appointment::statusLabel($a['status'], true),
        'd_rate'         => discount_label($a['discount_type']),
        'd_price'        => peso($a['total_price']),
        'd_notes'        => $a['notes'] ?: '—',
        'd_client_notes' => $clientNotes ?: '—',
        'd_photo'        => media_url($a['client_avatar'] ?? null) ?? url('assets/img/logo-monogram.png'),
        'd_profile'      => url('staff/client_view.php?id=' . (int) $a['client_id']),
        'can_start'      => in_array($a['status'], ['pending', 'confirmed'], true) && $a['appointment_date'] === $today,
        'can_complete'   => in_array($a['status'], ['confirmed', 'in_progress'], true) && $a['appointment_date'] <= $today,
    ]);
}

/** Latest notes per client, keyed by client id (joined with " · "). */
function client_notes_map(array $clientIds): array
{
    $clientIds = array_values(array_unique(array_map('intval', $clientIds)));
    if (!$clientIds) {
        return [];
    }
    $in = implode(',', array_fill(0, count($clientIds), '?'));
    $map = [];
    foreach (Database::all("SELECT client_id, note FROM client_notes WHERE client_id IN ($in) ORDER BY created_at DESC", $clientIds) as $n) {
        if (count($map[$n['client_id']] ?? []) < 4) {
            $map[$n['client_id']][] = $n['note'];
        }
    }
    return array_map(fn($notes) => implode(' · ', $notes), $map);
}

/** Simple pagination: returns [offset, pages, page]. */
function paginate(int $total, int $perPage = 15): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
    return [($page - 1) * $perPage, $pages, $page];
}

function pager_links(int $page, int $pages, int $total, string $noun = 'records'): string
{
    $q = $_GET;
    $link = function (int $p) use ($q) {
        $q['page'] = $p;
        return '?' . http_build_query($q);
    };
    $html = '<div class="pager"><span>' . number_format($total) . ' ' . e($noun) . '</span><div class="pager-links">';
    $html .= $page > 1 ? '<a href="' . e($link($page - 1)) . '" aria-label="Previous">‹</a>' : '<span class="disabled">‹</span>';
    $start = max(1, $page - 2);
    $end = min($pages, $start + 4);
    for ($p = $start; $p <= $end; $p++) {
        $html .= $p === $page ? '<span class="active">' . $p . '</span>' : '<a href="' . e($link($p)) . '">' . $p . '</a>';
    }
    $html .= $page < $pages ? '<a href="' . e($link($page + 1)) . '" aria-label="Next">›</a>' : '<span class="disabled">›</span>';
    return $html . '</div></div>';
}

function portal_label(string $role): string
{
    return ['owner' => 'Owner Dashboard', 'staff' => 'Staff Dashboard', 'client' => 'Client Portal'][$role] ?? 'Portal';
}
