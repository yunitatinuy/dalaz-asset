<?php require_once '../app/views/layouts/header.php'; ?>

<div class="page-header" style="margin-bottom: 15px;">
    <h1 class="page-title">User Data</h1>
</div>

<div class="toolbar-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
    <div class="search-box" style="position: relative; flex: 1; max-width: 300px;">
        <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #888;"></i>
        <input type="text" id="searchInput" placeholder="Search Users..." style="width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #ddd; border-radius: 6px;">
    </div>
    <div class="btn-group" style="display: flex; gap: 10px;">
        <button class="btn-cyan" onclick="UserManager.openImportModal()" style="background: #17a2b8; color: white; padding: 10px 15px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
            <i class="fas fa-file-csv"></i> Import CSV
        </button>
        <button class="btn-add" onclick="UserManager.addUser()">
            <i class="fas fa-plus"></i> Add
        </button>
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

<!-- Modal Import CSV Khusus User -->
<div class="modal-overlay" id="importModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div class="modal-content" style="max-width: 600px; width: 90%; background: white; border-radius: 8px; padding: 0;">
        <div class="modal-content-inner">
            <div class="modal-header-custom" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee;">
                <h3 class="modal-title" style="margin: 0; font-size: 18px; color: #333;">Import User Data (CSV)</h3>
                <button type="button" class="modal-close-btn" onclick="UserManager.closeModal('importModal')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #aaa;">&times;</button>
            </div>

            <form id="importForm" enctype="multipart/form-data">
                <div style="padding: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 20px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px; color: #333;"><i class="fas fa-info-circle" style="color: #17a2b8;"></i> Import Instructions:</h4>

                        <ul style="font-size: 13px; color: #555; padding-left: 20px; line-height: 1.6; margin-bottom: 15px;">
                            <li>Use the provided <strong>CSV Template</strong>.</li>
                            <li><strong>Full Name, Employee Number, and Role</strong> are required fields.</li>
                            <li>Valid Roles are: <strong>user</strong> or <strong>admin</strong>.</li>
                            <li>If the Role is 'admin', you MUST fill in the <strong>Username, Email, and Password</strong> columns.</li>
                            <li>If the Role is 'user', leave the Username, Email, and Password columns empty.</li>
                        </ul>

                        <a href="javascript:void(0)" onclick="UserManager.downloadTemplate()" class="btn-download-template" style="display: block; text-align: center; background: #e3f2fd; color: #0277bd; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 1px dashed #0277bd; transition: all 0.2s;">
                            <i class="fas fa-download"></i> Download CSV Template
                        </a>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Upload the Completed CSV File</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required style="width: 100%; border: 2px solid #ddd; padding: 10px; border-radius: 6px; background: white;">
                        <small style="color: #888; font-size: 12px; margin-top: 5px; display: block;">The file format must be .csv</small>
                    </div>
                </div>

                <div class="form-actions-modal" style="justify-content: flex-end; padding: 15px 20px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; gap: 10px;">
                    <button type="button" class="btn-cancel" onclick="UserManager.closeModal('importModal')" style="padding: 8px 15px; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-save" style="background-color: #17a2b8; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-upload"></i> Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>