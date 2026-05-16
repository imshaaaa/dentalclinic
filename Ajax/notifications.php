<?php

require_once __DIR__ . '/../Config/bootstrap.php';
require_login(['admin', 'staff', 'secretary', 'doctor', 'dentist', 'patient']);

header('Content-Type: application/json');

verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'mark_all_read') {
    mark_all_notifications_read();
} elseif ($action === 'mark_read') {
    mark_notification_read((int) ($_POST['notification_id'] ?? 0));
} else {
    http_response_code(422);
    echo json_encode(['error' => 'Unknown notification action.']);
    exit;
}

echo json_encode([
    'notifications' => notifications(),
    'unread_count' => count(array_filter(notifications(), fn ($notification) => !$notification['is_read'])),
]);
