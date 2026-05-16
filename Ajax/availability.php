<?php

require_once __DIR__ . '/../Config/bootstrap.php';

header('Content-Type: application/json');

$serviceId = (int) ($_GET['service_id'] ?? 0);
$doctorId = (int) ($_GET['doctor_id'] ?? 0);
$date = $_GET['date'] ?? '';

if ($serviceId && !$date && !$doctorId) {
    echo json_encode([
        'doctors' => doctors_for_service($serviceId),
        'message' => 'Choose a doctor to see availability.',
    ]);
    exit;
}

if (!$serviceId || !$doctorId || !$date) {
    echo json_encode(['slots' => [], 'message' => 'Choose a service, doctor, and date to see available times.']);
    exit;
}

$slots = slots_for_service($serviceId, $date, null, $doctorId);
$message = empty($slots)
    ? 'No slots are available for this date. Please choose another date.'
    : 'Select a time slot to continue.';

echo json_encode([
    'slots' => $slots,
    'message' => $message,
]);
