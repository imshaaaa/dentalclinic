<?php

require_once __DIR__ . '/../Config/bootstrap.php';

$redirectHome = route_url();

if (isset($_GET['logout'])) {
    logout();
    flash('auth_message', 'You have been logged out.');
    header('Location: ' . $redirectHome);
    exit;
}

verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (attempt_login(trim($_POST['email'] ?? ''), trim($_POST['password'] ?? ''))) {
        $user = current_user();
        $target = match ($user['role']) {
            'dentist', 'doctor' => route_url('Dentist/index.php'),
            'secretary' => route_url('Secretary/index.php'),
            'patient' => route_url('Patient/index.php'),
            default => route_url('Admin/index.php'),
        };
        header('Location: ' . $target);
        exit;
    }
    flash('auth_error', 'Invalid email or password.');
    header('Location: ' . route_url('?login_error=1'));
    exit;
}

header('Location: ' . $redirectHome);
exit;
