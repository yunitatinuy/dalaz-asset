<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">User Data</h1>
        <div class="page-actions">
            <button class="btn-add" onclick="UserManager.addUser()">+ Add</button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="userTable">
                <thead>
                    <tr>
                        <th width="5%">NO</th>
                        <th width="10%">USER ID</th>
                        <th>EMPLOYEE NUMBER</th>
                        <th>FULL NAME</th>
                        <th>POSITION</th>
                        <th>ROLE</th>
                        <th width="15%">ACTION</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr>
                        <td colspan="7" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="paginationControls"></div>
    </div>
</div>

<div class="modal-overlay" id="userModal">
    <div class="modal-content">
        <div class="modal-content-inner">
            <div class="modal-header" id="modalTitle">Add New User</div>

            <form id="userForm" enctype="multipart/form-data">
                <input type="hidden" id="userId" name="id">

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" id="position" name="position" placeholder="Enter position">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Employee Number <span class="required">*</span></label>
                        <input type="text" id="employee_no" name="employee_no" required placeholder="Enter employee number">
                    </div>
                    <div class="form-group">
                        <label>Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div style="border-top: 2px solid #f0f0f0; margin: 20px 0;"></div>

                <div id="credentialFields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username <span class="required">*</span></label>
                            <input type="text" id="username" name="username" placeholder="Enter username" autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label>Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="admin@example.com" autocomplete="off">
                            <small style="font-size: 10px; color: #888;">For system notifications (calibration alerts, etc)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label>Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="new-password">
                            <small style="display: block; margin-top: 5px; font-size: 11px; color: #666;">
                                Leave blank to keep current password (when editing)
                            </small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Profile Picture (Optional)</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Signature (Optional)</label>
                    <input type="file" id="signature" name="signature" accept="image/png, image/jpeg">
                    <small style="display: block; margin-top: 5px; font-size: 11px; color: #666;">
                        * Recommended: PNG file with transparent background for reports.
                    </small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="UserManager.closeModal('userModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="qrModal">
    <div class="modal-content qr-modal">
        <div class="modal-content-inner">
            <h2 class="modal-header">QR Code</h2>
            <div class="qr-container">
                <img id="qrImage" src="" alt="QR Code">
            </div>
            <p class="qr-label" id="qrLabel">Loading...</p>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="UserManager.closeModal('qrModal')">Close</button>
                <button type="button" class="btn-save" id="downloadQRBtn">Download</button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-content confirmation">
        <div class="modal-content-inner">
            <div class="modal-header">Are you sure?</div>
            <p style="text-align: center; margin: 20px 0; color: #666;">This user will be permanently deleted.</p>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="UserManager.closeModal('deleteModal')">Cancel</button>
                <button type="button" class="btn-ok btn-delete" onclick="UserManager.confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<?php require_once '../app/views/layouts/footer.php'; ?>