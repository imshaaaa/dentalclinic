<?php

require_once __DIR__ . '/Config/bootstrap.php';
require_login();

$user = current_user();

render_admin_shell_start('Profile', 'profile');
?>
<div class="dashboard-grid profile-grid">
    <section class="table-card">
        <h3>Profile Information</h3>
        <p class="muted">Your account details are loaded from the current session profile.</p>
        <form class="form-grid profile-form" data-profile-form data-endpoint="<?= h(route_url('update_profile.php')) ?>" data-role="<?= h((string) $user['role']) ?>">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label class="field">
                <span>Name</span>
                <input type="text" name="name" required>
            </label>
            <label class="field profile-username-field">
                <span>Username</span>
                <input type="text" name="username">
            </label>
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" required>
            </label>
            <label class="field">
                <span>Role</span>
                <input type="text" name="role_label" readonly>
            </label>
            <label class="field profile-contact-field">
                <span>Contact Number</span>
                <input type="text" name="contact_number" maxlength="11" inputmode="numeric" pattern="\d{1,11}" required>
            </label>
            <label class="field profile-service-role-field" hidden>
                <span>Assigned Service Role</span>
                <input type="text" name="service_role" readonly>
            </label>
            <label class="field full profile-schedule-field" hidden>
                <span>Schedule</span>
                <textarea name="schedule_display" readonly></textarea>
            </label>
            <div class="field full">
                <button class="button" type="submit">Update Profile</button>
            </div>
        </form>
        <div class="flash profile-alert" data-profile-alert hidden></div>
    </section>

    <section class="table-card">
        <h3>Change Password</h3>
        <p class="muted">Use your current password before saving a new one.</p>
        <form class="form-grid password-form" data-password-form data-endpoint="<?= h(route_url('change_password.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label class="field full">
                <span>Current Password</span>
                <div class="password-field-wrap">
                    <input type="password" name="current_password" required data-password-input>
                    <button class="password-toggle auth-link-reset" type="button" data-password-toggle aria-label="Show password">
                        <span class="password-toggle-icon is-visible" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="M12 5C6.5 5 2.1 8.4 1 12c1.1 3.6 5.5 7 11 7s9.9-3.4 11-7c-1.1-3.6-5.5-7-11-7Zm0 11.2A4.2 4.2 0 1 1 12 7.8a4.2 4.2 0 0 1 0 8.4Zm0-6.7a2.5 2.5 0 1 0 0 5.1 2.5 2.5 0 0 0 0-5.1Z"/></svg>
                        </span>
                        <span class="password-toggle-icon is-hidden" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="m3.3 2 18.7 18.7-1.4 1.4-3.1-3.1A13.3 13.3 0 0 1 12 20C6.5 20 2.1 16.6 1 13c.6-2 2.1-3.8 4.1-5.1L1.9 4.7 3.3 2Zm8.7 6a4.2 4.2 0 0 1 4.2 4.2c0 .8-.2 1.5-.6 2.1l-5.7-5.7c.6-.4 1.3-.6 2.1-.6Zm0-3c5.5 0 9.9 3.4 11 7a10.8 10.8 0 0 1-4 5.2l-1.5-1.5A8.9 8.9 0 0 0 20.9 12c-1-2.7-4.5-5.3-8.9-5.3-1.4 0-2.8.3-4 .8L6.7 6.2A12.6 12.6 0 0 1 12 5Zm-7.7 7c1 2.7 4.5 5.3 8.9 5.3 1 0 2-.1 2.9-.4l-2-2a4.2 4.2 0 0 1-5.7-5.7l-2-2A8.7 8.7 0 0 0 4.3 12Z"/></svg>
                        </span>
                    </button>
                </div>
            </label>
            <label class="field">
                <span>New Password</span>
                <div class="password-field-wrap">
                    <input type="password" name="new_password" required data-password-input>
                    <button class="password-toggle auth-link-reset" type="button" data-password-toggle aria-label="Show password">
                        <span class="password-toggle-icon is-visible" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="M12 5C6.5 5 2.1 8.4 1 12c1.1 3.6 5.5 7 11 7s9.9-3.4 11-7c-1.1-3.6-5.5-7-11-7Zm0 11.2A4.2 4.2 0 1 1 12 7.8a4.2 4.2 0 0 1 0 8.4Zm0-6.7a2.5 2.5 0 1 0 0 5.1 2.5 2.5 0 0 0 0-5.1Z"/></svg>
                        </span>
                        <span class="password-toggle-icon is-hidden" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="m3.3 2 18.7 18.7-1.4 1.4-3.1-3.1A13.3 13.3 0 0 1 12 20C6.5 20 2.1 16.6 1 13c.6-2 2.1-3.8 4.1-5.1L1.9 4.7 3.3 2Zm8.7 6a4.2 4.2 0 0 1 4.2 4.2c0 .8-.2 1.5-.6 2.1l-5.7-5.7c.6-.4 1.3-.6 2.1-.6Zm0-3c5.5 0 9.9 3.4 11 7a10.8 10.8 0 0 1-4 5.2l-1.5-1.5A8.9 8.9 0 0 0 20.9 12c-1-2.7-4.5-5.3-8.9-5.3-1.4 0-2.8.3-4 .8L6.7 6.2A12.6 12.6 0 0 1 12 5Zm-7.7 7c1 2.7 4.5 5.3 8.9 5.3 1 0 2-.1 2.9-.4l-2-2a4.2 4.2 0 0 1-5.7-5.7l-2-2A8.7 8.7 0 0 0 4.3 12Z"/></svg>
                        </span>
                    </button>
                </div>
            </label>
            <label class="field">
                <span>Confirm Password</span>
                <div class="password-field-wrap">
                    <input type="password" name="confirm_password" required data-password-input>
                    <button class="password-toggle auth-link-reset" type="button" data-password-toggle aria-label="Show password">
                        <span class="password-toggle-icon is-visible" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="M12 5C6.5 5 2.1 8.4 1 12c1.1 3.6 5.5 7 11 7s9.9-3.4 11-7c-1.1-3.6-5.5-7-11-7Zm0 11.2A4.2 4.2 0 1 1 12 7.8a4.2 4.2 0 0 1 0 8.4Zm0-6.7a2.5 2.5 0 1 0 0 5.1 2.5 2.5 0 0 0 0-5.1Z"/></svg>
                        </span>
                        <span class="password-toggle-icon is-hidden" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="m3.3 2 18.7 18.7-1.4 1.4-3.1-3.1A13.3 13.3 0 0 1 12 20C6.5 20 2.1 16.6 1 13c.6-2 2.1-3.8 4.1-5.1L1.9 4.7 3.3 2Zm8.7 6a4.2 4.2 0 0 1 4.2 4.2c0 .8-.2 1.5-.6 2.1l-5.7-5.7c.6-.4 1.3-.6 2.1-.6Zm0-3c5.5 0 9.9 3.4 11 7a10.8 10.8 0 0 1-4 5.2l-1.5-1.5A8.9 8.9 0 0 0 20.9 12c-1-2.7-4.5-5.3-8.9-5.3-1.4 0-2.8.3-4 .8L6.7 6.2A12.6 12.6 0 0 1 12 5Zm-7.7 7c1 2.7 4.5 5.3 8.9 5.3 1 0 2-.1 2.9-.4l-2-2a4.2 4.2 0 0 1-5.7-5.7l-2-2A8.7 8.7 0 0 0 4.3 12Z"/></svg>
                        </span>
                    </button>
                </div>
            </label>
            <div class="field full">
                <button class="button" type="submit">Change Password</button>
            </div>
        </form>
        <div class="flash profile-password-alert" data-password-alert hidden></div>
    </section>
</div>

<?php if (role_matches((string) $user['role'], 'admin')): ?>
<section class="table-card" style="margin-top: 18px;">
    <h3>All Profiles</h3>
    <table>
        <thead><tr><th>Name</th><th>Role</th><th>Username</th><th>Email</th><th>Contact</th><th>Service</th><th>Schedule</th><th>Update Schedule</th></tr></thead>
        <tbody data-admin-profiles-body data-schedule-endpoint="<?= h(route_url('update_schedule.php')) ?>" data-csrf="<?= h(csrf_token()) ?>"></tbody>
    </table>
</section>
<?php endif; ?>

<?php render_admin_shell_end();
