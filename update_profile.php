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
$profile = profile_by_user_id((int) $userId);

if (!$profile) {
    http_response_code(404);
    echo json_encode(['error' => 'Profile not found.']);
    exit;
}

$role = canonical_role((string) $profile['role']);
$postedContactNumber = trim($_POST['contact_number'] ?? '');
$input = [
    'name' => trim($_POST['name'] ?? ''),
    'username' => trim($_POST['username'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'contact_number' => $role === 'admin' ? (string) ($profile['contact_number'] ?? '') : $postedContactNumber,
    'service_id' => trim($_POST['service_id'] ?? ''),
    'schedule' => trim($_POST['schedule'] ?? ''),
];
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($role === 'doctor') {
    $input['name'] = (string) ($profile['name'] ?? '');
    $input['username'] = (string) ($profile['username'] ?? '');
    $input['email'] = (string) ($profile['email'] ?? '');
    $input['contact_number'] = (string) ($profile['contact_number'] ?? '');
    $input['service_id'] = (string) ($profile['service_id'] ?? '');
}

$errors = [];
if ($input['name'] === '') {
    $errors[] = 'Name is required.';
}
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email is required.';
}
if (user_email_taken_by_other($input['email'], (int) $userId)) {
    $errors[] = 'That email address is already in use.';
}

if ($input['username'] === '') {
    $errors[] = 'Username is required.';
}
if ($input['username'] !== '' && username_taken_by_other($input['username'], (int) $userId)) {
    $errors[] = 'That username is already in use.';
}
if ($role !== 'admin' && !preg_match('/^\d{1,11}$/', $input['contact_number'])) {
    $errors[] = 'Contact number must contain digits only, up to 11 digits.';
}
if (in_array($role, ['doctor', 'secretary'], true) && $input['service_id'] !== '' && !service_by_id((int) $input['service_id'])) {
    $errors[] = 'Please choose a valid assigned service.';
}
if (in_array($role, ['doctor', 'secretary'], true) && $input['schedule'] !== '') {
    if (!schedule_input_is_valid($input['schedule'])) {
        $errors[] = 'Office schedule must use format like Monday 9:00 AM - 5:00 PM.';
    }
}
if ($newPassword !== '' || $confirmPassword !== '') {
    if (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['error' => $errors[0]]);
    exit;
}

$updated = update_profile_record((int) $userId, $input);
if (!$updated) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to update profile.']);
    exit;
}

if (in_array($role, ['doctor', 'secretary'], true)) {
    update_user_service_assignment((int) $userId, $input['service_id'] === '' ? null : (int) $input['service_id']);
    update_user_schedule((int) $userId, $input['schedule']);
    $updated = profile_by_user_id((int) $userId);
}

if ($newPassword !== '') {
    update_user_password((int) $userId, $newPassword);
}

$_SESSION['user']['name'] = $updated['name'];
$_SESSION['user']['email'] = $updated['email'];
$_SESSION['user']['username'] = $updated['username'];

$updated['schedule_display'] = schedule_display_value($updated['schedule']);

echo json_encode([
    'message' => 'Profile updated successfully.',
    'profile' => $updated,
], JSON_UNESCAPED_UNICODE);
