<?php

require_once __DIR__ . '/Config/bootstrap.php';
require_login(['admin']);
verify_csrf();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$userId = (int) ($_POST['user_id'] ?? 0);
$schedule = trim($_POST['schedule'] ?? '');

$profile = profile_by_user_id($userId);
if (!$profile || !in_array($profile['role'], ['doctor', 'secretary'], true)) {
    http_response_code(404);
    echo json_encode(['error' => 'Doctor or secretary profile not found.']);
    exit;
}

if ($schedule !== '' && !schedule_input_is_valid($schedule)) {
    http_response_code(422);
    echo json_encode(['error' => 'Office schedule must use format like Monday 9:00 AM - 5:00 PM.']);
    exit;
}

$updated = update_user_schedule($userId, $schedule);
if (!$updated) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to update schedule.']);
    exit;
}

echo json_encode([
    'message' => 'Schedule updated successfully.',
    'profile' => $updated,
], JSON_UNESCAPED_UNICODE);
