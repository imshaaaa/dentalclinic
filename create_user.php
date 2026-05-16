<?php

require_once __DIR__ . '/Config/bootstrap.php';
require_login(['admin']);
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . route_url('Admin/index.php?page=users'));
    exit;
}

$input = [
    'name' => trim($_POST['name'] ?? ''),
    'username' => trim($_POST['username'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'contact_number' => trim($_POST['contact_number'] ?? ''),
    'role' => trim($_POST['role'] ?? ''),
    'service_role' => trim($_POST['service_role'] ?? ''),
    'password' => (string) ($_POST['password'] ?? ''),
];

$errors = validate_admin_user_input($input);

if ($errors) {
    flash('admin_error', reset($errors));
    flash('admin_user_old', $input);
    header('Location: ' . route_url('Admin/index.php?page=users'));
    exit;
}

$createdUser = admin_create_user($input);
add_notification('User added', sprintf('%s was added as %s.', $createdUser['name'], user_role_label($createdUser['role'])));
flash('admin_message', sprintf('%s added successfully.', user_role_label($createdUser['role'])));

header('Location: ' . route_url('Admin/index.php?page=users'));
exit;
