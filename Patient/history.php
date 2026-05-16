<?php

require_once __DIR__ . '/../Config/bootstrap.php';
require_login(['patient', 'admin']);

$user = current_user();
$patientAppointments = patient_appointments_by_email($user['email']);
$completedAppointments = patient_completed_appointments($patientAppointments);

render_admin_shell_start('Appointment history', 'history');
?>
<section class="table-card">
    <div class="step-actions" style="margin-top: 0; margin-bottom: 18px;">
        <div>
            <h3>Appointment History</h3>
        </div>
    </div>
    <table>
        <thead><tr><th>Reference</th><th>Service</th><th>Schedule</th><th>Status</th><th>Notes</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($completedAppointments as $appointment): ?>
            <?php $modalId = 'historyModal' . (int) $appointment['id']; ?>
            <tr>
                <td><?= h($appointment['reference_code']) ?></td>
                <td><?= h($appointment['service']['name']) ?></td>
                <td><?= h(date('M d, Y', strtotime($appointment['scheduled_date']))) ?>, <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></td>
                <td><span class="badge <?= h(status_badge_class($appointment['status'])) ?>"><?= h($appointment['status']) ?></span></td>
                <td><?= h($appointment['notes'] ?: 'No notes') ?></td>
                <td><button class="button-secondary compact-button" type="button" data-modal-open="<?= h($modalId) ?>">View Details</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php foreach ($completedAppointments as $appointment): ?>
    <?php
    $modalId = 'historyModal' . (int) $appointment['id'];
    $rule = service_booking_rule((int) $appointment['service_id']);
    ?>
    <div class="auth-modal" id="<?= h($modalId) ?>" aria-hidden="true">
        <div class="auth-backdrop" data-modal-close></div>
        <div class="auth-dialog card history-dialog">
            <button class="auth-close" type="button" data-modal-close aria-label="Close appointment details">&times;</button>
            <p class="eyebrow">Appointment details</p>
            <h2 class="section-title"><?= h($appointment['service']['name']) ?></h2>
            <div class="panel history-detail-panel">
                <div class="summary-item"><strong>Reference:</strong> <?= h($appointment['reference_code']) ?></div>
                <div class="summary-item"><strong>Service:</strong> <?= h($appointment['service']['name']) ?></div>
                <div class="summary-item"><strong>Doctor:</strong> <?= h($rule['doctor_name'] ?? 'Assigned doctor') ?></div>
                <div class="summary-item"><strong>Schedule:</strong> <?= h(date('F d, Y', strtotime($appointment['scheduled_date']))) ?> at <?= h(date('g:i A', strtotime($appointment['start_time']))) ?></div>
                <div class="summary-item"><strong>Status:</strong> <span class="badge <?= h(status_badge_class($appointment['status'])) ?>"><?= h($appointment['status']) ?></span></div>
                <div class="summary-item"><strong>Notes:</strong> <?= h($appointment['notes'] ?: 'No notes provided.') ?></div>
            </div>
            <div class="step-actions">
                <button class="button" type="button" data-modal-close>Close</button>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php render_booking_modal(route_url('Patient/history.php?modal=booking')); ?>
<?php render_admin_shell_end();
