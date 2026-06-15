<?php require_once '../app/views/layouts/user_header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/borrow.css">
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<div class="main-content">
    <div class="borrow-container">

        <div class="borrow-header">
            <h2><i class="fas fa-box-open"></i> Borrow Equipment</h2>
            <div style="background:rgba(255,255,255,0.2); padding:5px 15px; border-radius:20px; font-size:13px;">
                <?= date('l, d F Y') ?>
            </div>
        </div>

        <div class="step-indicator">
            <div class="step-item active" id="ind-1">
                <div class="step-circle">1</div>
                <span>Scan ID</span>
            </div>
            <div class="step-line" id="line-1"></div>
            <div class="step-item" id="ind-2">
                <div class="step-circle">2</div>
                <span>Job Details</span>
            </div>
            <div class="step-line" id="line-2"></div>
            <div class="step-item" id="ind-3">
                <div class="step-circle">3</div>
                <span>Equipment and Submit</span>
            </div>
        </div>

        <div class="borrow-body">

            <div class="step-content active" id="step-1">
                <div class="center-box">
                    <h3 style="margin-bottom:15px; color:#333;">Identify Yourself</h3>

                    <div id="reader-user" style="display:none;"></div>

                    <div id="user-placeholder">
                        <i class="fas fa-id-card" style="font-size:50px; margin-bottom:10px; display:block;"></i>
                        <span>Camera Not Active</span>
                    </div>

                    <div style="display:flex; justify-content:center; gap:15px; margin-bottom:20px;">
                        <button class="scan-btn btn-primary" id="btn-start-user" onclick="startUserCamera()">
                            <i class="fas fa-camera"></i> Activate Camera
                        </button>
                        <button class="scan-btn btn-secondary" id="btn-stop-user" onclick="stopUserCamera()" style="display:none;">
                            Stop
                        </button>
                    </div>

                    <div style="max-width:350px; margin:0 auto;">
                        <input type="text" id="user_qr_input" class="big-input" inputmode="none" placeholder="Scan employee ID..." style="text-align:center;" autofocus>
                        <div id="user_scan_msg" style="margin-top:10px; font-weight:bold; height:20px; color:#BB1B1B;"></div>
                    </div>
                </div>
            </div>

            <div class="step-content" id="step-2">
                <div class="step-2-container">

                    <div id="welcome-msg" class="welcome-banner">
                        <i class="fas fa-user-check" style="font-size:24px;"></i>
                        <div>
                            <strong>Welcome!</strong> <span id="user-name-display">Please fill in the details.</span>
                        </div>
                    </div>

                    <h3 style="margin-bottom:20px; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">Job Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>No JD (Job Description) <span style="color:red">*</span></label>
                            <input type="text" id="no_jd" class="big-input">
                        </div>

                        <div class="form-group">
                            <label>Client <span style="color:red">*</span></label>
                            <input type="text" id="client" class="big-input">
                        </div>

                        <div class="form-group">
                            <label>Location <span style="color:red">*</span></label>
                            <input type="text" id="location" class="big-input">
                        </div>

                        <div class="form-group">
                            <label>Working Days <span style="color:red">*</span></label>
                            <input type="number" id="working_days" class="big-input" min="1" value="1">
                        </div>
                    </div>

                    <div class="footer-actions">
                        <button class="scan-btn btn-secondary" onclick="goToStep(1)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button class="scan-btn btn-primary" onclick="goToStep(3)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="step-content" id="step-3">
                <div class="split-container">

                    <div class="split-left">
                        <h4 style="margin-top:0; margin-bottom:15px;">Scan Equipment</h4>

                        <div id="reader-equip" style="display:none;"></div>

                        <div id="equip-placeholder">
                            <i class="fas fa-qrcode" style="font-size:40px; margin-bottom:10px; color:#ccc;"></i>
                            <span>Camera Not Active</span>
                        </div>

                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <button class="scan-btn btn-primary" id="btn-start-equip" onclick="startEquipCamera()" style="flex:1; justify-content:center;">
                                <i class="fas fa-camera"></i> Activate Camera
                            </button>
                            <button class="scan-btn btn-secondary" id="btn-stop-equip" onclick="stopEquipCamera()" style="display:none; flex:1; justify-content:center;">
                                Stop
                            </button>
                        </div>

                        <input type="text" id="equip_qr_input" class="big-input" inputmode="none" placeholder="Scan Asset Code..." style="text-align:center;">
                        <div id="equip_scan_msg" style="margin-top:10px; color:#BB1B1B; font-weight:bold; min-height:20px;"></div>
                    </div>

                    <div class="split-right">
                        <div class="list-header-area">
                            <h3 style="margin:0;"><i class="fas fa-list-ul"></i> Equipment Scanned</h3>
                            <span id="item-count" style="background:#BB1B1B; color:white; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700;">0 Item</span>
                        </div>

                        <div class="table-wrapper">
                            <table class="equip-table">
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Code</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="equip-list-body">
                                </tbody>
                            </table>
                            <div id="empty-list-msg" style="text-align:center; padding:40px 20px; color:#999;">
                                <i class="fas fa-box-open" style="font-size:40px; margin-bottom:10px; display:block; opacity:0.3;"></i>
                                No items scanned yet.
                            </div>
                        </div>

                        <div class="footer-actions" style="margin-top:auto;">
                            <button class="scan-btn btn-secondary" onclick="goToStep(2)">
                                Back
                            </button>
                            <button class="scan-btn btn-success" onclick="submitBorrowing()">
                                <i class="fas fa-check-circle"></i> Confirm Borrowing
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<div id="confirmModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <div style="font-size: 50px; color: #27ae60; margin-bottom: 15px;">
            <i class="fas fa-question-circle"></i>
        </div>
        <h3 style="margin-bottom: 10px; color: #333;">Confirm Borrowing</h3>
        <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">
            Are you sure you want to borrow <br>
            <strong id="confirmItemCount" style="color: #27ae60; font-size: 20px;">0</strong> item(s)?
        </p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button type="button" class="scan-btn btn-secondary" onclick="closeConfirmModal()" style="flex: 1; justify-content: center;">Cancel</button>
            <button type="button" class="scan-btn btn-success" onclick="executeBorrowing()" style="flex: 1; justify-content: center; font-size: 14px;">Yes, Borrow</button>
        </div>
    </div>
</div>

<input type="hidden" id="hidden_user_id">

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/public/js/borrow.js"></script>

<?php require_once '../app/views/layouts/user_footer.php'; ?>