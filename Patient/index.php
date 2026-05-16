<?php

require_once __DIR__ . '/../Config/bootstrap.php';
require_login(['patient', 'admin']);

$user = current_user();
$rescheduleError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'patient_reschedule') {
    verify_csrf();

    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $newDate = $_POST['scheduled_date'] ?? '';
    $newTime = $_POST['start_time'] ?? '';
    $appointment = current(array_filter(
        patient_appointments_by_email($user['email']),
        fn (array $item): bool => (int) $item['id'] === $appointmentId
    ));

    $doctorId = (int) ($appointment['staff_id'] ?? 0);
    $slots = $appointment ? slots_for_service((int) $appointment['service_id'], $newDate, $appointmentId, $doctorId ?: null) : [];
    $selectedSlot = current(array_filter(
        $slots,
        fn (array $slot): bool => $slot['start'] === $newTime && $slot['available']
    ));

    if ($appointment && appointment_patient_reschedule_allowed($appointment) && $selectedSlot) {
        update_appointment($appointmentId, [
            'scheduled_date' => $newDate,
            'start_time' => $selectedSlot['start'],
            'end_time' => $selectedSlot['end'],
            // Accepted -> Pending: rescheduled requests go back to secretary/admin review.
            'status' => 'Pending',
        ]);
        flash('patient_message', 'Your reschedule request was submitted and is pending review.');
        header('Location: ' . route_url('Patient/index.php'));
        exit;
    }

    $rescheduleError = 'Please choose an available date and time for this appointment.';
}

$patientAppointments = patient_appointments_by_email($user['email']);
$upcoming = patient_upcoming_appointments($patientAppointments);
$completedAppointments = patient_completed_appointments($patientAppointments);

render_admin_shell_start('Patient dashboard', 'dashboard');
?>
<?php if ($message = flash('patient_message')): ?>
    <div class="flash"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($rescheduleError): ?>
    <div class="flash"><span class="error-text"><?= h($rescheduleError) ?></span></div>
<?php endif; ?>
<div class="stats-grid">
    <div class="stats-card"><span>Upcoming visits</span><strong><?= h((string) count($upcoming)) ?></strong></div>
    <div class="stats-card"><span>Total bookings</span><strong><?= h((string) count($patientAppointments)) ?></strong></div>
    <div class="stats-card"><span>Pending Appointment</span><strong><?= h((string) count(array_filter($patientAppointments, fn ($appointment) => $appointment['status'] === 'Pending'))) ?></strong></div>
    <div class="stats-card"><span>Approved Appointment</span><strong><?= h((string) count(array_filter($patientAppointments, fn ($appointment) => $appointment['status'] === 'Approved'))) ?></strong></div>
