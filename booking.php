<?php

require_once __DIR__ . '/Config/bootstrap.php';

verify_csrf();

$step = max(1, min(5, (int) ($_GET['step'] ?? 1)));
$booking = booking_session();
$errors = [];
$createdAppointment = $_SESSION['booking_completed'] ?? null;
unset($_SESSION['booking_completed']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'choose_service') {
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        if (service_by_id($serviceId)) {
            $booking['service_id'] = $serviceId;
            unset($booking['scheduled_date'], $booking['start_time']);
            save_booking_session($booking);
            header('Location: ' . route_url('booking.php?step=2'));
            exit;
        }
        $errors['service_id'] = 'Select a service to continue.';
        $step = 1;
    }

    if ($action === 'choose_schedule') {
        $serviceId = (int) ($booking['service_id'] ?? 0);
        $date = $_POST['scheduled_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $slots = slots_for_service($serviceId, $date);
        $validSlot = false;
        foreach ($slots as $slot) {
            if ($slot['start'] === $startTime && $slot['available']) {
                $validSlot = true;
                break;
            }
        }
        if ($date && $validSlot) {
            $booking['scheduled_date'] = $date;
            $booking['start_time'] = $startTime;
            save_booking_session($booking);
            header('Location: ' . route_url('booking.php?step=3'));
            exit;
        }
        $errors['schedule'] = 'Choose an available date and time to continue.';
        $step = 2;
    }

    if ($action === 'patient_details') {
        $patientInput = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
        $errors = validate_patient_input($patientInput);
        if (!$errors) {
            $booking['patient'] = $patientInput;
            save_booking_session($booking);
            header('Location: ' . route_url('booking.php?step=4'));
            exit;
        }
        $booking['patient'] = $patientInput;
        save_booking_session($booking);
        $step = 3;
    }

    if ($action === 'confirm_booking') {
        try {
            $appointment = create_appointment($booking, $booking['patient'] ?? []);
            clear_booking_session();
            $_SESSION['booking_completed'] = $appointment;
            header('Location: ' . route_url('booking.php?step=5'));
            exit;
        } catch (Throwable $exception) {
            $errors['schedule'] = $exception->getMessage();
            $step = 4;
        }
    }
}

if ($step > 1 && empty($booking['service_id']) && !$createdAppointment) {
    header('Location: ' . route_url('booking.php?step=1'));
    exit;
}
if ($step > 2 && (empty($booking['scheduled_date']) || empty($booking['start_time'])) && !$createdAppointment) {
    header('Location: ' . route_url('booking.php?step=2'));
    exit;
}
if ($step > 3 && empty($booking['patient']) && !$createdAppointment) {
    header('Location: ' . route_url('booking.php?step=3'));
    exit;
}
if ($step === 5 && !$createdAppointment) {
    header('Location: ' . route_url('booking.php?step=1'));
    exit;
}

$selectedService = service_by_id((int) ($booking['service_id'] ?? 0));
$selectedPatient = $booking['patient'] ?? [];
$selectedDate = $booking['scheduled_date'] ?? date('Y-m-d', strtotime('+1 day'));
$slotOptions = $selectedService ? slots_for_service((int) $selectedService['id'], $selectedDate) : [];

