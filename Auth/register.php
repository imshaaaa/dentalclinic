<?php

require_once __DIR__ . '/../Config/bootstrap.php';

verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . route_url('?modal=register'));
    exit;
}

$input = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'contact_number' => trim($_POST['contact_number'] ?? ''),
    'username' => trim($_POST['username'] ?? ''),
    'password' => (string) ($_POST['password'] ?? ''),
];

$errors = validate_registration_input($input);

if ($errors) {
    flash('register_error', reset($errors));
    flash('register_old', [
        'full_name' => $input['full_name'],
        'email' => $input['email'],
        'contact_number' => $input['contact_number'],
        'username' => $input['username'],
    ]);
    header('Location: ' . route_url('?modal=register'));
    exit;
}

register_patient_account($input);
flash('auth_message', 'Account created successfully. You can now sign in.');
header('Location: ' . route_url('?modal=login'));
exit;