</div>
<div class="dashboard-grid">
    <section class="table-card">
        <div class="step-actions" style="margin-top: 0; margin-bottom: 18px;">
            <div>
                <h3>Upcoming visits</h3>
            </div>
            <button class="button auth-link-reset compact-button" type="button" data-booking-open>Book appointment</button>
        </div>
        <table>
            <thead><tr><th>Reference</th><th>Service</th><th>Schedule</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($upcoming as $appointment): ?>
                <?php
                    $rescheduleModalId = 'patientRescheduleModal' . (int) $appointment['id'];
                    $canReschedule = appointment_patient_reschedule_allowed($appointment);
                ?>
                <tr>
                    <td><?= h($appointment['reference_code']) ?></td>
                    <td><?= h($appointment['service']['name']) ?></td>
                    <td><?= h(date('M d, Y', strtotime($appointment['scheduled_date']))) ?>, <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></td>
                    <td><span class="badge <?= h(status_badge_class($appointment['status'])) ?>"><?= h($appointment['status']) ?></span></td>
                    <td>
                        <?php if ($canReschedule): ?>
                            <button class="button-secondary compact-button" type="button" data-modal-open="<?= h($rescheduleModalId) ?>">Reschedule</button>
                        <?php else: ?>
                            <span class="muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <section class="table-card">
        <h3>Appointment History</h3>
        <div class="summary-list">
           
            
        </div>
        <?php foreach (array_slice($completedAppointments, 0, 4) as $appointment): ?>
            <div class="timeline-item">
                <strong><?= h($appointment['service']['name']) ?> - <?= h($appointment['status']) ?></strong>
                <p><?= h(date('F d, Y', strtotime($appointment['scheduled_date']))) ?> at <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></p>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<?php foreach ($upcoming as $appointment): ?>
    <?php
        $rescheduleModalId = 'patientRescheduleModal' . (int) $appointment['id'];
        $doctorId = (int) ($appointment['staff_id'] ?? 0);
        $rescheduleDate = $appointment['scheduled_date'] ?: date('Y-m-d', strtotime('+1 day'));
        $rescheduleSlots = slots_for_service((int) $appointment['service_id'], $rescheduleDate, (int) $appointment['id'], $doctorId ?: null);
        $canReschedule = appointment_patient_reschedule_allowed($appointment);
    ?>
    <div class="auth-modal" id="<?= h($rescheduleModalId) ?>" aria-hidden="true">
        <div class="auth-backdrop" data-modal-close></div>
        <div class="auth-dialog card user-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= h($rescheduleModalId) ?>Title">
            <button class="auth-close" type="button" data-modal-close aria-label="Close reschedule dialog">&times;</button>
            <p class="eyebrow">Upcoming visit</p>
            <h2 class="section-title" id="<?= h($rescheduleModalId) ?>Title">Reschedule Appointment</h2>
            <div class="panel history-detail-panel">
                <div class="summary-item"><strong>Reference:</strong> <?= h($appointment['reference_code']) ?></div>
                <div class="summary-item"><strong>Service:</strong> <?= h($appointment['service']['name']) ?></div>
                <div class="summary-item"><strong>Current Schedule:</strong> <?= h(date('F d, Y', strtotime($appointment['scheduled_date']))) ?> at <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></div>
                <div class="summary-item"><strong>Status:</strong> <span class="badge <?= h(status_badge_class($appointment['status'])) ?>"><?= h($appointment['status']) ?></span></div>
            </div>
            <?php if ($canReschedule): ?>
                <form method="post" class="form-grid patient-reschedule-form" data-patient-reschedule-form data-endpoint="<?= h(route_url('Ajax/availability.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="patient_reschedule">
                    <input type="hidden" name="appointment_id" value="<?= h((string) $appointment['id']) ?>">
                    <input type="hidden" data-patient-reschedule-service value="<?= h((string) $appointment['service_id']) ?>">
                    <input type="hidden" data-patient-reschedule-doctor value="<?= h((string) $doctorId) ?>">
                    <label class="field">
                        <span>New Date</span>
                        <input type="date" name="scheduled_date" value="<?= h($rescheduleDate) ?>" min="<?= h(date('Y-m-d')) ?>" required data-patient-reschedule-date>
                    </label>
                    <div class="field">
                        <span>New Time</span>
                        <input type="hidden" name="start_time" value="<?= h($appointment['start_time']) ?>" data-patient-reschedule-time-value>
                        <div class="time-picker" data-patient-time-picker>
                            <button class="time-picker-trigger" type="button" data-patient-time-picker-toggle aria-expanded="false">
                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                <span data-patient-time-picker-label>
                                    <?= h(date('g:i A', strtotime($appointment['start_time']))) ?>
                                </span>
                            </button>
                            <div class="time-picker-panel" data-patient-time-picker-panel hidden>
                                <?php foreach ($rescheduleSlots as $slot): ?>
                                    <?php if ($slot['available']): ?>
                                        <button class="time-picker-option <?= $slot['start'] === $appointment['start_time'] ? 'is-selected' : '' ?>" type="button" data-patient-time-option data-time-value="<?= h($slot['start']) ?>" data-time-label="<?= h($slot['label']) ?>">
                                            <?= h($slot['label']) ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="field full muted" data-patient-reschedule-message></div>
                    <div class="field full profile-modal-actions">
                        <button class="button" type="submit">Confirm Reschedule</button>
                        <button class="button-secondary" type="button" data-modal-close>Cancel</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="flash" style="margin-top: 18px;">This appointment is no longer eligible for online rescheduling.</div>
                <div class="step-actions">
                    <button class="button" type="button" data-modal-close>Close</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php render_booking_modal(route_url('Patient/index.php?modal=booking')); ?>
<?php render_admin_shell_end();