render_public_shell_start('Book your appointment', 'A calm, guided flow inspired by professional scheduling platforms.');
?>
<div class="progress-steps">
    <?php
    $steps = [1 => 'Select Service', 2 => 'Choose Date', 3 => 'Patient Details', 4 => 'Review', 5 => 'Confirmation'];
    foreach ($steps as $number => $label): ?>
        <div class="progress-step <?= $step === $number ? 'active' : '' ?>">
            <strong><?= h(sprintf('%02d', $number)) ?></strong>
            <div><?= h($label) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="booking-grid">
    <section class="card">
        <?php if ($step === 1): ?>
            <h2 class="section-title">Select a dental service</h2>
            <p class="section-copy">Choose the treatment that matches your visit. The categories and cards keep the first step simple and scannable.</p>
            <?php if (!empty($errors['service_id'])): ?><p class="error-text"><?= h($errors['service_id']) ?></p><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="choose_service">
                <?php foreach (services_by_category() as $group): ?>
                    <div class="category-block">
                        <div class="category-header">
                            <p class="eyebrow"><?= h($group['category']['name']) ?></p>
                            <p class="muted"><?= h($group['category']['description']) ?></p>
                        </div>
                        <div class="service-grid">
                            <?php foreach ($group['services'] as $service): ?>
                                <label class="service-card" data-service-card>
                                    <input type="radio" name="service_id" value="<?= h((string) $service['id']) ?>" <?= (int) ($booking['service_id'] ?? 0) === (int) $service['id'] ? 'checked' : '' ?>>
                                    <div class="service-meta">
                                        <span class="pill"><?= h($service['duration']) ?> mins</span>
                                        <span class="pill">PHP <?= h(number_format((float) $service['price'], 2)) ?></span>
                                    </div>
                                    <h3><?= h($service['name']) ?></h3>
                                    <p class="muted"><?= h($service['description']) ?></p>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="step-actions">
                    <a class="button-ghost" href="<?= h(route_url()) ?>">Back to homepage</a>
                    <button class="button" type="submit">Continue to schedule</button>
                </div>
            </form>
        <?php elseif ($step === 2): ?>
            <h2 class="section-title">Choose date and time</h2>
            <p class="section-copy">Acuity-style scheduling keeps the page focused: calendar on one side, clear slot choices on the other.</p>
            <?php if (!empty($errors['schedule'])): ?><p class="error-text"><?= h($errors['schedule']) ?></p><?php endif; ?>
            <form method="post" data-slots-root data-service-id="<?= h((string) $selectedService['id']) ?>" data-endpoint="<?= h(route_url('Ajax/availability.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="choose_schedule">
                <div class="booking-calendar">
                    <div class="calendar-panel">
                        <p class="eyebrow">Calendar view</p>
                        <input type="date" name="scheduled_date" value="<?= h($selectedDate) ?>" min="<?= h(date('Y-m-d')) ?>" data-date-input>
                        <div class="calendar-legend">
                            <span class="pill">Available</span>
                            <span class="pill">Booked</span>
                            <span class="pill">Selected</span>
                        </div>
                    </div>
                    <div class="calendar-panel">
                        <p class="eyebrow">Time slots</p>
                        <p class="muted" data-slots-message><?= empty($slotOptions) ? 'No slots are available for this date. Please choose another date.' : 'Select a time slot to continue.' ?></p>
                        <div class="slot-grid" data-slots>
                            <?php foreach ($slotOptions as $slot): ?>
                                <label class="slot-chip <?= $slot['available'] ? '' : 'disabled' ?>">
                                    <input type="radio" name="start_time" value="<?= h($slot['start']) ?>" <?= ($booking['start_time'] ?? '') === $slot['start'] ? 'checked' : '' ?> <?= $slot['available'] ? '' : 'disabled' ?>>
                                    <span><?= h($slot['label']) ?></span>
                                    <small><?= $slot['available'] ? 'Available' : 'Booked' ?></small>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <a class="button-ghost" href="<?= h(route_url('booking.php?step=1')) ?>">Back</a>
                    <button class="button" type="submit">Continue to details</button>
                </div>
            </form>
        <?php elseif ($step === 3): ?>
            <h2 class="section-title">Enter patient details</h2>
            <p class="section-copy">Only essential details are required so the team can confirm your visit quickly.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="patient_details">
                <div class="form-grid">
                    <label class="field">
                        <span>Full Name</span>
                        <input type="text" name="full_name" value="<?= h($selectedPatient['full_name'] ?? '') ?>">
                        <?php if (!empty($errors['full_name'])): ?><span class="error-text"><?= h($errors['full_name']) ?></span><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="<?= h($selectedPatient['email'] ?? '') ?>">
                        <?php if (!empty($errors['email'])): ?><span class="error-text"><?= h($errors['email']) ?></span><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Contact Number</span>
                        <input type="text" name="contact_number" value="<?= h($selectedPatient['contact_number'] ?? '') ?>">
                        <?php if (!empty($errors['contact_number'])): ?><span class="error-text"><?= h($errors['contact_number']) ?></span><?php endif; ?>
                    </label>
                    <label class="field full">
                        <span>Notes (optional)</span>
                        <textarea name="notes"><?= h($selectedPatient['notes'] ?? '') ?></textarea>
                    </label>
                </div>
                <div class="step-actions">
                    <a class="button-ghost" href="<?= h(route_url('booking.php?step=2')) ?>">Back</a>
                    <button class="button" type="submit">Continue to review</button>
                </div>
            </form>
        <?php elseif ($step === 4): ?>
            <h2 class="section-title">Review your appointment</h2>
            <p class="section-copy">Confirm the schedule and patient details before the request is sent to the clinic.</p>
            <?php if (!empty($errors['schedule'])): ?><p class="error-text"><?= h($errors['schedule']) ?></p><?php endif; ?>
            <div class="panel">
                <div class="summary-item"><strong>Service:</strong> <?= h($selectedService['name']) ?></div>
                <div class="summary-item"><strong>Date:</strong> <?= h(date('F d, Y', strtotime($booking['scheduled_date']))) ?></div>
                <div class="summary-item"><strong>Time:</strong> <?= h(date('g:i A', strtotime($booking['start_time']))) ?></div>
                <div class="summary-item"><strong>Patient:</strong> <?= h($selectedPatient['full_name']) ?></div>
                <div class="summary-item"><strong>Email:</strong> <?= h($selectedPatient['email']) ?></div>
                <div class="summary-item"><strong>Contact:</strong> <?= h($selectedPatient['contact_number']) ?></div>
                <div class="summary-item"><strong>Notes:</strong> <?= h($selectedPatient['notes'] ?? 'No additional notes.') ?></div>
            </div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="confirm_booking">
                <div class="step-actions">
                    <a class="button-ghost" href="<?= h(route_url('booking.php?step=3')) ?>">Back</a>
                    <button class="button" type="submit">Submit booking request</button>
                </div>
            </form>
        <?php else: ?>
            <h2 class="section-title">Appointment confirmed</h2>
            <p class="section-copy">Your request is now in the system and visible to the clinic team for confirmation.</p>
            <div class="panel">
                <div class="detail-grid">
                    <span class="pill">Reference: <?= h($createdAppointment['reference_code']) ?></span>
                    <span class="badge badge-warning"><?= h($createdAppointment['status']) ?></span>
                </div>
                <div class="summary-item"><strong>Service:</strong> <?= h($createdAppointment['service']['name']) ?></div>
                <div class="summary-item"><strong>Date:</strong> <?= h(date('F d, Y', strtotime($createdAppointment['scheduled_date']))) ?></div>
                <div class="summary-item"><strong>Time:</strong> <?= h(date('g:i A', strtotime($createdAppointment['start_time']))) ?></div>
                <div class="summary-item"><strong>Patient:</strong> <?= h($createdAppointment['patient']['full_name']) ?></div>
                <div class="step-actions">
                    <a class="button-secondary" href="<?= h(route_url('manage.php?reference=' . urlencode($createdAppointment['reference_code']))) ?>">Manage appointment</a>
                    <a class="button" href="<?= h(route_url('booking.php')) ?>">Book another visit</a>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <aside class="summary-card">
        <p class="eyebrow">Booking summary</p>
        <h3>PrimeCare Dental Clinic</h3>
        <div class="summary-list">
            <div class="summary-item"><strong>Service</strong><br><?= h($selectedService['name'] ?? 'Choose a treatment') ?></div>
            <div class="summary-item"><strong>Date</strong><br><?= h(!empty($booking['scheduled_date']) ? date('F d, Y', strtotime($booking['scheduled_date'])) : 'Select a date') ?></div>
            <div class="summary-item"><strong>Time</strong><br><?= h(!empty($booking['start_time']) ? date('g:i A', strtotime($booking['start_time'])) : 'Select a time slot') ?></div>
            <div class="summary-item"><strong>Status</strong><br>Pending approval after booking</div>
        </div>
        <p class="muted">Centered layout, clear cards, and one decision at a time keep the booking experience clean and low-stress.</p>
    </aside>
</div>
<?php render_public_shell_end();
