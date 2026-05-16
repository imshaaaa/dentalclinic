<?php

require_once __DIR__ . '/../Config/bootstrap.php';
require_login(['dentist', 'admin']);

$dentistId = current_user_id();
$dentistAppointments = appointments(['staff_id' => $dentistId]);
$dentistAcceptedAppointments = array_values(array_filter(
    $dentistAppointments,
    fn (array $appointment): bool => $appointment['status'] === 'Accepted'
));
$dentistUpcomingAppointments = array_slice($dentistAcceptedAppointments, 0, 8);
$today = date('Y-m-d');
$dentistCompletedAppointments = array_values(array_filter(
    $dentistAppointments,
    fn (array $appointment): bool => $appointment['status'] === 'Completed'
));

// Revenue aggregation: only completed appointments assigned to the logged-in dentist count as sales.
$totalSales = array_reduce($dentistCompletedAppointments, function (float $total, array $appointment): float {
    return $total + (float) ($appointment['service_fee'] ?? $appointment['service']['price'] ?? 0);
}, 0.0);

$dailySalesByMonth = [];
foreach ($dentistCompletedAppointments as $appointment) {
    $date = (string) $appointment['scheduled_date'];
    $monthKey = date('Y-m', strtotime($date));
    $dayKey = date('Y-m-d', strtotime($date));

    if (!isset($dailySalesByMonth[$monthKey])) {
        $dailySalesByMonth[$monthKey] = [];
    }

    // Daily revenue aggregation: group completed appointments by date and sum service prices.
    $dailySalesByMonth[$monthKey][$dayKey] = ($dailySalesByMonth[$monthKey][$dayKey] ?? 0) + (float) ($appointment['service_fee'] ?? $appointment['service']['price'] ?? 0);
}

foreach ($dailySalesByMonth as &$dailySales) {
    ksort($dailySales);
}
unset($dailySales);

$selectedSalesMonth = date('Y-m');

$dentistPatientIds = array_unique(array_map(
    fn (array $appointment): int => (int) ($appointment['user_id'] ?? 0),
    $dentistAppointments
));
$dentistPatients = array_values(array_filter(
    patients(),
    fn (array $patient): bool => in_array((int) $patient['id'], $dentistPatientIds, true)
));

render_admin_shell_start('Dentist dashboard', 'dashboard');
?>
<div class="stats-grid dentist-stats-grid">
    <div class="stats-card"><span>Today's Appointments</span><strong><?= h((string) count(array_filter($dentistAppointments, fn (array $appointment): bool => $appointment['scheduled_date'] === $today))) ?></strong></div>
    <div class="stats-card"><span>Pending Approvals</span><strong><?= h((string) count(array_filter($dentistAppointments, fn (array $appointment): bool => $appointment['status'] === 'Pending'))) ?></strong></div>
    <div class="stats-card"><span>Completed Appointments</span><strong><?= h((string) count($dentistCompletedAppointments)) ?></strong></div>
    <div class="stats-card"><span>Patients</span><strong><?= h((string) count($dentistPatients)) ?></strong></div>
    <div class="stats-card"><span>Total Sales</span><strong>PHP <?= h(number_format($totalSales, 2)) ?></strong></div>
</div>
<section class="table-card admin-chart-card">
    <div class="admin-chart-header">
        <div>
            <h3>Sales Chart</h3>
            <p class="muted">Daily completed appointment revenue for the selected month.</p>
        </div>
        <label class="field compact-month-field">
            <span>Month</span>
            <input type="month" value="<?= h($selectedSalesMonth) ?>" data-dentist-sales-month>
        </label>
    </div>
    <canvas id="dentistSalesChart" class="dashboard-chart-canvas" height="112" data-dentist-sales-chart data-selected-month="<?= h($selectedSalesMonth) ?>" data-monthly-sales="<?= h(json_encode($dailySalesByMonth, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>"></canvas>
</section>
<div class="dashboard-grid">
    <section class="table-card" id="schedule">
        <h3>Upcoming Appointment</h3>
        <table>
            <thead><tr><th>Patient</th><th>Service</th><th>Schedule</th><th>Status</th></tr></thead>
            <tbody>
            <!-- Accepted filter applied above: only Accepted appointments render in Upcoming Appointment. -->
            <?php foreach ($dentistUpcomingAppointments as $appointment): ?>
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
    <section class="table-card" id="records">
        <h3>Appointment History</h3>
        <table>
            <thead><tr><th>Patient</th><th>Service</th><th>Completed Date</th><th>Notes</th></tr></thead>
            <tbody>
            <!-- Completed appointments moved here: Appointment History displays completed appointments only. -->
            <?php foreach ($dentistCompletedAppointments as $appointment): ?>
                <tr>
                    <td><?= h($appointment['patient']['full_name']) ?></td>
                    <td><?= h($appointment['service']['name']) ?></td>
                    <td><?= h(date('M d, Y', strtotime($appointment['scheduled_date']))) ?></td>
                    <td><?= h($appointment['notes'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php render_admin_shell_end();
