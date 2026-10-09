<?php
/**
 * Grand Cafe - Authentication & Session Helper
 * Centralizes user session checks, access control guards, and flash messages.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the currently logged-in user is an admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current user information from session
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'email'    => $_SESSION['email'] ?? '',
        'role'     => $_SESSION['role'] ?? 'customer'
    ];
}

/**
 * Require login to access a page. Redirects to login with a return redirect URL if not logged in.
 */
function requireLogin($redirect = null) {
    if (!isLoggedIn()) {
        $target = $redirect ?: $_SERVER['REQUEST_URI'] ?? 'index.php';
        setFlash('warning', 'Please sign in to proceed.');
        header('Location: login.php?redirect=' . urlencode($target));
        exit;
    }
}

/**
 * Require admin privileges. Redirects or halts with 403 Forbidden.
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in with an administrator account.');
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'admin.php'));
        exit;
    }

    if (!isAdmin()) {
        http_response_code(403);
        die("<!DOCTYPE html><html><head><title>403 Forbidden</title><link rel='stylesheet' href='assets/css/style.css'></head><body style='display:flex;align-items:center;justify-content:center;height:100vh;background:#FAF6F0;font-family:sans-serif;'><div style='background:#fff;padding:40px;border-radius:12px;text-align:center;max-width:450px;box-shadow:0 8px 24px rgba(0,0,0,0.1);'><h2 style='color:#3E2723;'>⛔ Access Denied</h2><p style='color:#6D635F;'>This dashboard is reserved for Grand Cafe administrators only.</p><a href='index.php' class='btn btn-primary' style='display:inline-block;margin-top:15px;padding:10px 20px;text-decoration:none;'>Return to Cafe Home</a></div></body></html>");
    }
}

/**
 * Set a session flash message
 * Types: 'success', 'error', 'warning', 'info'
 */
function setFlash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear all flash messages
 */
function getFlashes() {
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}
