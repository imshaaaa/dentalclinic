<?php

require_once __DIR__ . '/../Config/bootstrap.php';
require_login(['staff', 'admin']);

verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'accept';
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $appointment = null;
    foreach (appointments() as $candidate) {
        if ((int) $candidate['id'] === $appointmentId) {
            $appointment = $candidate;
            break;
        }
    }

    // Secretary role restriction: actions are limited to accepting, rescheduling, and completing.
    if ($action === 'accept' && $appointment && $appointment['status'] === 'Pending') {
        $updated = update_appointment($appointmentId, ['status' => 'Accepted']);
        if ($updated) {
            add_notification('Appointment accepted', sprintf('%s was accepted by the secretary.', $updated['reference_code']));
            flash('secretary_message', 'Appointment accepted successfully.');
        }
    } elseif ($action === 'complete' && $appointment && in_array($appointment['status'], ['Accepted', 'Approved'], true)) {
        $updated = update_appointment($appointmentId, ['status' => 'Completed']);
        if ($updated) {
            add_notification('Appointment completed', sprintf('%s was marked completed by the secretary.', $updated['reference_code']));
            flash('secretary_message', 'Appointment marked as completed.');
        }
    } elseif ($action === 'reschedule' && $appointment && !in_array($appointment['status'], ['Cancelled', 'Rejected', 'Completed'], true)) {
        $newDate = trim($_POST['scheduled_date'] ?? '');
        $newTime = trim($_POST['start_time'] ?? '');
        $doctorId = (int) ($appointment['staff_id'] ?? 0);
        $slots = slots_for_service((int) $appointment['service_id'], $newDate, $appointmentId, $doctorId ?: null);
        $selectedSlot = null;
        foreach ($slots as $slot) {
            if ($slot['start'] === $newTime && $slot['available']) {
                $selectedSlot = $slot;
                break;
            }
        }

        if ($selectedSlot) {
            $updated = update_appointment($appointmentId, [
                'scheduled_date' => $newDate,
                'start_time' => $selectedSlot['start'],
                'end_time' => $selectedSlot['end'],
                'status' => $appointment['status'],
            ]);
            if ($updated) {
                add_notification('Appointment rescheduled', sprintf('%s was rescheduled by the secretary.', $updated['reference_code']));
                flash('secretary_message', 'Appointment rescheduled successfully.');
            }
        } else {
            flash('secretary_error', 'Please choose an available date and time.');
        }
    } else {
        flash('secretary_error', 'This appointment cannot be updated.');
    }

    header('Location: ' . route_url('Secretary/index.php#appointments'));
    exit;
}

$appointments = appointments();
$pendingAppointments = array_values(array_filter($appointments, fn ($appointment) => $appointment['status'] === 'Pending'));
$todayAppointmentCount = count(array_filter(
    is_array($appointments) ? $appointments : [],
    fn ($appointment) => ($appointment['scheduled_date'] ?? '') === date('Y-m-d')
));

render_admin_shell_start('Secretary dashboard', 'dashboard');
?>
<?php if ($message = flash('secretary_message')): ?>
    <div class="flash"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error = flash('secretary_error')): ?>
    <div class="flash"><span class="error-text"><?= h($error) ?></span></div>
<?php endif; ?>
<div class="stats-grid">
    <div class="stats-card"><span>Pending requests</span><strong><?= h((string) count($pendingAppointments)) ?></strong></div>
    <div class="stats-card"><span>All appointments</span><strong><?= h((string) count($appointments)) ?></strong></div>
    <div class="stats-card"><span>Today's Appointment</span><strong><?= h((string) $todayAppointmentCount) ?></strong></div>
    <div class="stats-card"><span>Notifications</span><strong><?= h((string) count(notifications())) ?></strong></div>
