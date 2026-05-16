<?php

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config;

    if (!$config) {
        $config = require __DIR__ . '/config.php';
        date_default_timezone_set($config['app']['timezone']);
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function asset_url(string $path): string
{
    return app_config('app.base_url') . '/Assets/' . ltrim($path, '/');
}

function route_url(string $path = ''): string
{
    return rtrim(app_config('app.base_url'), '/') . '/' . ltrim($path, '/');
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid session token.');
    }
}

function demo_mode(): bool
{
    return db() === null;
}

function flash(?string $key = null, mixed $value = null): mixed
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    if ($key !== null && $value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    if ($key !== null) {
        $stored = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $stored;
    }

    $all = $_SESSION['flash'];
    $_SESSION['flash'] = [];
    return $all;
}

function initialize_demo_data(): void
{
    if (!demo_mode() || isset($_SESSION['demo_data'])) {
        return;
    }

    $_SESSION['demo_data'] = [
        'services' => [
            ['id' => 1, 'category' => 'Preventive Care', 'name' => 'Braces', 'description' => 'Expert orthodontic care for aligned, confident smiles.', 'duration' => 60, 'buffer' => 15, 'price' => 4500.00, 'daily_limit' => 5, 'active' => 1],
            ['id' => 2, 'category' => 'Cosmetic Care', 'name' => 'Teeth Whitening', 'description' => 'Advanced whitening treatment for a brighter smile.', 'duration' => 45, 'buffer' => 15, 'price' => 3500.00, 'daily_limit' => 6, 'active' => 1],
            ['id' => 3, 'category' => 'Restorative Care', 'name' => 'Dental Implants', 'description' => 'Permanent, natural-looking tooth replacement with expert implant care.', 'duration' => 60, 'buffer' => 15, 'price' => 12000.00, 'daily_limit' => 4, 'active' => 1],
        ],
        'schedule' => [
            'slot_interval' => 15,
            'working_days' => [
                1 => ['open' => '09:00', 'close' => '17:00'],
                2 => ['open' => '09:00', 'close' => '17:00'],
                3 => ['open' => '09:00', 'close' => '17:00'],
                4 => ['open' => '09:00', 'close' => '17:00'],
                5 => ['open' => '09:00', 'close' => '17:00'],
                6 => ['open' => '09:00', 'close' => '15:00'],
            ],
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'blackouts' => [
                date('Y-m-d', strtotime('+9 days')),
            ],
        ],
        'users' => [
            ['id' => 1, 'name' => 'PrimeCare Admin', 'username' => 'primeadmin', 'email' => 'admin@primecare.test', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'role' => 'admin', 'contact_number' => '09170000001'],
            ['id' => 2, 'name' => 'Front Desk Staff', 'username' => 'frontdesk', 'email' => 'staff@primecare.test', 'password' => password_hash('staff123', PASSWORD_DEFAULT), 'role' => 'secretary', 'contact_number' => '09170000002'],
            ['id' => 3, 'name' => 'Dr. Sofia Lim', 'username' => 'drsofia', 'email' => 'dentist@primecare.test', 'password' => password_hash('dentist123', PASSWORD_DEFAULT), 'role' => 'dentist', 'contact_number' => '09170000003'],
            ['id' => 4, 'name' => 'Miguel Santos', 'username' => 'miguels', 'email' => 'miguel@example.com', 'password' => password_hash('patient123', PASSWORD_DEFAULT), 'role' => 'patient', 'contact_number' => '09171234567'],
        ],
        'staff_details' => [
            ['id' => 1, 'user_id' => 2, 'service_role' => 'Front Desk', 'schedule' => json_encode(['Mon-Fri 9:00 AM - 5:00 PM'])],
            ['id' => 2, 'user_id' => 3, 'service_role' => 'Orthodontist', 'schedule' => json_encode(['Mon-Thu 9:00 AM - 3:00 PM'])],
        ],
        'appointments' => [
            [
                'id' => 1,
                'reference_code' => 'PC-24001',
                'user_id' => 4,
                'service_id' => 1,
                'scheduled_date' => date('Y-m-d', strtotime('+1 day')),
                'start_time' => '09:00',
                'end_time' => '10:15',
                'status' => 'Approved',
                'patient_name' => 'Miguel Santos',
                'patient_email' => 'miguel@example.com',
                'patient_contact' => '09171234567',
                'service_name' => 'Braces',
                'service_fee' => 4500.00,
                'total_amount' => 4500.00,
                'notes' => 'Braces consultation',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'reference_code' => 'PC-24002',
                'user_id' => 4,
                'service_id' => 2,
                'scheduled_date' => date('Y-m-d', strtotime('+2 day')),
                'start_time' => '10:00',
                'end_time' => '11:00',
                'status' => 'Pending',
                'patient_name' => 'Miguel Santos',
                'patient_email' => 'miguel@example.com',
                'patient_contact' => '09171234567',
                'service_name' => 'Teeth Whitening',
                'service_fee' => 3500.00,
                'total_amount' => 3500.00,
                'notes' => 'First-time whitening inquiry',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ],
        'notifications' => [
            ['id' => 1, 'title' => 'New booking request', 'message' => 'Teeth whitening request is waiting for review.', 'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))],
            ['id' => 2, 'title' => 'Reminder sent', 'message' => 'Appointment reminder was sent to Miguel Santos.', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
        ],
    ];
}

function demo_store(string $key): array
{
    initialize_demo_data();
    return $_SESSION['demo_data'][$key] ?? [];
}

function demo_save(string $key, array $rows): void
{
    $_SESSION['demo_data'][$key] = array_values($rows);
}

function canonical_role(string $role): string
{
    return match ($role) {
        'dentist' => 'doctor',
        'staff' => 'secretary',
        default => $role,
    };
}

function role_matches(string $actualRole, string $requiredRole): bool
{
    return canonical_role($actualRole) === canonical_role($requiredRole);
}

function ensure_profile_schema(): void
{
    if (demo_mode()) {
        return;
    }

    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $pdo = db();
    if (!$pdo) {
        return;
    }

    $requiredTables = ['users', 'services', 'staff_details', 'doctor_schedules', 'appointments', 'notifications'];
    $existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];

    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables, true)) {
            throw new RuntimeException('Missing required table: ' . $table);
        }
    }

    $statusColumn = $pdo->query('SHOW COLUMNS FROM appointments LIKE "status"')->fetch();
    if ($statusColumn && !str_contains((string) $statusColumn['Type'], "'Accepted'")) {
        $pdo->exec("ALTER TABLE appointments MODIFY status ENUM('Pending','Accepted','Approved','Rejected','Cancelled','Completed') DEFAULT 'Pending'");
    }
}

function appointment_statuses(): array
{
    return ['Pending', 'Accepted', 'Approved', 'Rejected', 'Cancelled', 'Completed'];
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'Accepted', 'Approved', 'Completed' => 'badge-success',
        'Rejected', 'Cancelled' => 'badge-danger',
        'Pending' => 'badge-warning',
        default => 'badge-muted',
    };
}

function services(): array
{
    if (demo_mode()) {
        return array_values(array_filter(demo_store('services'), fn ($service) => (int) $service['active'] === 1));
    }

    return db()->query('SELECT id, name, category, description, duration, buffer, price, daily_limit, active FROM services WHERE active = 1 ORDER BY category, name')->fetchAll();
}

function services_by_category(): array
{
    $grouped = [];
    foreach (services() as $service) {
        $categoryName = $service['category'] ?: 'General';
        $categoryKey = strtolower($categoryName);
        if (!isset($grouped[$categoryKey])) {
            $grouped[$categoryKey] = [
                'category' => [
                    'id' => $categoryKey,
                    'name' => $categoryName,
                    'description' => 'PrimeCare ' . $categoryName . ' services.',
                ],
                'services' => [],
            ];
        }
        $grouped[$categoryKey]['services'][] = $service;
    }

    return array_values($grouped);
}

function service_by_id(int $serviceId): ?array
{
    foreach (services() as $service) {
        if ((int) $service['id'] === $serviceId) {
            return $service;
        }
    }

    return null;
}

function service_booking_rules(): array
{
    return [
        'braces' => [
            'doctor_name' => 'Dr. Norielyn Funtanar',
            'schedule_text' => 'Monday to Thursday 9:00 AM - 3:00 PM',
            'weekdays' => [1, 2, 3, 4],
            'start' => '09:00',
            'end' => '15:00',
        ],
        'teeth whitening' => [
            'doctor_name' => 'Dr. Nicole Marikit',
            'schedule_text' => 'Monday to Friday 10:00 AM - 4:00 PM',
            'weekdays' => [1, 2, 3, 4, 5],
            'start' => '10:00',
            'end' => '16:00',
        ],
        'dental implants' => [
            'doctor_name' => 'Dr. Shallom Kyle Jacinto',
            'schedule_text' => 'Wednesday to Friday 11:00 AM - 4:00 PM',
            'weekdays' => [3, 4, 5],
            'start' => '11:00',
            'end' => '16:00',
        ],
    ];
}

