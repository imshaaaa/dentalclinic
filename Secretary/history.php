<?php

require_once __DIR__ . '/../Config/bootstrap.php';
require_login(['staff', 'admin']);

$appointments = appointments();

render_admin_shell_start('Appointment history', 'history');
?>
<section class="table-card">
    <h3>Appointment history</h3>
    <table>
        <thead><tr><th>Reference</th><th>Patient</th><th>Service</th><th>Schedule</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (empty($appointments)): ?>
            <tr>
                <td colspan="5" class="empty-table-message">No appointment history found.</td>
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
<?php render_admin_shell_end();
