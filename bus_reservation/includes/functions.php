<?php
// includes/functions.php - Small reusable helpers used across the whole site.

// Fallback so the site still works if the mbstring extension is switched off.
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s): int { return preg_match_all('/./us', $s) ?: 0; }
}

/** Escape text before printing it in HTML (stops XSS). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a URL inside the project: url('login.php') */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') || str_starts_with($path, '/') ? $path : url($path)));
    exit;
}

function money($amount): string
{
    return APP_CURRENCY . number_format((float)$amount, 2);
}

function formatDateTime(?string $dt, string $format = 'D, d M Y · H:i'): string
{
    return $dt ? date($format, strtotime($dt)) : '-';
}

function badge(string $status): string
{
    return '<span class="badge badge-' . e($status) . '">' . e($status) . '</span>';
}

/* ---------- CSRF protection ---------- */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/** Stops the page if a POST form does not carry the right token. */
function verifyCsrf(): void
{
    $sent  = $_POST['csrf_token'] ?? '';
    $saved = $_SESSION['csrf_token'] ?? '';
    // Both tokens must exist (non-empty) and match exactly.
    if (!is_string($sent) || $sent === '' || $saved === '' || !hash_equals($saved, $sent)) {
        http_response_code(419);
        die('Security check failed. Please go back, refresh the page and try again.');
    }
}

function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Read a trimmed text value from POST. */
function post(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

/* ---------- Flash messages (one-time notices) ---------- */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function showFlash(): void
{
    foreach ($_SESSION['flash'] ?? [] as $f) {
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/* ---------- Authentication and authorization ---------- */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/** Only allow redirects that stay inside this project. */
function safeRedirectTarget(?string $target, string $default): string
{
    if ($target && str_starts_with($target, BASE_URL . '/') && !str_contains($target, '//', 1) && !str_contains($target, "\n")) {
        return $target;
    }
    return $default;
}

function dashboardFor(string $role): string
{
    return $role === 'admin' ? url('admin/dashboard.php') : url('passenger/dashboard.php');
}

/**
 * Protect a page. requireLogin() = any user, requireLogin('admin') = admins only.
 */
function requireLogin(?string $role = null): void
{
    if (!isLoggedIn()) {
        setFlash('info', 'Please log in to continue.');
        redirect(url('login.php') . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    // Make sure the account still exists and is active.
    $st = getConnection()->prepare("SELECT role, status FROM users WHERE id = :id");
    $st->execute([':id' => currentUserId()]);
    $u = $st->fetch();
    if (!$u || $u['status'] !== 'active') {
        session_unset();
        session_destroy();
        session_start();
        setFlash('error', 'Your account is not available. Please contact support.');
        redirect(url('login.php'));
    }
    $_SESSION['role'] = $u['role'];
    if ($role !== null && $u['role'] !== $role) {
        setFlash('error', 'You are not allowed to open that page.');
        redirect(dashboardFor($u['role']));
    }
}

function getSetting(string $key, string $default = ''): string
{
    $st = getConnection()->prepare("SELECT setting_value FROM settings WHERE setting_key = :k");
    $st->execute([':k' => $key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : (string)$v;
}

/** Locations for dropdowns. */
function getLocations(bool $activeOnly = true): array
{
    $sql = "SELECT id, name FROM locations" . ($activeOnly ? " WHERE is_active = 1" : "") . " ORDER BY name";
    return getConnection()->query($sql)->fetchAll();
}

/** Build seat numbers 1A,1B,1C,1D,2A... for a bus. */
function generateSeats(PDO $pdo, int $busId, int $capacity): void
{
    $letters = ['A', 'B', 'C', 'D'];
    $ins = $pdo->prepare("INSERT INTO seats (bus_id, seat_number) VALUES (:b, :s)");
    for ($i = 0; $i < $capacity; $i++) {
        $row = intdiv($i, 4) + 1;
        $ins->execute([':b' => $busId, ':s' => $row . $letters[$i % 4]]);
    }
}

/** Validate a name/phone/email style input. Returns an error message or ''. */
function validatePhone(string $phone): string
{
    return preg_match('/^[0-9+\s-]{7,20}$/', $phone) ? '' : 'Enter a valid phone number (7 to 20 digits).';
}

function validatePassword(string $pw): string
{
    if (strlen($pw) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return 'Password must contain letters and numbers.';
    return '';
}

/** Is this nav link the current page? */
function navActive(string $file): string
{
    return basename($_SERVER['SCRIPT_NAME']) === $file ? ' active' : '';
}
