<?php

require_once __DIR__ . '/Config/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=UTF-8');

$currentUser = current_user();
$currentUserId = current_user_id();

if (isset($_GET['all'])) {
    if (!role_matches((string) $currentUser['role'], 'admin')) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied.']);
        exit;
    }

    $profiles = array_map(function ($profile) {
        $profile['schedule_display'] = schedule_display_value($profile['schedule']);
        return $profile;
    }, all_profiles());

    echo json_encode(['profiles' => $profiles], JSON_UNESCAPED_UNICODE);
    exit;
}

$requestedUserId = (int) ($_GET['user_id'] ?? $currentUserId);
if ($requestedUserId !== $currentUserId && !role_matches((string) $currentUser['role'], 'admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

$profile = profile_by_user_id($requestedUserId);
if (!$profile) {
    http_response_code(404);
    echo json_encode(['error' => 'Profile not found.']);
    exit;
}

$profile['schedule_display'] = schedule_display_value($profile['schedule']);

echo json_encode($profile, JSON_UNESCAPED_UNICODE);
