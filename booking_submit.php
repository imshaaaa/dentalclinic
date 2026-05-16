<?php

require_once __DIR__ . '/Config/bootstrap.php';

verify_csrf();

$returnTo = trim($_POST['return_to'] ?? route_url('?modal=booking'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $returnTo);
    exit;
}

$serviceId = (int) ($_POST['service_id'] ?? 0);
$doctorId = (int) ($_POST['doctor_id'] ?? 0);
$date = trim($_POST['scheduled_date'] ?? '');
$startTime = trim($_POST['start_time'] ?? '');
$patientInput = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'contact_number' => trim($_POST['contact_number'] ?? ''),
    'notes' => trim($_POST['notes'] ?? ''),
];

$errors = validate_patient_input($patientInput);
$rule = $doctorId ? doctor_booking_rule($serviceId, $doctorId) : null;

if (!$rule) {
    $errors['service'] = 'Please choose a valid dental service and doctor.';
}

$validSlot = false;
if ($serviceId && $doctorId && $date && $startTime) {
    foreach (slots_for_service($serviceId, $date, null, $doctorId) as $slot) {
        if ($slot['start'] === $startTime && $slot['available']) {
            $validSlot = true;
            break;
        }
    }
}

if (!$date || !$startTime || !$validSlot) {
    $errors['schedule'] = 'Please choose a valid appointment date and time.';
}

if ($errors) {
    flash('booking_error', reset($errors));
    flash('booking_old', array_merge($patientInput, [
        'service_id' => $serviceId,
        'doctor_id' => $doctorId,
        'scheduled_date' => $date,
        'start_time' => $startTime,
    ]));
    header('Location: ' . $returnTo);
    exit;
}

try {
    $appointment = create_appointment([
        'service_id' => $serviceId,
        'doctor_id' => $doctorId,
        'scheduled_date' => $date,
        'start_time' => $startTime,
    ], $patientInput);

    flash('booking_success', $appointment);
    header('Location: ' . $returnTo);
    exit;
} catch (Throwable $exception) {
    flash('booking_error', $exception->getMessage());
    flash('booking_old', array_merge($patientInput, [
        'service_id' => $serviceId,
        'doctor_id' => $doctorId,
        'scheduled_date' => $date,
        'start_time' => $startTime,
    ]));
    header('Location: ' . $returnTo);
    exit;
}