function service_booking_rule(int $serviceId): ?array
{
    $service = service_by_id($serviceId);
    if (!$service) {
        return null;
    }

    if (!demo_mode()) {
        $statement = db()->prepare('
            SELECT u.id, u.full_name, ds.day_of_week, ds.start_time, ds.end_time
            FROM staff_details sd
            INNER JOIN users u ON u.id = sd.user_id AND u.role = "dentist"
            INNER JOIN doctor_schedules ds ON ds.user_id = u.id
            WHERE sd.assigned_service_id = :service_id
            ORDER BY
                CASE WHEN sd.assigned_service_id = :service_id_order THEN 0 ELSE 1 END,
                FIELD(ds.day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"),
                ds.start_time
        ');
        $statement->execute(['service_id' => $serviceId, 'service_id_order' => $serviceId]);
        $rows = $statement->fetchAll();

        if ($rows) {
            $weekdays = [];
            foreach ($rows as $row) {
                $weekday = weekday_number($row['day_of_week']);
                $weekdays[] = $weekday;
            }

            return [
                'doctor_id' => (int) $rows[0]['id'],
                'doctor_name' => $rows[0]['full_name'],
                'schedule_text' => implode("\n", format_schedule_rows($rows)),
                'weekdays' => array_values(array_unique($weekdays)),
                'start' => substr((string) $rows[0]['start_time'], 0, 5),
                'end' => substr((string) $rows[0]['end_time'], 0, 5),
                'service' => $service,
            ];
        }
    }

    $rules = service_booking_rules();
    $rule = $rules[strtolower($service['name'])] ?? null;

    if (!$rule) {
        return null;
    }

    return array_merge($rule, ['service' => $service]);
}

function doctors_for_service(int $serviceId): array
{
    if (demo_mode()) {
        $doctors = [];
        foreach (demo_store('users') as $user) {
            if (canonical_role((string) $user['role']) !== 'doctor') {
                continue;
            }
            $detail = doctor_secretary_detail_by_user_id((int) $user['id']);
            $assignedServiceId = (int) ($detail['assigned_service_id'] ?? 0);
            if ($assignedServiceId && $assignedServiceId !== $serviceId) {
                continue;
            }
            $doctors[] = [
                'id' => (int) $user['id'],
                'name' => $user['full_name'] ?? $user['name'],
                'schedule_text' => schedule_display_value($detail['schedule'] ?? null),
            ];
        }
        return $doctors;
    }

    $statement = db()->prepare('
        SELECT
            u.id,
            u.full_name AS name,
            GROUP_CONCAT(
                CONCAT(ds.day_of_week, " ", TIME_FORMAT(ds.start_time, "%h:%i %p"), " - ", TIME_FORMAT(ds.end_time, "%h:%i %p"))
                ORDER BY FIELD(ds.day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), ds.start_time
                SEPARATOR ", "
            ) AS schedule_text
        FROM users u
        INNER JOIN staff_details sd ON sd.user_id = u.id
        LEFT JOIN doctor_schedules ds ON ds.user_id = u.id
        WHERE u.role = "dentist" AND sd.assigned_service_id = :service_id
        GROUP BY u.id, u.full_name
        ORDER BY CASE WHEN MAX(sd.assigned_service_id = :service_id_order) THEN 0 ELSE 1 END, u.full_name
    ');
    $statement->execute(['service_id' => $serviceId, 'service_id_order' => $serviceId]);
    return array_map(fn ($doctor) => [
        'id' => (int) $doctor['id'],
        'name' => $doctor['name'],
        'schedule_text' => schedule_display_value($doctor['schedule_text'] ?: null),
    ], $statement->fetchAll());
}

function doctor_booking_rule(int $serviceId, int $doctorId): ?array
{
    $service = service_by_id($serviceId);
    if (!$service) {
        return null;
    }

    if (!demo_mode()) {
        $statement = db()->prepare('
            SELECT u.id, u.full_name, ds.day_of_week, ds.start_time, ds.end_time
            FROM users u
            INNER JOIN staff_details sd ON sd.user_id = u.id
            INNER JOIN doctor_schedules ds ON ds.user_id = u.id
            WHERE u.id = :doctor_id
              AND u.role = "dentist"
              AND sd.assigned_service_id = :service_id
            ORDER BY FIELD(ds.day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), ds.start_time
        ');
        $statement->execute(['doctor_id' => $doctorId, 'service_id' => $serviceId]);
        $rows = $statement->fetchAll();

        if ($rows) {
            $weekdays = [];
            foreach ($rows as $row) {
                $weekdays[] = weekday_number($row['day_of_week']);
            }

            return [
                'doctor_id' => (int) $rows[0]['id'],
                'doctor_name' => $rows[0]['full_name'],
                'schedule_text' => implode("\n", format_schedule_rows($rows)),
                'weekdays' => array_values(array_unique($weekdays)),
                'start' => substr((string) $rows[0]['start_time'], 0, 5),
                'end' => substr((string) $rows[0]['end_time'], 0, 5),
                'service' => $service,
            ];
        }
    }

    $rule = service_booking_rule($serviceId);
    if (!$rule) {
        return null;
    }
    $doctor = current(array_filter(doctors_for_service($serviceId), fn ($doctor) => (int) $doctor['id'] === $doctorId));
    return $doctor ? array_merge($rule, ['doctor_id' => $doctorId, 'doctor_name' => $doctor['name']]) : null;
}

function patient_by_email(string $email): ?array
{
    foreach (patients() as $patient) {
        if (strtolower((string) $patient['email']) === strtolower($email)) {
            return $patient;
        }
    }

    return null;
}

function clinic_schedule(): array
{
    if (demo_mode()) {
        return demo_store('schedule');
    }

    return [
        'slot_interval' => 15,
        'working_days' => [
            1 => ['open' => '09:00', 'close' => '17:00'],
            2 => ['open' => '09:00', 'close' => '17:00'],
            3 => ['open' => '09:00', 'close' => '17:00'],
            4 => ['open' => '09:00', 'close' => '17:00'],
            5 => ['open' => '09:00', 'close' => '17:00'],
        ],
        'breaks' => [
            ['start' => '12:00', 'end' => '13:00'],
        ],
        'blackouts' => [],
    ];
}

function patients(): array
{
    if (demo_mode()) {
        return array_values(array_map(
            fn ($user) => [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'] ?? $user['name'],
                'email' => $user['email'],
                'contact_number' => $user['contact_number'] ?? '',
                'username' => $user['username'] ?? null,
                'role' => 'patient',
            ],
            array_filter(demo_store('users'), fn ($user) => canonical_role((string) $user['role']) === 'patient')
        ));
    }

    return db()->query("
        SELECT id, full_name, email, contact_number, username, role
        FROM users
        WHERE role = 'patient'
        ORDER BY full_name
    ")->fetchAll();
}

function patient_by_id(int $patientId): ?array
{
    foreach (patients() as $patient) {
        if ((int) $patient['id'] === $patientId) {
            return $patient;
        }
    }

    return null;
}

function upsert_patient(array $input): int
{
    if (demo_mode()) {
        $users = demo_store('users');
        foreach ($users as &$user) {
            if (strtolower($user['email']) === strtolower($input['email']) && canonical_role((string) $user['role']) === 'patient') {
                $user['name'] = $input['full_name'];
                $user['full_name'] = $input['full_name'];
                $user['contact_number'] = $input['contact_number'];
                demo_save('users', $users);
                return (int) $user['id'];
            }
        }

        $user = [
            'id' => next_id($users),
            'name' => $input['full_name'],
            'full_name' => $input['full_name'],
            'username' => $input['username'] ?? default_username_from_input($input['full_name'], $input['email']),
            'email' => $input['email'],
            'password' => $input['password'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'role' => 'patient',
            'contact_number' => $input['contact_number'],
        ];
        $users[] = $user;
        demo_save('users', $users);
        return (int) $user['id'];
    }

    $statement = db()->prepare('SELECT id, role FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $input['email']]);
    $existing = $statement->fetch();

    if ($existing) {
        if (canonical_role((string) $existing['role']) !== 'patient') {
            throw new RuntimeException('That email address belongs to a staff account. Please use a patient email.');
        }
        $update = db()->prepare('UPDATE users SET full_name = :full_name, contact_number = :contact_number WHERE id = :id');
        $update->execute([
            'full_name' => $input['full_name'],
            'contact_number' => $input['contact_number'],
            'id' => $existing['id'],
        ]);
        return (int) $existing['id'];
    }

    $insert = db()->prepare('
        INSERT INTO users (full_name, username, email, password, role, contact_number)
        VALUES (:full_name, :username, :email, :password, :role, :contact_number)
    ');
    $insert->execute([
        'full_name' => $input['full_name'],
        'username' => $input['username'] ?? default_username_from_input($input['full_name'], $input['email']),
        'email' => $input['email'],
        'password' => $input['password'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
        'role' => 'patient',
        'contact_number' => $input['contact_number'],
    ]);
    return (int) db()->lastInsertId();
}

function users(): array
{
    if (demo_mode()) {
        return array_map('profile_payload_from_user', demo_store('users'));
    }

    ensure_profile_schema();

    $sql = '
        SELECT
            u.id,
            u.full_name AS name,
            u.full_name,
            u.username,
            u.email,
            u.role,
            u.contact_number,
            sd.assigned_service_id AS service_id,
            s.name AS service_role,
            sched.schedule
        FROM users u
        LEFT JOIN staff_details sd ON sd.user_id = u.id
        LEFT JOIN services s ON s.id = sd.assigned_service_id
        LEFT JOIN (
            SELECT
                user_id,
                GROUP_CONCAT(
                    CONCAT(day_of_week, " ", TIME_FORMAT(start_time, "%h:%i %p"), " - ", TIME_FORMAT(end_time, "%h:%i %p"))
                    ORDER BY FIELD(day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), start_time
                    SEPARATOR ", "
                ) AS schedule
            FROM doctor_schedules
            GROUP BY user_id
        ) sched ON sched.user_id = u.id
        ORDER BY u.role, u.full_name
    ';

    return array_map('profile_payload_from_user', db()->query($sql)->fetchAll());
}

function user_email_exists(string $email): bool
{
    foreach (demo_mode() ? demo_store('users') : users() as $user) {
        if (strtolower((string) $user['email']) === strtolower($email)) {
            return true;
        }
    }

    if (!demo_mode()) {
        $statement = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        return (bool) $statement->fetchColumn();
    }

    return false;
}

function user_email_taken_by_other(string $email, int $excludeUserId): bool
{
    foreach (all_profiles() as $user) {
        if ((int) $user['id'] !== $excludeUserId && strtolower((string) $user['email']) === strtolower($email)) {
            return true;
        }
    }

    return false;
}

function username_taken_by_other(string $username, int $excludeUserId): bool
{
    foreach (all_profiles() as $user) {
        if ((int) $user['id'] !== $excludeUserId && strtolower((string) ($user['username'] ?? '')) === strtolower($username)) {
            return true;
        }
    }

    return false;
}

function admin_user_roles(): array
{
    return [
        'doctor' => 'Doctor',
        'secretary' => 'Secretary',
    ];
}

function user_role_storage_value(string $role): string
{
    return match (canonical_role($role)) {
        'doctor' => 'dentist',
        'secretary' => 'secretary',
        'admin' => 'admin',
        default => 'patient',
    };
}

function service_id_from_assignment(?string $assignment): ?int
{
    $assignment = trim((string) $assignment);
    if ($assignment === '') {
        return null;
    }

    foreach (services() as $service) {
        if ((string) $service['id'] === $assignment || strtolower((string) $service['name']) === strtolower($assignment)) {
            return (int) $service['id'];
        }
    }

    return null;
}

function default_username_from_input(string $fullName, string $email): string
{
    $base = preg_replace('/[^a-z0-9]+/i', '', strtolower($fullName));
    if ($base === '') {
        $base = strstr(strtolower($email), '@', true) ?: 'user';
        $base = preg_replace('/[^a-z0-9]+/i', '', $base);
    }
    if ($base === '') {
        $base = 'user';
    }

    $candidate = $base;
    $counter = 1;
    while (username_taken_by_other($candidate, 0)) {
        $counter++;
        $candidate = $base . $counter;
    }

    return $candidate;
}

function weekday_name(int $weekday): string
{
    return [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'][$weekday] ?? 'Monday';
}

function weekday_number(string $weekday): int
{
    return array_search($weekday, [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'], true) ?: 1;
}

function normalize_weekday_name(string $weekday): ?string
{
    $normalized = strtolower(trim($weekday));
    $aliases = [
        'mon' => 'Monday',
        'monday' => 'Monday',
        'tue' => 'Tuesday',
        'tues' => 'Tuesday',
        'tuesday' => 'Tuesday',
        'wed' => 'Wednesday',
        'wednesday' => 'Wednesday',
        'thu' => 'Thursday',
        'thur' => 'Thursday',
        'thurs' => 'Thursday',
        'thursday' => 'Thursday',
        'fri' => 'Friday',
        'friday' => 'Friday',
        'sat' => 'Saturday',
        'saturday' => 'Saturday',
        'sun' => 'Sunday',
        'sunday' => 'Sunday',
    ];

    return $aliases[$normalized] ?? null;
}

function format_schedule_time(string $time): string
{
    $timestamp = strtotime($time);
    return $timestamp ? date('g:i A', $timestamp) : trim($time);
}

function parse_schedule_entries(?string $schedule): array
{
    if (!$schedule) {
        return [];
    }

    $decoded = json_decode($schedule, true);
    $entries = json_last_error() === JSON_ERROR_NONE && is_array($decoded)
        ? array_map('strval', $decoded)
        : preg_split('/[\r\n,]+/', $schedule);

    $rows = [];
    foreach ($entries ?: [] as $entry) {
        $entry = trim((string) $entry);
        if ($entry === '') {
            continue;
        }

        if (!preg_match('/^([A-Za-z]+)(?:\s*(?:to|-)\s*([A-Za-z]+))?\s*\|?\s*(\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)$/i', $entry, $matches)) {
            continue;
        }

        $startDay = normalize_weekday_name($matches[1]);
        $endDay = normalize_weekday_name($matches[2] !== '' ? $matches[2] : $matches[1]);
        if (!$startDay || !$endDay) {
            continue;
        }

        $startIndex = weekday_number($startDay);
        $endIndex = weekday_number($endDay);
        if ($endIndex < $startIndex) {
            continue;
        }

        $startTime = format_schedule_time($matches[3]);
        $endTime = format_schedule_time($matches[4]);
        for ($dayIndex = $startIndex; $dayIndex <= $endIndex; $dayIndex++) {
            $rows[] = [
                'day_of_week' => weekday_name($dayIndex),
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }
    }

    return $rows;
}

function schedule_input_is_valid(string $schedule): bool
{
    $entries = preg_split('/[\r\n,]+/', $schedule);
    $hasEntry = false;

    foreach ($entries ?: [] as $entry) {
        $entry = trim((string) $entry);
        if ($entry === '') {
            continue;
        }

        $hasEntry = true;
        if (!preg_match('/^([A-Za-z]+)(?:\s*(?:to|-)\s*([A-Za-z]+))?\s*\|?\s*(\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)$/i', $entry, $matches)) {
            return false;
        }

        $startDay = normalize_weekday_name($matches[1]);
        $endDay = normalize_weekday_name($matches[2] !== '' ? $matches[2] : $matches[1]);
        if (!$startDay || !$endDay || weekday_number($endDay) < weekday_number($startDay)) {
            return false;
        }
    }

    return $hasEntry;
}

function format_schedule_rows(array $rows): array
{
    if (!$rows) {
        return [];
    }

    usort($rows, function (array $left, array $right): int {
        return [weekday_number((string) $left['day_of_week']), strtotime((string) $left['start_time'])]
            <=> [weekday_number((string) $right['day_of_week']), strtotime((string) $right['start_time'])];
    });

    $groups = [];
    foreach ($rows as $row) {
        $day = normalize_weekday_name((string) ($row['day_of_week'] ?? ''));
        if (!$day) {
            continue;
        }

        $startTime = format_schedule_time((string) ($row['start_time'] ?? ''));
        $endTime = format_schedule_time((string) ($row['end_time'] ?? ''));
        if ($startTime === '' || $endTime === '') {
            continue;
        }

        $dayIndex = weekday_number($day);
        $timeKey = $startTime . '|' . $endTime;
        $lastIndex = count($groups) - 1;

        // Group only directly consecutive days with the exact same time range.
        if ($lastIndex >= 0 && $groups[$lastIndex]['time_key'] === $timeKey && $groups[$lastIndex]['end_index'] + 1 === $dayIndex) {
            $groups[$lastIndex]['end_day'] = $day;
            $groups[$lastIndex]['end_index'] = $dayIndex;
            continue;
        }

        $groups[] = [
            'start_day' => $day,
            'end_day' => $day,
            'end_index' => $dayIndex,
            'time_key' => $timeKey,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    return array_map(function (array $group): string {
        $dayLabel = $group['start_day'] === $group['end_day']
            ? $group['start_day']
            : $group['start_day'] . ' to ' . $group['end_day'];

        return sprintf('%s %s - %s', $dayLabel, $group['start_time'], $group['end_time']);
    }, $groups);
}

function schedule_display_lines(?string $schedule): array
{
    return format_schedule_rows(parse_schedule_entries($schedule));
}

function render_schedule_display(?string $schedule): string
{
    $lines = schedule_display_lines($schedule);
    if (!$lines) {
        return '<span class="schedule-line schedule-empty">No schedule assigned yet.</span>';
    }

    return '<span class="schedule-stack">' . implode('', array_map(
        fn (string $line): string => '<span class="schedule-line">' . h($line) . '</span>',
        $lines
    )) . '</span>';
}

function schedule_rows_by_user_id(int $userId): array
{
    if (demo_mode()) {
        $detail = doctor_secretary_detail_by_user_id($userId);
        if (!$detail || empty($detail['schedule'])) {
            return [];
        }

        return array_map(fn (string $line): array => ['label' => $line], schedule_display_lines($detail['schedule']));
    }

    $statement = db()->prepare('SELECT day_of_week, start_time, end_time FROM doctor_schedules WHERE user_id = :user_id ORDER BY FIELD(day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), start_time');
    $statement->execute(['user_id' => $userId]);
    return $statement->fetchAll();
}

function user_role_label(string $role): string
{
    return match ($role) {
        'staff', 'secretary' => 'Secretary',
        'doctor', 'dentist' => 'Doctor',
        'admin' => 'Admin',
        'patient' => 'Patient',
        default => ucfirst($role),
    };
}

function validate_admin_user_input(array $input): array
{
    $errors = [];
    $username = trim((string) ($input['username'] ?? ''));
    $contactNumber = trim((string) ($input['contact_number'] ?? ''));

    if (trim((string) ($input['name'] ?? '')) === '') {
        $errors['name'] = 'Please enter the full name.';
    }

    if (!filter_var((string) ($input['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (user_email_exists((string) $input['email'])) {
        $errors['email'] = 'That email address is already registered.';
    }

    if (!array_key_exists((string) ($input['role'] ?? ''), admin_user_roles())) {
        $errors['role'] = 'Please choose a valid role.';
    }

    if (($input['role'] ?? '') === 'doctor' && !service_id_from_assignment($input['service_role'] ?? null)) {
        $errors['service_role'] = 'Please choose a valid service for the doctor.';
    }

    if (strlen((string) ($input['password'] ?? '')) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($username === '') {
        $errors['username'] = 'Please enter a username.';
    } elseif (username_taken_by_other($username, 0)) {
        $errors['username'] = 'That username is already registered.';
    }

    if (!preg_match('/^\d{1,11}$/', $contactNumber)) {
        $errors['contact_number'] = 'Contact number must contain digits only, up to 11 digits.';
    }

    return $errors;
}

function admin_create_user(array $input): array
{
    $role = (string) $input['role'];
    $user = [
        'full_name' => trim((string) $input['name']),
        'username' => trim((string) ($input['username'] ?? '')),
        'email' => trim((string) $input['email']),
        'password' => password_hash((string) $input['password'], PASSWORD_DEFAULT),
        'role' => user_role_storage_value($role),
        'contact_number' => trim((string) ($input['contact_number'] ?? '')) ?: null,
    ];

    if (demo_mode()) {
        $users = demo_store('users');
        $user['id'] = next_id($users);
        $user['name'] = $user['full_name'];
        $users[] = $user;
        demo_save('users', $users);
        $details = demo_store('staff_details');
        $details[] = [
            'id' => next_id($details),
            'user_id' => $user['id'],
            'service_role' => trim((string) ($input['service_role'] ?? '')),
            'schedule' => null,
        ];
        demo_save('staff_details', $details);

        return profile_payload_from_user($user);
    }

    ensure_profile_schema();
    $statement = db()->prepare('INSERT INTO users (full_name, username, email, password, role, contact_number) VALUES (:full_name, :username, :email, :password, :role, :contact_number)');
    $statement->execute($user);
    $userId = (int) db()->lastInsertId();

    $serviceId = service_id_from_assignment($input['service_role'] ?? null);
    $detailStatement = db()->prepare('INSERT INTO staff_details (user_id, assigned_service_id) VALUES (:user_id, :assigned_service_id)');
    $detailStatement->execute([
        'user_id' => $userId,
        'assigned_service_id' => $serviceId,
    ]);

    return profile_by_user_id($userId);
}

function doctor_secretary_detail_by_user_id(int $userId): ?array
{
    if (demo_mode()) {
        foreach (demo_store('staff_details') as $detail) {
            if ((int) $detail['user_id'] === $userId) {
                return $detail;
            }
        }
        return null;
    }

    ensure_profile_schema();
    $statement = db()->prepare('
        SELECT
            sd.user_id,
            s.name AS service_role,
            (
                SELECT GROUP_CONCAT(
                    CONCAT(day_of_week, " ", TIME_FORMAT(start_time, "%h:%i %p"), " - ", TIME_FORMAT(end_time, "%h:%i %p"))
                    ORDER BY FIELD(day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), start_time
                    SEPARATOR ", "
                )
                FROM doctor_schedules ds
                WHERE ds.user_id = sd.user_id
            ) AS schedule
        FROM staff_details sd
        LEFT JOIN services s ON s.id = sd.assigned_service_id
        WHERE sd.user_id = :user_id
        LIMIT 1
    ');
    $statement->execute(['user_id' => $userId]);
    return $statement->fetch() ?: null;
}

function profile_payload_from_user(array $user): array
{
    $detail = array_key_exists('service_role', $user) ? $user : doctor_secretary_detail_by_user_id((int) $user['id']);
    $displayName = $user['name'] ?? $user['full_name'] ?? null;

    return [
        'id' => (int) $user['id'],
        'name' => $displayName,
        'full_name' => $displayName,
        'username' => $user['username'] ?? null,
        'email' => $user['email'],
        'role' => canonical_role((string) $user['role']),
        'role_label' => user_role_label((string) $user['role']),
        'contact_number' => $user['contact_number'] ?? null,
        'service_id' => $user['service_id'] ?? null,
        'service_role' => is_array($detail) ? ($detail['service_role'] ?? null) : null,
        'schedule' => is_array($detail) ? ($detail['schedule'] ?? null) : null,
    ];
}

function schedule_display_value(?string $schedule): string
{
    $lines = schedule_display_lines($schedule);
    return $lines ? implode("\n", $lines) : 'No schedule assigned yet.';
}

function profile_by_user_id(int $userId, bool $includePassword = false): ?array
{
    if (demo_mode()) {
        foreach (demo_store('users') as $user) {
            if ((int) $user['id'] === $userId) {
                $payload = profile_payload_from_user($user);
                if ($includePassword) {
                    $payload['password'] = $user['password'];
                }
                return $payload;
            }
        }
        return null;
    }

    ensure_profile_schema();
    $fields = 'u.id, u.full_name AS name, u.full_name, u.username, u.email, u.role, u.contact_number, sd.assigned_service_id AS service_id, s.name AS service_role,
        (
            SELECT GROUP_CONCAT(
                CONCAT(ds.day_of_week, " ", TIME_FORMAT(ds.start_time, "%h:%i %p"), " - ", TIME_FORMAT(ds.end_time, "%h:%i %p"))
                ORDER BY FIELD(ds.day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), ds.start_time
                SEPARATOR ", "
            )
            FROM doctor_schedules ds
            WHERE ds.user_id = u.id
        ) AS schedule';
    if ($includePassword) {
        $fields .= ', u.password';
    }

    $statement = db()->prepare("
        SELECT {$fields}
        FROM users u
        LEFT JOIN staff_details sd ON sd.user_id = u.id
        LEFT JOIN services s ON s.id = sd.assigned_service_id
        WHERE u.id = :id
        LIMIT 1
    ");
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    if (!$user) {
        return null;
    }

    return profile_payload_from_user($user) + ($includePassword ? ['password' => $user['password']] : []);
}

function all_profiles(): array
{
    return users();
}

function patient_appointments_by_email(string $email): array
{
    return array_values(array_filter(appointments(), function ($appointment) use ($email) {
        return strtolower((string) ($appointment['patient']['email'] ?? '')) === strtolower($email);
    }));
}

function patient_upcoming_appointments(array $appointments): array
{
    return array_values(array_filter($appointments, function (array $appointment): bool {
        return in_array($appointment['status'], ['Pending', 'Accepted'], true)
            && strtotime($appointment['scheduled_date'] . ' ' . $appointment['start_time']) >= time();
    }));
}

function patient_completed_appointments(array $appointments): array
{
    return array_values(array_filter(
        $appointments,
        fn (array $appointment): bool => $appointment['status'] === 'Completed'
    ));
}

function ensure_notification_schema(): void
{
    if (demo_mode()) {
        return;
    }

    $column = db()->query('SHOW COLUMNS FROM notifications LIKE "is_read"')->fetch();
    if (!$column) {
        db()->exec('ALTER TABLE notifications ADD is_read TINYINT(1) DEFAULT 0 AFTER message');
    }
}

function normalize_notification(array $notification): array
{
    $notification['id'] = (int) ($notification['id'] ?? 0);
    $notification['message'] = (string) ($notification['message'] ?? '');
    $notification['title'] = (string) ($notification['title'] ?? 'Notification');
    $notification['created_at'] = (string) ($notification['created_at'] ?? date('Y-m-d H:i:s'));
    $notification['is_read'] = (bool) ($notification['is_read'] ?? false);
    return $notification;
}

function notification_is_appointment_related(array $notification): bool
{
    $text = strtolower(trim(($notification['title'] ?? '') . ' ' . ($notification['message'] ?? '')));
    if ($text === '') {
        return false;
    }

    return str_contains($text, 'appointment')
        || str_contains($text, 'booking')
        || str_contains($text, 'booked')
        || str_contains($text, 'accepted')
        || str_contains($text, 'approved')
        || str_contains($text, 'cancelled')
        || str_contains($text, 'canceled')
        || str_contains($text, 'rescheduled')
        || str_contains($text, 'completed');
}

function notification_is_appointment_update(array $notification): bool
{
    $text = strtolower(trim(($notification['title'] ?? '') . ' ' . ($notification['message'] ?? '')));
    if ($text === '') {
        return false;
    }

    return (str_contains($text, 'appointment') || str_contains($text, 'booking'))
        && (
            str_contains($text, 'accepted')
            || str_contains($text, 'approved')
            || str_contains($text, 'rescheduled')
            || str_contains($text, 'cancelled')
            || str_contains($text, 'canceled')
            || str_contains($text, 'completed')
        );
}

function notification_visible_for_role(array $notification, ?string $role = null): bool
{
    $role = canonical_role((string) ($role ?? (current_user()['role'] ?? '')));

    if ($role === 'admin') {
        return true;
    }

    // Dentists and secretaries only receive appointment workflow notifications.
    if (in_array($role, ['doctor', 'secretary'], true)) {
        return notification_is_appointment_related($notification);
    }

    // Patients only see appointment status/update notifications, not system or admin events.
    if ($role === 'patient') {
        return notification_is_appointment_update($notification);
    }

    return true;
}

function notifications(int $limit = 20): array
{
    if (demo_mode()) {
        $notifications = array_map('normalize_notification', demo_store('notifications'));
        $notifications = array_values(array_filter($notifications, 'notification_visible_for_role'));
        return array_slice($notifications, 0, $limit);
    }

    ensure_notification_schema();
    $statement = db()->query('SELECT id, user_id, title, message, is_read, created_at FROM notifications ORDER BY created_at DESC');

    $notifications = array_map('normalize_notification', $statement->fetchAll());
    $notifications = array_values(array_filter($notifications, 'notification_visible_for_role'));
    return array_slice($notifications, 0, $limit);
}

function mark_notification_read(int $notificationId): void
{
    if (demo_mode()) {
        $notifications = array_map('normalize_notification', demo_store('notifications'));
        foreach ($notifications as &$notification) {
            if ((int) $notification['id'] === $notificationId && notification_visible_for_role($notification)) {
                $notification['is_read'] = true;
            }
        }
        demo_save('notifications', $notifications);
        return;
    }

    ensure_notification_schema();
    $statement = db()->prepare('SELECT id, user_id, title, message, is_read, created_at FROM notifications WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $notificationId]);
    $notification = $statement->fetch();
    if (!$notification || !notification_visible_for_role(normalize_notification($notification))) {
        return;
    }

    $statement = db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
    $statement->execute(['id' => $notificationId]);
}

function mark_all_notifications_read(): void
{
    if (demo_mode()) {
        $notifications = array_map('normalize_notification', demo_store('notifications'));
        foreach ($notifications as &$notification) {
            if (notification_visible_for_role($notification)) {
                $notification['is_read'] = true;
            }
        }
        demo_save('notifications', $notifications);
        return;
    }

    ensure_notification_schema();
    if (canonical_role((string) (current_user()['role'] ?? '')) === 'admin') {
        db()->exec('UPDATE notifications SET is_read = 1');
        return;
    }

    foreach (notifications(1000) as $notification) {
        mark_notification_read((int) $notification['id']);
    }
}

function next_id(array $rows): int
{
    return empty($rows) ? 1 : max(array_map(fn ($row) => (int) $row['id'], $rows)) + 1;
}

function appointment_with_relations(array $appointment): array
{
    $appointment['user_id'] = (int) ($appointment['user_id'] ?? 0);
    $patientRecord = !empty($appointment['patient_name']) ? null : patient_by_id($appointment['user_id']);
    $appointment['patient'] = [
        'id' => $appointment['user_id'],
        'full_name' => $appointment['patient_name'] ?? ($patientRecord['full_name'] ?? null),
        'email' => $appointment['patient_email'] ?? ($patientRecord['email'] ?? null),
        'contact_number' => $appointment['patient_contact'] ?? ($patientRecord['contact_number'] ?? null),
    ];
    $appointment['service'] = service_by_id((int) $appointment['service_id']);
    $appointment['staff'] = !empty($appointment['staff_id']) ? profile_by_user_id((int) $appointment['staff_id']) : null;
    return $appointment;
}

function appointments(array $filters = []): array
{
    if (demo_mode()) {
        $appointments = demo_store('appointments');
    } else {
        $appointments = db()->query('SELECT * FROM appointments ORDER BY scheduled_date, start_time')->fetchAll();
    }

    $appointments = array_map('appointment_with_relations', $appointments);

    return array_values(array_filter($appointments, function ($appointment) use ($filters) {
        if (!empty($filters['status']) && $appointment['status'] !== $filters['status']) {
            return false;
        }
        if (!empty($filters['service_id']) && (int) $appointment['service_id'] !== (int) $filters['service_id']) {
            return false;
        }
        if (!empty($filters['date']) && $appointment['scheduled_date'] !== $filters['date']) {
            return false;
        }
        if (!empty($filters['staff_id']) && (int) ($appointment['staff_id'] ?? 0) !== (int) $filters['staff_id']) {
            return false;
        }
        if (!empty($filters['user_id']) && (int) ($appointment['user_id'] ?? 0) !== (int) $filters['user_id']) {
            return false;
        }
        return true;
    }));
}

function generate_reference(): string
{
    return 'PC-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function parse_time_string(string $date, string $time): DateTimeImmutable
{
    return new DateTimeImmutable($date . ' ' . $time);
}

function overlaps(string $date, string $start, string $end, array $appointment): bool
{
    $candidateStart = parse_time_string($date, $start);
    $candidateEnd = parse_time_string($date, $end);
    $existingStart = parse_time_string($appointment['scheduled_date'], $appointment['start_time']);
    $existingEnd = parse_time_string($appointment['scheduled_date'], $appointment['end_time']);

    return $candidateStart < $existingEnd && $candidateEnd > $existingStart;
}

function day_schedule(string $date): ?array
{
    $schedule = clinic_schedule();
    $weekday = (int) date('N', strtotime($date));

    if (in_array($date, $schedule['blackouts'], true) || empty($schedule['working_days'][$weekday])) {
        return null;
    }

    return [
        'date' => $date,
        'hours' => $schedule['working_days'][$weekday],
        'breaks' => $schedule['breaks'],
        'slot_interval' => $schedule['slot_interval'],
    ];
}

function slots_for_service(int $serviceId, string $date, ?int $ignoreAppointmentId = null, ?int $doctorId = null): array
{
    $service = service_by_id($serviceId);
    $rule = $doctorId ? doctor_booking_rule($serviceId, $doctorId) : service_booking_rule($serviceId);
    $schedule = day_schedule($date);

    if (!$service || !$schedule || !$rule || strtotime($date) < strtotime(date('Y-m-d'))) {
        return [];
    }

    $weekday = (int) date('N', strtotime($date));
    if (!in_array($weekday, $rule['weekdays'], true)) {
        return [];
    }

    $duration = (int) $service['duration'] + (int) $service['buffer'];
    $clinicStart = parse_time_string($date, $schedule['hours']['open']);
    $clinicEnd = parse_time_string($date, $schedule['hours']['close']);
    $doctorStart = parse_time_string($date, $rule['start']);
    $doctorEnd = parse_time_string($date, $rule['end']);
    $dayStart = $clinicStart > $doctorStart ? $clinicStart : $doctorStart;
    $dayEnd = $clinicEnd < $doctorEnd ? $clinicEnd : $doctorEnd;
    $interval = new DateInterval('PT' . (int) $schedule['slot_interval'] . 'M');

    $appointments = array_filter(appointments(['date' => $date]), function ($appointment) use ($ignoreAppointmentId, $doctorId) {
        if ($ignoreAppointmentId && (int) $appointment['id'] === $ignoreAppointmentId) {
            return false;
        }
        if ($doctorId && (int) ($appointment['staff_id'] ?? 0) !== $doctorId) {
            return false;
        }
        return !in_array($appointment['status'], ['Cancelled', 'Rejected'], true);
    });

    $available = [];
    for ($slotStart = $dayStart; $slotStart < $dayEnd; $slotStart = $slotStart->add($interval)) {
        $slotEnd = $slotStart->add(new DateInterval('PT' . $duration . 'M'));
        if ($slotEnd > $dayEnd) {
            continue;
        }

        $blockedByBreak = false;
        foreach ($schedule['breaks'] as $break) {
            $breakStart = parse_time_string($date, $break['start']);
            $breakEnd = parse_time_string($date, $break['end']);
            if ($slotStart < $breakEnd && $slotEnd > $breakStart) {
                $blockedByBreak = true;
                break;
            }
        }

        if ($blockedByBreak || $slotStart < new DateTimeImmutable()) {
            continue;
        }

        $slot = [
            'start' => $slotStart->format('H:i'),
            'end' => $slotEnd->format('H:i'),
            'label' => $slotStart->format('g:i A'),
            'available' => true,
        ];

        foreach ($appointments as $appointment) {
            if (overlaps($date, $slot['start'], $slot['end'], $appointment)) {
                $slot['available'] = false;
                break;
            }
        }

        $available[] = $slot;
    }

    if (!empty($service['daily_limit'])) {
        $bookingsForService = array_filter($appointments, fn ($appointment) => (int) $appointment['service_id'] === $serviceId);
        if (count($bookingsForService) >= (int) $service['daily_limit']) {
            foreach ($available as &$slot) {
                $slot['available'] = false;
            }
        }
    }

    return $available;
}

function validate_patient_input(array $input): array
{
    $errors = [];

    if (trim($input['full_name'] ?? '') === '') {
        $errors['full_name'] = 'Please enter the patient name.';
    }
    if (!filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^\d{1,11}$/', $input['contact_number'] ?? '')) {
        $errors['contact_number'] = 'Please enter a contact number using digits only, up to 11 digits.';
    }

    return $errors;
}

function validate_registration_input(array $input): array
{
    $errors = validate_patient_input($input);

    if (strlen((string) ($input['password'] ?? '')) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if (user_email_exists((string) ($input['email'] ?? ''))) {
        $errors['email'] = 'That email address is already registered.';
    }

    $username = trim((string) ($input['username'] ?? ''));
    if ($username === '') {
        $errors['username'] = 'Username is required.';
    } elseif (username_taken_by_other($username, 0)) {
        $errors['username'] = 'That username is already registered.';
    }

    return $errors;
}

function booking_session(): array
{
    return $_SESSION['booking'] ?? [];
}

function save_booking_session(array $booking): void
{
    $_SESSION['booking'] = $booking;
}

function clear_booking_session(): void
{
    unset($_SESSION['booking']);
}

function add_notification(string $title, string $message): void
{
    if (demo_mode()) {
        $notifications = demo_store('notifications');
        array_unshift($notifications, [
            'id' => next_id($notifications),
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        demo_save('notifications', array_slice($notifications, 0, 12));
        return;
    }

    ensure_notification_schema();
    $statement = db()->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (:user_id, :title, :message, 0, :created_at)');
    $statement->execute([
        'user_id' => current_user_id(),
        'title' => $title,
        'message' => $message,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

function register_patient_account(array $input): void
{
    $user = [
        'full_name' => trim($input['full_name']),
        'username' => trim((string) ($input['username'] ?? '')) ?: default_username_from_input(trim($input['full_name']), trim($input['email'])),
        'email' => trim($input['email']),
        'password' => password_hash($input['password'], PASSWORD_DEFAULT),
        'role' => user_role_storage_value('patient'),
        'contact_number' => trim($input['contact_number']),
    ];

    if (demo_mode()) {
        $users = demo_store('users');
        $user['id'] = next_id($users);
        $user['name'] = $user['full_name'];
        $users[] = $user;
        demo_save('users', $users);
    } else {
        ensure_profile_schema();
        $statement = db()->prepare('INSERT INTO users (full_name, username, email, password, role, contact_number) VALUES (:full_name, :username, :email, :password, :role, :contact_number)');
        $statement->execute($user);
    }

    add_notification('New patient registration', sprintf('%s created a patient account.', $input['full_name']));
}

function update_profile_record(int $userId, array $input): ?array
{
    $existing = profile_by_user_id($userId);
    if (!$existing) {
        return null;
    }

    $role = canonical_role((string) $existing['role']);
    $name = trim((string) ($input['name'] ?? ''));
    $username = trim((string) ($input['username'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $contactNumber = trim((string) ($input['contact_number'] ?? ''));

    if (demo_mode()) {
        $users = demo_store('users');
        foreach ($users as &$user) {
            if ((int) $user['id'] === $userId) {
                $user['name'] = $name;
                $user['full_name'] = $name;
                $user['email'] = $email;
                $user['username'] = $username;
                $user['contact_number'] = $contactNumber;
                break;
            }
        }
        demo_save('users', $users);

        return profile_by_user_id($userId);
    }

    ensure_profile_schema();
    $fields = ['full_name = :name', 'email = :email'];
    $params = ['id' => $userId, 'name' => $name, 'email' => $email];

    $fields[] = 'username = :username';
    $params['username'] = $username;

    $fields[] = 'contact_number = :contact_number';
    $params['contact_number'] = $contactNumber;

    $statement = db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
    $statement->execute($params);

    return profile_by_user_id($userId);
}

function update_user_password(int $userId, string $newPassword): void
{
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    if (demo_mode()) {
        $users = demo_store('users');
        foreach ($users as &$user) {
            if ((int) $user['id'] === $userId) {
                $user['password'] = $hashed;
                break;
            }
        }
        demo_save('users', $users);
        return;
    }

    $statement = db()->prepare('UPDATE users SET password = :password WHERE id = :id');
    $statement->execute([
        'password' => $hashed,
        'id' => $userId,
    ]);
}

function update_user_service_assignment(int $userId, ?int $serviceId): ?array
{
    if (demo_mode()) {
        $details = demo_store('staff_details');
        $updated = false;
        foreach ($details as &$detail) {
            if ((int) $detail['user_id'] === $userId) {
                $detail['assigned_service_id'] = $serviceId;
                $detail['service_role'] = $serviceId ? (service_by_id($serviceId)['name'] ?? null) : null;
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $details[] = [
                'id' => next_id($details),
                'user_id' => $userId,
                'assigned_service_id' => $serviceId,
                'service_role' => $serviceId ? (service_by_id($serviceId)['name'] ?? null) : null,
                'schedule' => null,
            ];
        }
        demo_save('staff_details', $details);
        return profile_by_user_id($userId);
    }

    ensure_profile_schema();
    $statement = db()->prepare('
        INSERT INTO staff_details (user_id, assigned_service_id)
        VALUES (:user_id, :assigned_service_id)
        ON DUPLICATE KEY UPDATE assigned_service_id = VALUES(assigned_service_id)
    ');
    $statement->execute([
        'user_id' => $userId,
        'assigned_service_id' => $serviceId,
    ]);

    return profile_by_user_id($userId);
}

function update_user_schedule(int $userId, string $schedule): ?array
{
    $rows = parse_schedule_entries($schedule);

    if (demo_mode()) {
        $details = demo_store('staff_details');
        foreach ($details as &$detail) {
            if ((int) $detail['user_id'] === $userId) {
                $detail['schedule'] = implode(', ', format_schedule_rows($rows));
                demo_save('staff_details', $details);
                return profile_by_user_id($userId);
            }
        }
        return null;
    }

    ensure_profile_schema();
    db()->prepare('DELETE FROM doctor_schedules WHERE user_id = :user_id')->execute(['user_id' => $userId]);

    $insert = db()->prepare('INSERT INTO doctor_schedules (user_id, day_of_week, start_time, end_time) VALUES (:user_id, :day_of_week, :start_time, :end_time)');
    foreach ($rows as $row) {
        $insert->execute([
            'user_id' => $userId,
            'day_of_week' => $row['day_of_week'],
            'start_time' => date('H:i:s', strtotime((string) $row['start_time'])),
            'end_time' => date('H:i:s', strtotime((string) $row['end_time'])),
        ]);
    }

    return profile_by_user_id($userId);
}

function create_appointment(array $booking, array $patientInput): array
{
    $patientId = upsert_patient([
        'full_name' => trim($patientInput['full_name']),
        'email' => trim($patientInput['email']),
        'contact_number' => trim($patientInput['contact_number']),
    ]);

    $service = service_by_id((int) $booking['service_id']);
    $doctorId = (int) ($booking['doctor_id'] ?? 0);
    $rule = $doctorId ? doctor_booking_rule((int) $booking['service_id'], $doctorId) : service_booking_rule((int) $booking['service_id']);
    $slots = slots_for_service((int) $booking['service_id'], $booking['scheduled_date'], null, $doctorId ?: null);
    $selectedSlot = null;

    foreach ($slots as $slot) {
        if ($slot['start'] === $booking['start_time'] && $slot['available']) {
            $selectedSlot = $slot;
            break;
        }
    }

    if (!$service || !$selectedSlot) {
        throw new RuntimeException('The selected time slot is no longer available.');
    }

    $appointment = [
        'user_id' => $patientId,
        'service_id' => (int) $booking['service_id'],
        'staff_id' => (int) ($rule['doctor_id'] ?? $doctorId) ?: null,
        'scheduled_date' => $booking['scheduled_date'],
        'start_time' => $selectedSlot['start'],
        'end_time' => $selectedSlot['end'],
        'status' => 'Pending',
        'patient_name' => trim($patientInput['full_name']),
        'patient_email' => trim($patientInput['email']),
        'patient_contact' => trim($patientInput['contact_number']),
        'service_name' => $service['name'],
        'service_fee' => (float) $service['price'],
        'total_amount' => (float) $service['price'],
        'notes' => trim($patientInput['notes'] ?? ''),
        'reference_code' => generate_reference(),
        'created_at' => date('Y-m-d H:i:s'),
    ];

    if (demo_mode()) {
        $appointments = demo_store('appointments');
        $appointment['id'] = next_id($appointments);
        $appointments[] = $appointment;
        demo_save('appointments', $appointments);
    } else {
        $statement = db()->prepare(
            'INSERT INTO appointments (
                reference_code, user_id, service_id, staff_id, scheduled_date, start_time, end_time, status,
                patient_name, patient_email, patient_contact, service_name, service_fee, total_amount, notes, created_at
             ) VALUES (
                :reference_code, :user_id, :service_id, :staff_id, :scheduled_date, :start_time, :end_time, :status,
                :patient_name, :patient_email, :patient_contact, :service_name, :service_fee, :total_amount, :notes, :created_at
             )'
        );
        $statement->execute($appointment);
        $appointment['id'] = (int) db()->lastInsertId();
    }

    add_notification(
        'New appointment booked',
        sprintf('%s booked %s on %s at %s.', $patientInput['full_name'], $service['name'], date('M d, Y', strtotime($booking['scheduled_date'])), date('g:i A', strtotime($booking['start_time'])))
    );

    return appointment_with_relations($appointment);
}

function appointment_by_reference(string $reference, string $contactNumber = ''): ?array
{
    foreach (appointments() as $appointment) {
        if ($appointment['reference_code'] === trim($reference)) {
            if ($contactNumber === '' || $appointment['patient']['contact_number'] === trim($contactNumber)) {
                return $appointment;
            }
        }
    }

    return null;
}

function appointment_change_allowed(array $appointment): bool
{
    $allowedStatuses = ['Pending', 'Accepted', 'Approved'];
    $cutoffHours = (int) app_config('app.reschedule_cutoff_hours', 12);
    $appointmentStart = strtotime($appointment['scheduled_date'] . ' ' . $appointment['start_time']);

    return in_array($appointment['status'], $allowedStatuses, true)
        && $appointmentStart > strtotime('+' . $cutoffHours . ' hours');
}

function appointment_patient_reschedule_allowed(array $appointment): bool
{
    $cutoffHours = (int) app_config('app.reschedule_cutoff_hours', 12);
    $appointmentStart = strtotime($appointment['scheduled_date'] . ' ' . $appointment['start_time']);

    return in_array($appointment['status'], ['Pending', 'Accepted'], true)
        && $appointmentStart > strtotime('+' . $cutoffHours . ' hours');
}

function update_appointment(int $appointmentId, array $attributes): ?array
{
    if (demo_mode()) {
        $appointments = demo_store('appointments');
        foreach ($appointments as &$appointment) {
            if ((int) $appointment['id'] === $appointmentId) {
                $appointment = array_merge($appointment, $attributes);
                demo_save('appointments', $appointments);
                return appointment_with_relations($appointment);
            }
        }
        return null;
    }

    $fields = [];
    foreach ($attributes as $column => $value) {
        $fields[] = $column . ' = :' . $column;
    }
    $attributes['id'] = $appointmentId;
    $statement = db()->prepare('UPDATE appointments SET ' . implode(', ', $fields) . ' WHERE id = :id');
    $statement->execute($attributes);

    $fetch = db()->prepare('SELECT * FROM appointments WHERE id = :id LIMIT 1');
    $fetch->execute(['id' => $appointmentId]);
    $appointment = $fetch->fetch();

    return $appointment ? appointment_with_relations($appointment) : null;
}

function dashboard_stats(): array
{
    $appointments = appointments();
    $today = date('Y-m-d');
    $currentMonth = date('Y-m');
    $monthlySales = array_reduce($appointments, function (float $total, array $appointment) use ($currentMonth): float {
        if ($appointment['status'] !== 'Completed' || date('Y-m', strtotime($appointment['scheduled_date'])) !== $currentMonth) {
            return $total;
        }

        // Sales are clinic-wide: sum completed appointment revenue for all doctors this month.
        return $total + (float) ($appointment['service_fee'] ?? $appointment['service']['price'] ?? 0);
    }, 0.0);

    return [
        'today' => count(array_filter($appointments, fn ($appointment) => $appointment['scheduled_date'] === $today)),
        'pending' => count(array_filter($appointments, fn ($appointment) => $appointment['status'] === 'Pending')),
        'completed' => count(array_filter($appointments, fn ($appointment) => $appointment['status'] === 'Completed')),
        'cancelled' => count(array_filter($appointments, fn ($appointment) => in_array($appointment['status'], ['Cancelled', 'Rejected'], true))),
        'monthly_sales' => $monthlySales,
    ];
}

function report_data(): array
{
    $appointments = appointments();
    $statusCounts = [];
    $serviceCounts = [];
    $dailyCounts = [];

    foreach ($appointments as $appointment) {
        $statusCounts[$appointment['status']] = ($statusCounts[$appointment['status']] ?? 0) + 1;
        $serviceCounts[$appointment['service']['name'] ?? 'Unknown'] = ($serviceCounts[$appointment['service']['name'] ?? 'Unknown'] ?? 0) + 1;
        $dailyCounts[$appointment['scheduled_date']] = ($dailyCounts[$appointment['scheduled_date']] ?? 0) + 1;
    }

    ksort($dailyCounts);

    return [
        'status_counts' => $statusCounts,
        'service_counts' => $serviceCounts,
        'daily_counts' => $dailyCounts,
    ];
}

function monthly_appointments_data(): array
{
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $counts = array_fill(1, 12, 0);

    if (demo_mode()) {
        foreach (appointments() as $appointment) {
            $monthNumber = (int) date('n', strtotime($appointment['scheduled_date']));
            $counts[$monthNumber]++;
        }
    } else {
        $statement = db()->query('SELECT MONTH(scheduled_date) AS month, COUNT(*) AS count FROM appointments GROUP BY MONTH(scheduled_date) ORDER BY month');
        foreach ($statement->fetchAll() as $row) {
            $counts[(int) $row['month']] = (int) $row['count'];
        }
    }

    $data = [];
    foreach ($months as $index => $monthLabel) {
        $data[] = [
            'month' => $monthLabel,
            'count' => $counts[$index + 1] ?? 0,
        ];
    }

    return $data;
}

function password_matches_stored(string $inputPassword, string $storedPassword): bool
{
    $storedPassword = trim($storedPassword);

    if ($storedPassword === '') {
        return false;
    }

    $passwordInfo = password_get_info($storedPassword);
    if (($passwordInfo['algo'] ?? 0) !== 0 && password_verify($inputPassword, $storedPassword)) {
        return true;
    }

    if (preg_match('/^[a-f0-9]{64}$/i', $storedPassword)) {
        return hash_equals(strtolower($storedPassword), hash('sha256', $inputPassword));
    }

    return hash_equals($storedPassword, $inputPassword);
}

function attempt_login(string $email, string $password): bool
{
    foreach (demo_mode() ? demo_store('users') : users() as $user) {
        $match = strtolower($user['email']) === strtolower($email);
        $valid = demo_mode() ? password_matches_stored($password, (string) $user['password']) : false;

        if ($match && $valid) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => canonical_role((string) $user['role']),
                'username' => $user['username'] ?? null,
            ];
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = canonical_role((string) $user['role']);
            return true;
        }
    }

    if (!demo_mode()) {
        ensure_profile_schema();
        $statement = db()->prepare('SELECT id, full_name AS name, username, email, password, role FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        if ($user && password_matches_stored($password, (string) $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => canonical_role((string) $user['role']),
                'username' => $user['username'] ?? null,
            ];
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = canonical_role((string) $user['role']);
            return true;
        }
    }

    return false;
}

function current_user(): ?array
{
    $sessionUser = $_SESSION['user'] ?? null;
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : (isset($sessionUser['id']) ? (int) $sessionUser['id'] : 0);

    if ($userId <= 0) {
        return null;
    }

    $profile = profile_by_user_id($userId);
    if (!$profile) {
        return $sessionUser;
    }

    $_SESSION['user'] = array_merge($sessionUser ?? [], [
        'id' => (int) $profile['id'],
        'name' => $profile['name'],
        'email' => $profile['email'],
        'role' => canonical_role((string) $profile['role']),
        'username' => $profile['username'] ?? null,
    ]);

    return $_SESSION['user'];
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : ((current_user()['id'] ?? null) ? (int) current_user()['id'] : null);
}

function require_login(array $roles = []): void
{
    $user = current_user();
    if (!$user) {
        header('Location: ' . route_url('?login_required=1'));
        exit;
    }
    if ($roles) {
        $allowed = false;
        foreach ($roles as $role) {
            if (role_matches((string) $user['role'], (string) $role)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            http_response_code(403);
            exit('Access denied.');
        }
    }
}

function logout(): void
{
    unset($_SESSION['user']);
    unset($_SESSION['user_id'], $_SESSION['role']);
}

function render_header(string $title, array $options = []): void
{
    $isAdmin = $options['admin'] ?? false;
    $bodyClass = $options['body_class'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= h($title) ?> | <?= h(app_config('app.name')) ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
        <link rel="stylesheet" href="<?= h(asset_url('css/style.css')) ?>">
    </head>
    <body class="<?= h($bodyClass) ?><?= $isAdmin ? ' admin-body' : '' ?>">
    <?php
}

function render_footer(): void
{
    ?>
        <div class="global-bottom-footer">
            <p>&copy; 2026 PrimeCare Dental Hub. All rights reserved.</p>
        </div>
        <script src="<?= h(asset_url('js/app.js')) ?>"></script>
    </body>
    </html>
    <?php
}

function render_public_shell_start(string $title, string $subtitle = ''): void
{
    render_header($title, ['body_class' => 'public-body']);
    ?>
    <div class="booking-shell">
        <header class="booking-hero">
            <div class="logo-mark">P</div>
            <div>
                <p class="eyebrow">PrimeCare Dental Clinic</p>
                <h1><?= h($title) ?></h1>
                <p class="hero-copy"><?= h($subtitle ?: app_config('app.tagline')) ?></p>
            </div>
            <a class="ghost-link" href="<?= h(route_url('manage.php')) ?>">Manage appointment</a>
        </header>
    <?php
}

function render_public_shell_end(): void
{
    echo '</div>';
    render_footer();
}

function render_booking_modal(string $returnPath): void
{
    $open = isset($_GET['modal']) && $_GET['modal'] === 'booking';
    $success = flash('booking_success');
    $error = flash('booking_error');
    $old = flash('booking_old') ?? [];
    $user = current_user();
    $patient = $user ? patient_by_email($user['email']) : null;
    $defaultPatient = [
        'full_name' => $old['full_name'] ?? ($patient['full_name'] ?? ($user['name'] ?? '')),
        'email' => $old['email'] ?? ($patient['email'] ?? ($user['email'] ?? '')),
        'contact_number' => $old['contact_number'] ?? ($patient['contact_number'] ?? ''),
        'notes' => $old['notes'] ?? '',
    ];
    $selectedServiceId = (int) ($old['service_id'] ?? 0);
    $selectedDoctorId = (int) ($old['doctor_id'] ?? 0);
    $selectedDate = $old['scheduled_date'] ?? '';
    $selectedTime = $old['start_time'] ?? '';
    $selectedService = $selectedServiceId ? service_by_id($selectedServiceId) : null;
    $selectedDoctor = $selectedDoctorId ? profile_by_user_id($selectedDoctorId) : null;
    $selectedRule = ($selectedServiceId && $selectedDoctorId) ? doctor_booking_rule($selectedServiceId, $selectedDoctorId) : null;
    $selectedSlots = ($selectedServiceId && $selectedDoctorId && $selectedDate) ? slots_for_service($selectedServiceId, $selectedDate, null, $selectedDoctorId) : [];
    $initialStep = 1;
    if ($selectedServiceId || $selectedDate || $selectedTime) {
        $initialStep = 2;
    }
    if (!empty($defaultPatient['full_name']) && !empty($defaultPatient['email']) && !empty($defaultPatient['contact_number']) && $selectedTime) {
        $initialStep = 3;
    }
    if (!empty($defaultPatient['full_name']) && !empty($defaultPatient['email']) && !empty($defaultPatient['contact_number']) && $selectedTime && $selectedDate) {
        $initialStep = 4;
    }
    ?>
    <div class="auth-modal booking-modal <?= ($open || $success || $error) ? 'is-open' : '' ?>" id="bookingModal" aria-hidden="<?= ($open || $success || $error) ? 'false' : 'true' ?>">
        <div class="auth-backdrop" data-booking-close></div>
        <div class="auth-dialog card booking-dialog">
            <button class="auth-close" type="button" data-booking-close aria-label="Close booking modal">&times;</button>
            <?php if ($success): ?>
                <p class="eyebrow">Appointment booked</p>
                <h2 class="section-title">Booking successful</h2>
                <p class="section-copy">Your appointment request has been submitted successfully.</p>
                <div class="panel">
                    <div class="summary-item"><strong>Service:</strong> <?= h($success['service']['name']) ?></div>
                    <div class="summary-item"><strong>Doctor:</strong> <?= h(service_booking_rule((int) $success['service_id'])['doctor_name'] ?? 'Assigned doctor') ?></div>
                    <div class="summary-item"><strong>Date:</strong> <?= h(date('F d, Y', strtotime($success['scheduled_date']))) ?></div>
                    <div class="summary-item"><strong>Time:</strong> <?= h(date('g:i A', strtotime($success['start_time']))) ?></div>
                    <div class="summary-item"><strong>Reference:</strong> <?= h($success['reference_code']) ?></div>
                </div>
                <div class="step-actions">
                    <button class="button" type="button" data-booking-close>Close</button>
                </div>
            <?php else: ?>
                <p class="eyebrow">Book appointment</p>
                <h2 class="section-title">PrimeCare dental booking</h2>
                <p class="section-copy">Choose a service, see the assigned doctor automatically, and book only within valid availability.</p>
                <?php if ($error): ?><div class="flash"><span class="error-text"><?= h($error) ?></span></div><?php endif; ?>
                <div class="progress-steps booking-modal-progress">
                    <?php foreach ([1 => 'Service', 2 => 'Doctor, Date & Time', 3 => 'Details', 4 => 'Confirm'] as $number => $label): ?>
                        <div class="progress-step <?= $number === 1 && !$selectedServiceId ? 'active' : '' ?>" data-booking-progress="<?= h((string) $number) ?>">
                            <strong><?= h(sprintf('%02d', $number)) ?></strong>
                            <div><?= h($label) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="post" action="<?= h(route_url('booking_submit.php')) ?>" class="booking-modal-form" data-booking-form data-endpoint="<?= h(route_url('Ajax/availability.php')) ?>" data-initial-step="<?= h((string) $initialStep) ?>">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="return_to" value="<?= h($returnPath) ?>">
                    <input type="hidden" name="service_id" value="<?= h((string) $selectedServiceId) ?>" data-booking-service-input>
                    <input type="hidden" name="doctor_id" value="<?= h((string) $selectedDoctorId) ?>" data-booking-doctor-input>
                    <input type="hidden" name="service_price" value="<?= h((string) ($selectedService['price'] ?? '')) ?>" data-booking-price-input>
                    <input type="hidden" name="scheduled_date" value="<?= h($selectedDate) ?>" data-booking-date-hidden>
                    <input type="hidden" name="start_time" value="<?= h($selectedTime) ?>" data-booking-time-hidden>

                    <section class="booking-step-panel is-active" data-booking-step="1">
                        <h3>Select Dental Service</h3>
                        <div class="service-grid">
                            <?php foreach (services() as $service): ?>
                                <button type="button" class="service-card booking-service-card <?= $selectedServiceId === (int) $service['id'] ? 'is-selected' : '' ?>" data-booking-service
                                    data-service-id="<?= h((string) $service['id']) ?>"
                                    data-service-name="<?= h($service['name']) ?>"
                                    data-service-price="<?= h((string) $service['price']) ?>">
                                    <div class="service-meta">
                                        <span class="pill"><?= h($service['duration']) ?> mins</span>
                                        <span class="pill">PHP <?= h(number_format((float) $service['price'], 2)) ?></span>
                                    </div>
                                    <h4><?= h($service['name']) ?></h4>
                                    <p class="muted"><?= h($service['description']) ?></p>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="booking-step-panel" data-booking-step="2">
                        <h3>Doctor, Date and Time</h3>
                        <div class="panel booking-doctor-panel">
                            <label class="field full">
                                <span>Assigned Doctor</span>
                                <select name="doctor_pick" data-booking-doctor-select>
                                    <option value="">Choose a service first</option>
                                    <?php if ($selectedServiceId): ?>
                                        <?php foreach (doctors_for_service($selectedServiceId) as $doctor): ?>
                                            <option value="<?= h((string) $doctor['id']) ?>" data-doctor-name="<?= h($doctor['name']) ?>" data-schedule-text="<?= h($doctor['schedule_text']) ?>" <?= $selectedDoctorId === (int) $doctor['id'] ? 'selected' : '' ?>><?= h($doctor['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </label>
                            <div class="summary-item"><strong>Doctor:</strong> <span data-booking-doctor><?= h($selectedDoctor['name'] ?? 'Choose a doctor') ?></span></div>
                            <div class="summary-item"><strong>Schedule:</strong> <span data-booking-schedule><?= isset($selectedRule['schedule_text']) ? render_schedule_display((string) $selectedRule['schedule_text']) : h('Doctor availability will appear here.') ?></span></div>
                        </div>
                        <div class="booking-calendar">
                            <div class="calendar-panel">
                                <input type="date" value="<?= h($selectedDate) ?>" min="<?= h(date('Y-m-d')) ?>" data-booking-date>
                                <p class="muted" data-booking-date-help>Select only dates that fall within the assigned doctor schedule.</p>
                                <p class="muted booking-slot-count" data-booking-slot-count>
                                    <?= $selectedDate ? h((string) count(array_filter($selectedSlots, fn ($slot) => $slot['available']))) . ' slots available on this day.' : 'Choose a date to see available slots.' ?>
                                </p>
                            </div>
                            <div class="calendar-panel">
                                <label class="field full booking-time-field">
                                    <span>Available Time Slot</span>
                                    <select name="booking_time_pick" data-booking-time-select>
                                        <option value="">Select a time slot</option>
                                        <?php foreach ($selectedSlots as $slot): ?>
                                            <?php if ($slot['available']): ?>
                                                <option value="<?= h($slot['start']) ?>" <?= $selectedTime === $slot['start'] ? 'selected' : '' ?>><?= h($slot['label']) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <p class="muted" data-booking-slots-message><?= empty($selectedSlots) ? 'Choose a valid service, doctor, and date to load available slots.' : 'Select an available slot from the dropdown.' ?></p>
                            </div>
                        </div>
                    </section>

                    <section class="booking-step-panel" data-booking-step="3">
                        <h3>Patient Details</h3>
                        <div class="form-grid">
                            <label class="field">
                                <span>Full Name</span>
                                <input type="text" name="full_name" value="<?= h($defaultPatient['full_name']) ?>" required>
                            </label>
                            <label class="field">
                                <span>Contact Number</span>
                                <input type="text" name="contact_number" value="<?= h($defaultPatient['contact_number']) ?>" maxlength="11" inputmode="numeric" pattern="\d{1,11}" required>
                            </label>
                            <label class="field full">
                                <span>Email</span>
                                <input type="email" name="email" value="<?= h($defaultPatient['email']) ?>" required>
                            </label>
                            <label class="field full">
                                <span>Optional notes</span>
                                <textarea name="notes"><?= h($defaultPatient['notes']) ?></textarea>
                            </label>
                        </div>
                    </section>

                    <section class="booking-step-panel" data-booking-step="4">
                        <h3>Confirm Appointment</h3>
                        <div class="panel">
                            <div class="summary-item"><strong>Service:</strong> <span data-booking-summary-service><?= h($selectedService['name'] ?? 'Select a service') ?></span></div>
                            <div class="summary-item"><strong>Price:</strong> <span data-booking-summary-price><?= $selectedService ? 'PHP ' . h(number_format((float) $selectedService['price'], 2)) : 'Select a service' ?></span></div>
                            <div class="summary-item"><strong>Doctor:</strong> <span data-booking-summary-doctor><?= h($selectedDoctor['name'] ?? 'Choose a doctor') ?></span></div>
                            <div class="summary-item"><strong>Date:</strong> <span data-booking-summary-date><?= h($selectedDate ? date('F d, Y', strtotime($selectedDate)) : 'Choose a date') ?></span></div>
                            <div class="summary-item"><strong>Time:</strong> <span data-booking-summary-time><?= h($selectedTime ? date('g:i A', strtotime($selectedTime)) : 'Choose a time slot') ?></span></div>
                            <div class="summary-item"><strong>Patient:</strong> <span data-booking-summary-patient><?= h($defaultPatient['full_name'] ?: 'Enter patient details') ?></span></div>
                            <div class="summary-item"><strong>Email:</strong> <span data-booking-summary-email><?= h($defaultPatient['email'] ?: 'Enter patient email') ?></span></div>
                            <div class="summary-item"><strong>Contact:</strong> <span data-booking-summary-contact><?= h($defaultPatient['contact_number'] ?: 'Enter contact number') ?></span></div>
                        </div>
                    </section>

                    <div class="step-actions booking-modal-actions">
                        <button class="button-ghost" type="button" data-booking-back>Back</button>
                        <button class="button" type="button" data-booking-next>Next</button>
                        <button class="button" type="submit" data-booking-submit hidden>Confirm Appointment</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function render_admin_shell_start(string $title, string $active = 'dashboard'): void
{
    $user = current_user();
    $profile = profile_by_user_id((int) ($user['id'] ?? 0)) ?? $user;
    $profileName = $profile['name'] ?? $user['name'];
    $profileUsername = $profile['username'] ?? '';
    $profileEmail = $profile['email'] ?? $user['email'];
    $profileContact = $profile['contact_number'] ?? '';
    $profileRoleLabel = $profile['role_label'] ?? user_role_label((string) $user['role']);
    $profileRole = canonical_role((string) ($profile['role'] ?? $user['role']));
    $profileServiceId = (string) ($profile['service_id'] ?? '');
    $profileServiceRole = $profile['service_role'] ?? '';
    $profileScheduleInput = (string) ($profile['schedule'] ?? '');
    $profileSchedule = schedule_display_value($profile['schedule'] ?? null);
    $doctorProfileReadOnly = $profileRole === 'doctor';
    $topbarNotifications = notifications();
    $unreadNotificationCount = count(array_filter($topbarNotifications, fn ($notification) => !$notification['is_read']));
    render_header($title, ['admin' => true]);
    $links = match (canonical_role((string) $user['role'])) {
        'doctor' => [
            'dashboard' => ['label' => 'Dashboard', 'href' => route_url('Dentist/index.php')],
            'schedule' => ['label' => 'Appointment Request', 'href' => route_url('Dentist/index.php#schedule')],
            'records' => ['label' => 'Patient Records', 'href' => route_url('Dentist/index.php#records')],
        ],
        'secretary' => [
            // Secretary sidebar is intentionally limited to operational appointment views only.
            'dashboard' => ['label' => 'Dashboard', 'href' => route_url('Secretary/index.php')],
            // Appointments opens the shared booking modal instead of routing away.
            'appointments' => ['label' => 'Schedule Appointments
', 'href' => '#', 'attrs' => ['data-booking-open' => 'true']],
            'history' => ['label' => 'History', 'href' => route_url('Secretary/history.php')],
        ],
        'patient' => [
            'dashboard' => ['label' => 'Dashboard', 'href' => route_url('Patient/index.php')],
            'book' => ['label' => 'Book Appointment', 'href' => '#', 'attrs' => ['data-booking-open' => 'true']],
            'history' => ['label' => 'Appointment History', 'href' => route_url('Patient/history.php')],
        ],
        default => [
            'dashboard' => ['label' => 'Dashboard', 'href' => route_url('Admin/index.php')],
            'patients' => ['label' => 'Patients', 'href' => route_url('Admin/index.php?page=patients')],
            'users' => ['label' => 'Users', 'href' => route_url('Admin/index.php?page=users')],
            'reports' => ['label' => 'Reports', 'href' => route_url('Admin/index.php?page=reports')],
        ],
    };
    ?>
    <div class="admin-shell">
        <aside class="sidebar" id="appSidebar">
            <div class="brand-block">
                <div class="logo-mark small">P</div>
                <div>
                    <p class="eyebrow">PrimeCare</p>
                    <strong>Clinic System</strong>
                </div>
            </div>
            <nav class="sidebar-nav">
                <?php foreach ($links as $key => $link): ?>
                    <?php
                    $attributes = '';
                    foreach (($link['attrs'] ?? []) as $attribute => $value) {
                        $attributes .= ' ' . $attribute . ($value !== '' ? '="' . h((string) $value) . '"' : '');
                    }
                    ?>
                    <a class="<?= $key === $active ? 'active' : '' ?>" href="<?= h($link['href']) ?>"<?= $attributes ?>><?= h($link['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <p class="muted-label">Logged in as</p>
                <strong data-sidebar-user-name><?= h($user['name']) ?></strong>
                <span><?= h(ucfirst($user['role'])) ?></span>
            </div>
        </aside>
        <main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-title-wrap">
                    <button class="icon-button mobile-toggle" type="button" data-sidebar-toggle aria-label="Toggle sidebar">&#9776;</button>
                    <div>
              
                    <h1><?= h($title) ?></h1>
                    </div>
                </div>
                <div class="topbar-actions">
                    <div class="notification-menu-wrap" data-notification-menu data-notification-endpoint="<?= h(route_url('Ajax/notifications.php')) ?>" data-notification-csrf="<?= h(csrf_token()) ?>">
                        <button class="icon-button notification-toggle" type="button" aria-label="Notifications" data-notification-toggle aria-expanded="false">
                            <span aria-hidden="true">&#128276;</span>
                            <span class="notification-badge" data-notification-badge <?= $unreadNotificationCount > 0 ? '' : 'hidden' ?>><?= h((string) $unreadNotificationCount) ?></span>
                        </button>
                        <div class="notification-dropdown" data-notification-dropdown hidden>
                            <div class="notification-dropdown-header">
                                <strong>Notifications</strong>
                                <button type="button" data-notification-mark-all>Mark all as read</button>
                            </div>
                            <div class="notification-list" data-notification-list>
                                <?php if (empty($topbarNotifications)): ?>
                                    <p class="notification-empty">No notifications yet.</p>
                                <?php endif; ?>
                                <?php foreach ($topbarNotifications as $notification): ?>
                                    <button class="notification-item <?= $notification['is_read'] ? '' : 'is-unread' ?>" type="button" data-notification-item data-notification-id="<?= h((string) $notification['id']) ?>" data-created-at="<?= h($notification['created_at']) ?>">
                                        <span class="notification-unread-dot" aria-hidden="true"></span>
                                        <span>
                                            <strong><?= h($notification['title']) ?></strong>
                                            <span><?= h($notification['message']) ?></span>
                                            <small data-notification-time><?= h($notification['created_at']) ?></small>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="profile-menu-wrap" data-profile-menu>
                        <button class="ghost-link" type="button" data-profile-toggle aria-expanded="false">
                            <?= h($user['name']) ?>
                        </button>
                        <div class="profile-menu-dropdown" data-profile-dropdown hidden>
                            <button type="button" data-modal-open="profileModal">View Profile</button>
                            <a href="<?= h(route_url('Auth/login.php?logout=1')) ?>">Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            <div class="auth-modal" id="profileModal" aria-hidden="true" data-profile-modal data-profile-role="<?= h($profileRole) ?>">
                <div class="auth-backdrop" data-modal-close></div>
                <div class="auth-dialog card user-detail-dialog">
                    <button class="auth-close" type="button" data-modal-close aria-label="Close profile details">&times;</button>
                    <div class="profile-modal-heading">
                        <div>
                            <p class="eyebrow">View profile</p>
                            <h2 class="section-title" data-profile-modal-title><?= h($profileName) ?></h2>
                        </div>
                        <button class="icon-button" type="button" data-profile-edit aria-label="Edit profile">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="user-detail-grid" data-profile-view>
                        <div class="user-detail-item"><span>Name</span><strong data-profile-value="name"><?= h($profileName) ?></strong></div>
                        <div class="user-detail-item"><span>Email</span><strong data-profile-value="email"><?= h($profileEmail) ?></strong></div>
                        <div class="user-detail-item"><span>Role</span><strong data-profile-value="role_label"><?= h($profileRoleLabel) ?></strong></div>
                        <div class="user-detail-item"><span>Username</span><strong data-profile-value="username"><?= h($profileUsername ?: '-') ?></strong></div>
                        <?php if ($profileRole !== 'admin'): ?>
                            <div class="user-detail-item"><span>Contact</span><strong data-profile-value="contact_number"><?= h($profileContact ?: '-') ?></strong></div>
                        <?php endif; ?>
                        <?php if ($profileRole === 'doctor'): ?>
                            <div class="user-detail-item"><span>Service</span><strong data-profile-value="service_role"><?= h($profileServiceRole ?: '-') ?></strong></div>
                        <?php endif; ?>
                        <?php if (in_array($profileRole, ['doctor', 'secretary'], true)): ?>
                            <div class="user-detail-item full"><span>Office Schedule</span><strong data-profile-value="schedule_display"><?= render_schedule_display($profile['schedule'] ?? null) ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <form class="form-grid profile-modal-form" data-profile-modal-form data-endpoint="<?= h(route_url('update_profile.php')) ?>" hidden>
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="service_id" value="<?= h($profileServiceId) ?>">
                        <label class="field">
                            <span>Name</span>
                            <input type="text" name="name" value="<?= h($profileName) ?>" <?= $doctorProfileReadOnly ? 'readonly' : '' ?> required>
                        </label>
                        <label class="field">
                            <span>Username</span>
                            <input type="text" name="username" value="<?= h($profileUsername) ?>" <?= $doctorProfileReadOnly ? 'readonly' : '' ?> required>
                        </label>
                        <label class="field">
                            <span>Email</span>
                            <input type="email" name="email" value="<?= h($profileEmail) ?>" <?= $doctorProfileReadOnly ? 'readonly' : '' ?> required>
                        </label>
                        <?php if ($profileRole !== 'admin'): ?>
                            <label class="field">
                                <span>Contact Number</span>
                                <input type="text" name="contact_number" value="<?= h($profileContact) ?>" maxlength="11" inputmode="numeric" pattern="\d{1,11}" <?= $doctorProfileReadOnly ? 'readonly' : '' ?> required>
                            </label>
                        <?php endif; ?>
                        <div class="field <?= $profileRole === 'admin' ? '' : 'full' ?>">
                            <span>Role</span>
                            <input type="text" name="role_label" value="<?= h($profileRoleLabel) ?>" readonly>
                        </div>
                        <?php if ($profileRole === 'doctor'): ?>
                            <div class="field full">
                                <span>Service</span>
                                <input type="text" value="<?= h($profileServiceRole ?: '-') ?>" readonly>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array($profileRole, ['doctor', 'secretary'], true)): ?>
                            <label class="field full">
                                <span>Office Schedule</span>
                                <textarea name="schedule" rows="3" placeholder="Monday 9:00 AM - 5:00 PM&#10;Tuesday 9:00 AM - 5:00 PM"><?= h($profileSchedule) ?></textarea>
                            </label>
                        <?php endif; ?>
                        <label class="field">
                            <span>New Password</span>
                            <div class="password-field-wrap">
                                <input type="password" name="new_password" data-password-input autocomplete="new-password">
                                <button class="password-toggle auth-link-reset" type="button" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                            </div>
                        </label>
                        <label class="field">
                            <span>Confirm Password</span>
                            <div class="password-field-wrap">
                                <input type="password" name="confirm_password" data-password-input autocomplete="new-password">
                                <button class="password-toggle auth-link-reset" type="button" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                            </div>
                        </label>
                        <div class="field full profile-modal-actions">
                            <button class="button" type="submit">Save</button>
                            <button class="button-secondary" type="button" data-profile-cancel>Cancel</button>
                        </div>
                    </form>
                    <div class="flash profile-modal-alert" data-profile-modal-alert hidden></div>
                </div>
            </div>
    <?php
}

function render_admin_shell_end(): void
{
    $role = canonical_role((string) (current_user()['role'] ?? ''));
    if (in_array($role, ['admin', 'secretary'], true)) {
        $returnPath = $_SERVER['REQUEST_URI'] ?? route_url();
        render_booking_modal($returnPath);
    }
    echo '</main></div>';
    render_footer();
}
