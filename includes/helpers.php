<?php
declare(strict_types=1);

// Session helper
function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

// Cart contract: chi luu [food_id => quantity]
function ensureCartInitialized(): void
{
    startSession();

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

// Sanitize input cho output HTML
function sanitizeInput(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// Redirect helper
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// Upload helper: validate extension, mime, size va rename
function uploadImage(array $file, string $targetDir, array $allowedExtensions, int $maxSize): ?string
{
    if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > $maxSize) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return null;
    }

    $mimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!isset($mimeMap[$extension]) || $mimeType !== $mimeMap[$extension]) {
        return null;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $newName = uniqid('img_', true) . '.' . $extension;
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return $newName;
}
