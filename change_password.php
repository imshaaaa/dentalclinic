<?php

require_once __DIR__ . '/Config/bootstrap.php';
require_login();
verify_csrf();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$userId = current_user_id();
$profile = profile_by_user_id((int) $userId, true);

if (!$profile) {
    http_response_code(404);
    echo json_encode(['error' => 'Profile not found.']);
    exit;
}

$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if (!password_verify($currentPassword, (string) $profile['password'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Current password is incorrect.']);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(422);
    echo json_encode(['error' => 'New password must be at least 8 characters.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(422);
    echo json_encode(['error' => 'Password confirmation does not match.']);
    exit;
}

update_user_password((int) $userId, $newPassword);

echo json_encode(['message' => 'Password changed successfully.'], JSON_UNESCAPED_UNICODE);