</div>
<div class="dashboard-grid">
    <section class="table-card" id="appointments">
        <h3>Appointment management</h3>
        <table>
            <thead><tr><th>Reference</th><th>Patient</th><th>Service</th><th>Schedule</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <?php
                    $canReschedule = !in_array($appointment['status'], ['Cancelled', 'Rejected', 'Completed'], true);
                    $canComplete = in_array($appointment['status'], ['Accepted', 'Approved'], true);
                    $rescheduleModalId = 'secretaryRescheduleModal' . (int) $appointment['id'];
                ?>
                <tr>
                    <td><?= h($appointment['reference_code']) ?></td>
                    <td><?= h($appointment['patient']['full_name']) ?></td>
                    <td><?= h($appointment['service']['name']) ?></td>
                    <td><?= h(date('M d, Y', strtotime($appointment['scheduled_date']))) ?>, <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></td>
                    <td><span class="badge <?= h(status_badge_class($appointment['status'])) ?>"><?= h($appointment['status']) ?></span></td>
                    <td>
                        <div class="table-actions">
                            <select class="action-dropdown" aria-label="Appointment action" data-secretary-action-select>
                                <option value="" hidden selected></option>
                                <option value="accept" <?= $appointment['status'] === 'Pending' ? '' : 'disabled' ?>>Accept</option>
                                <option value="reschedule" <?= $canReschedule ? '' : 'disabled' ?>>Reschedule</option>
                                <option value="complete" <?= $canComplete ? '' : 'disabled' ?>>Completed</option>
                            </select>
                            <form method="post" data-secretary-action-form="accept" data-accept-appointment-form hidden>
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="appointment_id" value="<?= h((string) $appointment['id']) ?>">
                            </form>
                            <button type="button" data-secretary-action-form="reschedule" data-modal-open="<?= h($rescheduleModalId) ?>" hidden>Reschedule</button>
                            <form method="post" data-secretary-action-form="complete" hidden>
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="complete">
                                <input type="hidden" name="appointment_id" value="<?= h((string) $appointment['id']) ?>">
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php foreach ($appointments as $appointment): ?>
            <?php
                if (in_array($appointment['status'], ['Cancelled', 'Rejected', 'Completed'], true)) {
                    continue;
                }

                $rescheduleModalId = 'secretaryRescheduleModal' . (int) $appointment['id'];
                $rescheduleDate = $appointment['scheduled_date'] ?: date('Y-m-d', strtotime('+1 day'));
                $doctorId = (int) ($appointment['staff_id'] ?? 0);
                $rescheduleSlots = slots_for_service(
                    (int) $appointment['service_id'],
                    $rescheduleDate,
                    (int) $appointment['id'],
                    $doctorId ?: null
                );
            ?>
            <div class="auth-modal" id="<?= h($rescheduleModalId) ?>" aria-hidden="true">
                <div class="auth-backdrop" data-modal-close></div>
                <div class="auth-dialog card user-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= h($rescheduleModalId) ?>Title">
                    <button class="modal-close profile-modal-close" type="button" data-modal-close aria-label="Close">&times;</button>
                    <div class="modal-header">
                        <div>
                            <span class="eyebrow">Secretary action</span>
                            <h2 id="<?= h($rescheduleModalId) ?>Title">Reschedule Appointment</h2>
                        </div>
                    </div>
                    <div class="profile-detail-list">
                        <div><span>Reference</span><strong><?= h($appointment['reference_code']) ?></strong></div>
                        <div><span>Patient</span><strong><?= h($appointment['patient']['full_name']) ?></strong></div>
                        <div><span>Service</span><strong><?= h($appointment['service']['name']) ?></strong></div>
                        <div><span>Current Schedule</span><strong><?= h(date('M d, Y', strtotime($appointment['scheduled_date']))) ?>, <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></strong></div>
                    </div>
                    <form method="post" class="form-grid">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="reschedule">
                        <input type="hidden" name="appointment_id" value="<?= h((string) $appointment['id']) ?>">
                        <label class="field">
                            <span>New Date</span>
                            <input type="date" name="scheduled_date" value="<?= h($rescheduleDate) ?>" min="<?= h(date('Y-m-d')) ?>" required>
                        </label>
                        <label class="field">
                            <span>New Time</span>
                            <select name="start_time" required>
                                <?php foreach ($rescheduleSlots as $slot): ?>
                                    <option value="<?= h($slot['start']) ?>" <?= $slot['available'] ? '' : 'disabled' ?> <?= $slot['start'] === $appointment['start_time'] ? 'selected' : '' ?>>
                                        <?= h($slot['label']) ?><?= $slot['available'] ? '' : ' - unavailable' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="field full profile-modal-actions">
                            <button class="button" type="submit">Save Schedule</button>
                            <button class="button-secondary" type="button" data-modal-close>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
    <section class="table-card" id="updates">
        <h3>Recent updates</h3>
        <?php foreach (notifications() as $notification): ?>
            <div class="timeline-item">
                <strong><?= h($notification['title']) ?></strong>
                <p><?= h($notification['message']) ?></p>
            </div>
        <?php endforeach; ?>
    </section>
</div>
<?php render_admin_shell_end();
