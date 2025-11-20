<?php
// Simple security helpers: CSRF tokens and HTML escaping
// Meant to be included from scripts that already manage sessions.

// Ensure session is active before working with tokens
if (session_status() === PHP_SESSION_ACTIVE) {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(md5(uniqid('', true)));
        }
    }
    if (empty($_SESSION['csrf_token_setup'])) {
        try {
            $_SESSION['csrf_token_setup'] = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $_SESSION['csrf_token_setup'] = bin2hex(md5(uniqid('', true)));
        }
    }
}

/**
 * Get CSRF token for normal forms (login, edits)
 */
function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Get CSRF token intended for setup form (backwards compatible)
 */
function csrf_token_setup(): string {
    return $_SESSION['csrf_token_setup'] ?? '';
}

/**
 * Validate incoming CSRF token.
 * Accepts either main token or setup token for compatibility.
 */
function validate_csrf(): bool {
    if (!isset($_POST['_csrf'])) return false;
    $token = (string)$_POST['_csrf'];
    if (!empty($_SESSION['csrf_token']) && hash_equals((string)$_SESSION['csrf_token'], $token)) {
        return true;
    }
    if (!empty($_SESSION['csrf_token_setup']) && hash_equals((string)$_SESSION['csrf_token_setup'], $token)) {
        return true;
    }
    return false;
}

/**
 * Shortcut for consistent HTML escaping across templates.
 */
function h($text): string {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}