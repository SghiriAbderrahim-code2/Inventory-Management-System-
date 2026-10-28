<?php
/**
 * includes/functions.php
 * ------------------------------------------------------------
 * Shared helpers: output escaping, flash messages, CSRF
 * protection, redirects and stock-status colour coding.
 * Loaded automatically by header.php as well, but pages may
 * require it directly when they render before the header.
 * ------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape a value for safe HTML output (XSS protection).
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect the browser and stop the script (POST → Redirect → GET
 * pattern, which also prevents duplicate submissions on refresh).
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Store a one-time flash message in the session.
 *
 * @param string $type    'success' | 'error'
 * @param string $message Human readable message (escaped on output)
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Read and clear all pending flash messages (rendered once by header.php).
 */
function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Return the CSRF token, generating it on first use.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Hidden input carrying the CSRF token — include in every POST form.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify the token submitted with a POST request (timing-safe).
 */
function csrf_check(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Trimmed POST string (empty string when missing).
 */
function post_str(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

/**
 * Colour status of a product's stock:
 *   'low'  → red   : stock is at or below the alert threshold
 *   'warn' → orange: stock is approaching the threshold (≤ 2× threshold)
 *   'ok'   → green : healthy stock level
 */
function stock_status(array $product): string
{
    $stock     = (int) $product['stock_quantity'];
    $threshold = (int) $product['alert_threshold'];

    if ($stock <= $threshold) {
        return 'low';
    }
    if ($threshold > 0 && $stock <= $threshold * 2) {
        return 'warn';
    }
    return 'ok';
}

/**
 * Human friendly date/time for the UI (e.g. "Oct 24, 2026 9:15 AM").
 */
function pretty_date(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date('M j, Y g:i A', $ts) : $datetime;
}
