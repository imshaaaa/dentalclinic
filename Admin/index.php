<?php

require_once __DIR__ . '/../Config/bootstrap.php';
require_login(['admin']);

verify_csrf();

$page = $_GET['page'] ?? 'dashboard';
$userFormOld = flash('admin_user_old') ?? [];

$stats = dashboard_stats();
$appointments = appointments([
    'status' => $_GET['status'] ?? '',
    'service_id' => $_GET['service_id'] ?? '',
    'date' => $_GET['date'] ?? '',
]);
$report = report_data();

render_admin_shell_start(ucfirst($page), $page);
if ($message = flash('admin_message')): ?>
    <div class="flash"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error = flash('admin_error')): ?>
    <div class="flash"><span class="error-text"><?= h($error) ?></span></div>
<?php endif; ?>

<?php if ($page === 'dashboard'): ?>
    <div class="stats-grid">
        <div class="stats-card"><span>Today's appointments</span><strong><?= h((string) $stats['today']) ?></strong></div>
        <div class="stats-card"><span>Pending approvals</span><strong><?= h((string) $stats['pending']) ?></strong></div>
        <div class="stats-card"><span>Completed visits</span><strong><?= h((string) $stats['completed']) ?></strong></div>
        <div class="stats-card">
            <span>Monthly Sales (All Doctors)</span>
            <strong>PHP <?= h(number_format((float) $stats['monthly_sales'], 2)) ?></strong>
           
        </div>
    </div>
    <section class="table-card admin-chart-card">
        <div class="admin-chart-header">
            <div>
                <h3>Monthly appointments</h3>
            
            </div>
        </div>
        <div class="admin-chart-loading" data-monthly-chart-loading>Loading monthly appointment data...</div>
        <div class="admin-chart-error error-text" data-monthly-chart-error hidden>Unable to load monthly appointment data.</div>
        <canvas id="monthlyAppointmentsChart" height="96" data-chart-endpoint="<?= h(route_url('get_monthly_appointments.php')) ?>"></canvas>
    </section>
    <div class="dashboard-grid">
        <section class="table-card">
            <h3>Upcoming appointments</h3>
            <table>
                <thead><tr><th>Patient</th><th>Service</th><th>Schedule</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($appointments, 0, 6) as $appointment): ?>
                    <tr>
                        <td><?= h($appointment['patient']['full_name']) ?></td>
                        <td><?= h($appointment['service']['name']) ?></td>
                        <td><?= h(date('M d, Y', strtotime($appointment['scheduled_date']))) ?>, <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></td>
                        <td><span class="badge <?= h(status_badge_class($appointment['status'])) ?>"><?= h($appointment['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <section>
            <?php foreach (notifications() as $notification): ?>
                <div class="timeline-item">
                    <strong><?= h($notification['title']) ?></strong>
                    <p><?= h($notification['message']) ?></p>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
<?php elseif ($page === 'appointments'): ?>
    <section class="table-card">
        <div class="filters-bar">
            <form method="get" class="filters-bar" data-auto-filter-form>
                <input type="hidden" name="page" value="appointments">
                <input type="date" name="date" value="<?= h($_GET['date'] ?? '') ?>" aria-label="Filter by date">
                <select name="service_id" aria-label="Filter by service">
                    <option value="">All services</option>
                    <?php foreach (services() as $service): ?>
                        <option value="<?= h((string) $service['id']) ?>" <?= (($_GET['service_id'] ?? '') == $service['id']) ? 'selected' : '' ?>><?= h($service['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" aria-label="Filter by status">
                    <option value="">All status</option>
                    <?php foreach (['Pending' => 'Pending', 'Approved' => 'Approve', 'Completed' => 'Completed'] as $statusValue => $statusLabel): ?>
                        <option value="<?= h($statusValue) ?>" <?= (($_GET['status'] ?? '') === $statusValue) ? 'selected' : '' ?>><?= h($statusLabel) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($_GET['date']) || !empty($_GET['service_id']) || !empty($_GET['status'])): ?>
                    <a class="button-secondary" href="<?= h(route_url('Admin/index.php?page=appointments')) ?>">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        <table>
            <thead><tr><th>Reference</th><th>Patient</th><th>Service</th><th>Schedule</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="5" class="empty-table-message">No results found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= h($appointment['reference_code']) ?></td>
                    <td><?= h($appointment['patient']['full_name']) ?></td>
                    <td><?= h($appointment['service']['name']) ?></td>
                    <td><?= h(date('M d, Y', strtotime($appointment['scheduled_date']))) ?>, <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></td>
                    <td><span class="badge <?= h(status_badge_class($appointment['status'])) ?>"><?= h($appointment['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php elseif ($page === 'patients'): ?>
    <section class="table-card">
        <h3>Patient records and history</h3>
        <table>
            <thead><tr><th>Patient</th><th>Email</th><th>Contact</th><th>Visit history</th></tr></thead>
            <tbody>
            <?php foreach (patients() as $patient): ?>
                <?php $history = array_filter(appointments(), fn ($appointment) => (int) $appointment['user_id'] === (int) $patient['id']); ?>
                <tr>
                    <td><?= h($patient['full_name']) ?></td>
                    <td><?= h($patient['email']) ?></td>
                    <td><?= h($patient['contact_number']) ?></td>
                    <td><?= h((string) count($history)) ?> appointment(s)</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php elseif ($page === 'users'): ?>
    <div class="split-cards">
    <section class="table-card">
        <h3>Add new doctor or secretary</h3>
        <form method="post" action="<?= h(route_url('create_user.php')) ?>" class="form-grid admin-user-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label class="field">
                <span>Full Name</span>
                <input type="text" name="name" value="<?= h($userFormOld['name'] ?? '') ?>" required>
            </label>
            <label class="field">
                <span>Username</span>
                <input type="text" name="username" value="<?= h($userFormOld['username'] ?? '') ?>" required>
            </label>
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="<?= h($userFormOld['email'] ?? '') ?>" required>
            </label>
            <label class="field">
                <span>Contact Number</span>
                <input type="text" name="contact_number" value="<?= h($userFormOld['contact_number'] ?? '') ?>" maxlength="11" inputmode="numeric" pattern="\d{1,11}" required>
            </label>
            <label class="field">
                <span>Role</span>
                <select name="role" required data-admin-user-role>
                    <option value="">Select role</option>
                    <?php foreach (admin_user_roles() as $roleValue => $roleLabel): ?>
                        <option value="<?= h($roleValue) ?>" <?= (($userFormOld['role'] ?? '') === $roleValue) ? 'selected' : '' ?>><?= h($roleLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field" data-admin-user-service-field hidden>
                <span>Service</span>
                <select name="service_role" data-admin-user-service disabled>
                    <option value="">Select service</option>
                    <?php foreach (services() as $service): ?>
                        <option value="<?= h((string) $service['id']) ?>" <?= ((string) ($userFormOld['service_role'] ?? '') === (string) $service['id']) ? 'selected' : '' ?>><?= h($service['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <div class="field full">
                <button class="button" type="submit">Add User</button>
            </div>
        </form>
    </section>
    <section class="table-card">
        <h3>Role-based team access</h3>
        <div class="team-users-table-wrap">
        <table class="team-users-table">
            <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Contact</th><th>Role</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach (users() as $user): ?>
                <?php
                $userModalId = 'userDetailsModal' . (int) $user['id'];
                $scheduleDisplay = schedule_display_value($user['schedule'] ?? null);
                ?>
                <tr>
                    <td>
                        <div class="team-user-menu" data-user-menu>
                            <button class="team-user-toggle" type="button" data-user-menu-toggle aria-expanded="false">
                                <span class="team-user-avatar"><?= h(strtoupper(substr((string) $user['name'], 0, 1))) ?></span>
                                <span class="team-user-name"><?= h($user['name']) ?></span>
                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <div class="team-user-dropdown" data-user-dropdown hidden>
                                <button type="button" data-modal-open="<?= h($userModalId) ?>">View Details</button>
                                <a href="mailto:<?= h($user['email']) ?>">Send Email</a>
                            </div>
                        </div>
                    </td>
                    <td class="team-cell-text"><?= h($user['username'] ?? '-') ?></td>
                    <td class="team-cell-text"><?= h($user['email']) ?></td>
                    <td class="team-cell-text"><?= h($user['contact_number'] ?? '-') ?></td>
                    <td class="team-cell-role"><span class="badge badge-muted"><?= h(user_role_label($user['role'])) ?></span></td>
                    <td class="actions-cell">
                        <button class="button-secondary compact-button" type="button" data-modal-open="<?= h($userModalId) ?>">View Details</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </section>

    <?php foreach (users() as $user): ?>
        <?php
        $userModalId = 'userDetailsModal' . (int) $user['id'];
        $scheduleDisplay = schedule_display_value($user['schedule'] ?? null);
        $isPatientUser = canonical_role((string) $user['role']) === 'patient';
        ?>
        <div class="auth-modal" id="<?= h($userModalId) ?>" aria-hidden="true">
            <div class="auth-backdrop" data-modal-close></div>
            <div class="auth-dialog card user-detail-dialog">
                <button class="auth-close" type="button" data-modal-close aria-label="Close user details">&times;</button>
                <p class="eyebrow">Team access</p>
                <h2 class="section-title"><?= h($user['name']) ?></h2>
                <div class="user-detail-grid">
                    <div class="user-detail-item"><span>Name</span><strong><?= h($user['name']) ?></strong></div>
                    <div class="user-detail-item"><span>Username</span><strong><?= h($user['username'] ?? '-') ?></strong></div>
                    <div class="user-detail-item"><span>Email</span><strong><?= h($user['email']) ?></strong></div>
                    <div class="user-detail-item"><span>Contact</span><strong><?= h($user['contact_number'] ?? '-') ?></strong></div>
                    <div class="user-detail-item"><span>Role</span><strong><?= h(user_role_label($user['role'])) ?></strong></div>
                    <?php if (!$isPatientUser): ?>
                        <div class="user-detail-item"><span>Assigned Service</span><strong><?= h($user['service_role'] ?? '-') ?></strong></div>
                        <div class="user-detail-item full"><span>Schedule</span><strong><?= render_schedule_display($user['schedule'] ?? null) ?></strong></div>
                    <?php endif; ?>
                </div>
                <div class="step-actions">
                    <button class="button" type="button" data-modal-close>Close</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php elseif ($page === 'reports'): ?>
    <?php
        $reportStartDate = date('Y-m-01');
        $reportEndDate = date('Y-m-t');
        $reportsPayload = [
            'appointments' => array_map(fn ($appointment) => [
                'id' => (int) $appointment['id'],
                'status' => $appointment['status'],
                'scheduled_date' => $appointment['scheduled_date'],
                'start_time' => $appointment['start_time'],
                'service_name' => $appointment['service']['name'] ?? ($appointment['service_name'] ?? 'Unknown service'),
                'service_fee' => (float) ($appointment['service_fee'] ?? $appointment['service']['price'] ?? 0),
                'staff_id' => (int) ($appointment['staff_id'] ?? 0),
                'staff_name' => $appointment['staff']['name'] ?? 'Unassigned',
                'patient_id' => (int) ($appointment['user_id'] ?? 0),
                'patient_email' => $appointment['patient']['email'] ?? '',
            ], appointments()),
            'patients' => array_map(fn ($patient) => [
                'id' => (int) $patient['id'],
                'email' => $patient['email'],
            ], patients()),
            'doctors' => array_values(array_map(fn ($user) => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
            ], array_filter(users(), fn ($user) => in_array($user['role'], ['doctor', 'dentist'], true)))),
        ];
    ?>
    <section class="table-card reports-module" data-admin-reports>
        <div class="reports-toolbar">
            <label class="field">
                <span>Report Type</span>
                <select data-report-type>
                    <option value="revenue">Revenue Reports</option>
                    <option value="doctor">Doctor Performance Report</option>
                    <option value="appointments">Appointment Analytics</option>
                    <option value="patients">Patient Report</option>
                </select>
            </label>
            <label class="field">
                <span>Start Date</span>
                <input type="date" value="<?= h($reportStartDate) ?>" data-report-start>
            </label>
            <label class="field">
                <span>End Date</span>
                <input type="date" value="<?= h($reportEndDate) ?>" data-report-end>
            </label>
        </div>
        <div class="reports-loading" data-report-loading hidden>Loading report...</div>
        <div class="reports-output" data-report-output aria-live="polite"></div>
        <script type="application/json" data-report-data><?= json_encode($reportsPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    </section>
<?php else: ?>
    <section class="table-card">
        <h3>Clinic settings</h3>
        <p class="muted">Working hours, slot interval, break periods, and blackout dates are modeled in the shared scheduling configuration. This page is ready for a future editable settings form.</p>
    </section>
<?php endif; ?>

<?php if ($page === 'dashboard'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>
<?php render_admin_shell_end();
