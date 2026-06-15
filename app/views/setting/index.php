<?php
// Load Header sesuai sesi (Admin/User)
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    require_once __DIR__ . '/../layouts/header.php';
} else {
    require_once __DIR__ . '/../layouts/user_header.php';
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/setting.css">

<div class="container settings-page">
    <h1 class="page-title">Settings</h1>
    <p class="page-subtitle">Manage your profile and security settings</p>

    <form class="settings-form" id="settingForm">

        <div class="form-column">
            <div class="section-header">
                <h2>Profile</h2>
            </div>

            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" id="fullName" value="<?= htmlspecialchars($data['user']['full_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?= htmlspecialchars($data['user']['username'] ?? '') ?>" readonly style="background-color: #f9f9f9; color: #777;">
                <small style="font-size: 11px; color: #999; margin-top: 4px; display:block;">Username cannot be changed.</small>
            </div>

            <div class="form-group">
                <label>Employee Number</label>
                <input type="text" value="<?= htmlspecialchars($data['user']['employee_no'] ?? '-') ?>" readonly style="background-color: #f9f9f9; color: #777;">
                <small style="font-size: 11px; color: #999; margin-top: 4px; display:block;">Managed by Administrator.</small>
            </div>

            <div class="form-group">
                <label>Position</label>
                <input type="text" name="position" value="<?= htmlspecialchars($data['user']['position'] ?? '') ?>" placeholder="e.g. Staff IT">
            </div>
        </div>

        <div class="form-divider"></div>

        <div class="form-column">
            <div class="section-header">
                <h2>Password</h2>
                <span class="section-badge">Optional</span>
            </div>

            <div class="section-note">
                Leave these fields blank if you don't want to change your password.
            </div>

            <div class="form-group">
                <label>Current Password</label>
                <input type="password" id="currentPass" name="current_password" autocomplete="off" placeholder="Required to set new password">
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="newPass" name="new_password" autocomplete="new-password" placeholder="Min. 6 characters">

                <div class="password-strength" id="strengthContainer" style="display:none;">
                    <div class="strength-bar" id="strengthBar"></div>
                    <small class="strength-text" id="strengthText"></small>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" id="confirmPass" name="confirm_password" autocomplete="new-password" placeholder="Repeat new password">
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" id="cancelButton">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>

</div>

<div id="toastContainer" class="toast-container"></div>

<script src="<?= BASE_URL ?>/public/js/setting.js" data-base-url="<?= BASE_URL ?>"></script>

<?php
// Load Footer sesuai sesi
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    require_once __DIR__ . '/../layouts/footer.php';
} else {
    require_once __DIR__ . '/../layouts/user_footer.php';
}
?>