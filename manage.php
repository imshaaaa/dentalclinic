<?php

require_once __DIR__ . '/Config/bootstrap.php';

verify_csrf();

$reference = trim($_GET['reference'] ?? ($_POST['reference_code'] ?? ''));
$contact = trim($_POST['contact_number'] ?? '');
$appointment = null;
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'lookup';

    if ($action === 'lookup') {
        $appointment = appointment_by_reference($reference, $contact);
        if (!$appointment) {
            $errors['lookup'] = 'We could not find an appointment with that reference and contact number.';
        }
    }

    if ($action === 'cancel') {
        $appointment = appointment_by_reference($reference, $contact);
        if ($appointment && appointment_change_allowed($appointment)) {
            $appointment = update_appointment((int) $appointment['id'], ['status' => 'Cancelled']);
            $success = 'The appointment has been cancelled and the time slot is now available again.';
        } else {
            $errors['lookup'] = 'This appointment can no longer be cancelled online.';
        }
    }

    if ($action === 'reschedule') {
        $appointment = appointment_by_reference($reference, $contact);
        $newDate = $_POST['scheduled_date'] ?? '';
        $newTime = $_POST['start_time'] ?? '';
        $slots = $appointment ? slots_for_service((int) $appointment['service_id'], $newDate, (int) $appointment['id']) : [];
        $valid = false;
        foreach ($slots as $slot) {
            if ($slot['start'] === $newTime && $slot['available']) {
                $valid = true;
                $endTime = $slot['end'];
                break;
            }
        }
        if ($appointment && appointment_change_allowed($appointment) && $valid) {
            $appointment = update_appointment((int) $appointment['id'], [
                'scheduled_date' => $newDate,
                'start_time' => $newTime,
                'end_time' => $endTime,
                'status' => 'Pending',
            ]);
            $success = 'The appointment was rescheduled successfully and set back to pending review.';
        } else {
            $errors['lookup'] = 'Please choose a valid new date and time for this appointment.';
        }
    }
} elseif ($reference !== '') {
    $appointment = appointment_by_reference($reference);
}

$rescheduleDate = $appointment['scheduled_date'] ?? date('Y-m-d', strtotime('+1 day'));
$rescheduleSlots = $appointment ? slots_for_service((int) $appointment['service_id'], $rescheduleDate, (int) $appointment['id']) : [];

render_public_shell_start('Manage your appointment', 'Use your appointment reference and contact number to reschedule or cancel online.');
?>
<div class="booking-grid">
    <section class="card">
        <h2 class="section-title">Lookup appointment</h2>
        <p class="section-copy">Patients can manage eligible bookings online before the reschedule cutoff window.</p>
        <?php if (!empty($errors['lookup'])): ?><div class="flash"><span class="error-text"><?= h($errors['lookup']) ?></span></div><?php endif; ?>
        <?php if ($success): ?><div class="flash"><?= h($success) ?></div><?php endif; ?>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="lookup">
            <label class="field">
                <span>Reference Code</span>
                <input type="text" name="reference_code" value="<?= h($reference) ?>">
            </label>
            <label class="field">
                <span>Contact Number</span>
                <input type="text" name="contact_number" value="<?= h($contact) ?>" maxlength="11" inputmode="numeric" pattern="\d{1,11}">
            </label>
            <div class="field full">
                <button class="button" type="submit">Find appointment</button>
            </div>
        </form>

        <?php if ($appointment): ?>
            <div class="panel" style="margin-top: 20px;">
                <div class="detail-grid">
                    <span class="pill"><?= h($appointment['reference_code']) ?></span>
                    <span class="badge <?= h(status_badge_class($appointment['status'])) ?>"><?= h($appointment['status']) ?></span>
                </div>
                <div class="summary-item"><strong>Patient:</strong> <?= h($appointment['patient']['full_name']) ?></div>
                <div class="summary-item"><strong>Service:</strong> <?= h($appointment['service']['name']) ?></div>
                <div class="summary-item"><strong>Current schedule:</strong> <?= h(date('F d, Y', strtotime($appointment['scheduled_date']))) ?> at <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></div>
                <div class="summary-item"><strong>Notes:</strong> <?= h($appointment['notes'] ?: 'No additional notes.') ?></div>
            </div>

            <?php if (appointment_change_allowed($appointment)): ?>
                <div class="split-cards" style="margin-top: 20px;">
                    <form method="post" class="form-card">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="reschedule">
                        <input type="hidden" name="reference_code" value="<?= h($appointment['reference_code']) ?>">
                        <input type="hidden" name="contact_number" value="<?= h($appointment['patient']['contact_number']) ?>">
                        <p class="eyebrow">Reschedule</p>
                        <label class="field">
                            <span>New Date</span>
                            <input type="date" name="scheduled_date" value="<?= h($rescheduleDate) ?>" min="<?= h(date('Y-m-d')) ?>">
                        </label>
                        <label class="field">
                            <span>New Time</span>
                            <select name="start_time">
                                <?php foreach ($rescheduleSlots as $slot): ?>
                                    <?php if ($slot['available']): ?>
                                        <option value="<?= h($slot['start']) ?>"><?= h($slot['label']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="button" type="submit">Reschedule appointment</button>
                    </form>

                    <form method="post" class="form-card">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="reference_code" value="<?= h($appointment['reference_code']) ?>">
                        <input type="hidden" name="contact_number" value="<?= h($appointment['patient']['contact_number']) ?>">
                        <p class="eyebrow">Cancel booking</p>
                        <h3>Need to cancel?</h3>
                        <p class="muted">Online changes stay available until <?= h((string) app_config('app.reschedule_cutoff_hours')) ?> hours before the scheduled visit.</p>
                        <button class="button-secondary" type="submit">Cancel appointment</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="flash" style="margin-top: 20px;">This appointment is no longer eligible for online changes. Please contact the clinic directly for support.</div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <aside class="summary-card">
        <p class="eyebrow">Quick help</p>
        <h3>What patients can do here</h3>
        <div class="summary-list">
            <div class="summary-item">Look up an existing appointment using the reference code</div>
            <div class="summary-item">Reschedule to another valid slot without creating overlaps</div>
            <div class="summary-item">Cancel eligible appointments before the cutoff window</div>
        </div>
    </aside>
</div>
<?php render_public_shell_end();
