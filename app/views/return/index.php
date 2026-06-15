<?php require_once '../app/views/layouts/user_header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/return.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/modal.css">
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<div class="main-content">
    <div class="return-container">

        <div class="return-header">
            <h2><i class="fas fa-undo"></i> Equipment Return</h2>
            <div style="background:rgba(255,255,255,0.2); padding:5px 15px; border-radius:20px; font-size:13px;">
                <?= date('l, d F Y') ?>
            </div>
        </div>

        <div class="step-indicator">
            <div class="step-item active" id="step-ind-1">
                <div class="step-circle">1</div>
                <span>Scan User</span>
            </div>
            <div class="step-line" id="line-1"></div>
            <div class="step-item" id="step-ind-2">
                <div class="step-circle">2</div>
                <span>Return Items</span>
            </div>
        </div>

        <div class="return-body">

            <div id="step-content-1" class="step-content active">
                <div class="center-box">
                    <h3 style="margin-bottom:15px; color:#333;">Identify Borrower</h3>
                    <p style="color:#777; margin-bottom:25px;">Scan User QR Code to load borrowed items.</p>

                    <div id="reader-user" style="display:none;"></div>

                    <div id="user-placeholder">
                        <i class="fas fa-id-card" style="font-size:60px; margin-bottom:15px; display:block;"></i>
                        <span>Camera Inactive</span>
                    </div>

                    <div style="display:flex; justify-content:center; gap:15px; margin-bottom:25px;">
                        <button class="scan-btn btn-primary" id="btn-start-user" onclick="startUserCamera()">
                            <i class="fas fa-camera"></i> Activate Camera
                        </button>
                        <button class="scan-btn btn-secondary" id="btn-stop-user" onclick="stopUserCamera()" style="display:none;">
                            Stop
                        </button>
                    </div>

                    <div style="max-width:400px; margin:0 auto;">
                        <input type="text" id="user_qr_return" class="form-input" inputmode="none" placeholder="Scan Employee ID..." autofocus>
                        <div id="return_msg" style="margin-top:15px; font-weight:bold; height:20px; color:#c0392b;"></div>
                    </div>
                </div>
            </div>

            <div id="step-content-2" class="step-content hidden">

                <div class="user-banner">
                    <div>
                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; opacity:0.7;">Borrower</div>
                        <div id="user_name_disp" style="font-size:18px; font-weight:bold;">Loading...</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; opacity:0.7;">Employee ID</div>
                        <div id="user_emp_disp" style="font-size:18px; font-weight:bold;">...</div>
                    </div>
                </div>

                <div class="split-container">

                    <div class="split-left">
                        <h4 style="margin-top:0; margin-bottom:15px;">Scan Item to Return</h4>

                        <div id="reader-item" style="display:none;"></div>

                        <div id="item-placeholder">
                            <i class="fas fa-qrcode" style="font-size:50px; margin-bottom:10px; color:#ccc;"></i>
                            <span>Camera Off</span>
                        </div>

                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <button class="scan-btn btn-primary" id="btn-start-item" onclick="startItemCamera()" style="flex:1; justify-content:center;">
                                <i class="fas fa-camera"></i> Activate Camera
                            </button>
                            <button class="scan-btn btn-secondary" id="btn-stop-item" onclick="stopItemCamera()" style="display:none; flex:1; justify-content:center;">
                                Stop
                            </button>
                        </div>

                        <input type="text" id="item_qr_return" class="form-input" inputmode="none" placeholder="Scan Asset QR Code here...">
                    </div>

                    <div class="split-right">
                        <h3 style="margin-top:0; margin-bottom:15px;"><i class="fas fa-list-ul"></i> Borrowed Items List</h3>

                        <div class="table-wrapper">
                            <table class="return-table">
                                <thead>
                                    <tr>
                                        <th>Asset Name</th>
                                        <th>Code</th>
                                        <th>Condition</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="return-table-body">
                                </tbody>
                            </table>
                        </div>

                        <div class="footer-actions">
                            <button class="scan-btn btn-secondary" onclick="location.reload()">
                                Cancel
                            </button>
                            <button id="btnSubmitReturn" class="scan-btn btn-submit-step" onclick="initiateReturnProcess()">
                                <i class="fas fa-check-circle"></i> Confirm Return
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<div id="defectModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-content-inner">
            <div class="modal-header danger" style="color: #c0392b; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> Defect Report
            </div>
            <p style="margin-bottom:20px; color:#555; line-height: 1.6;">
                You marked <strong id="defectAssetName" style="color:#333;">Asset</strong> as
                <strong id="defectStatus" style="color:#c0392b; text-transform:uppercase;">DAMAGED</strong>.<br>
                Please describe the cause and upload photos of the defect.
            </p>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight: 600; color:#333; margin-bottom: 8px; display:block;">Defect Cause / Chronology <span style="color:red;">*</span></label>
                <textarea id="defectCauseInput" class="form-input" rows="3" style="text-align:left; resize:vertical;" placeholder="Example: Item fell from the table..."></textarea>
            </div>

            <div id="photoUploadGroup" class="form-group" style="margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px dashed #ccc;">
                <label style="font-weight: 600; color:#333; margin-bottom: 8px; display:block;">
                    <i class="fas fa-camera"></i> Photos of Defect <span style="color:red;">*</span>
                </label>
                <input type="file" id="defectPhotosInput" accept="image/*" multiple class="form-input" onchange="handleDefectPhotos(this)" style="background: white; padding: 5px;">
                <small style="color:#888; font-size:11px; display:block; margin-top:5px;">Please upload at least 1 evidence photo.</small>
                <div id="defectPhotosPreview" style="display:flex; gap:10px; margin-top:10px; flex-wrap:wrap;"></div>
            </div>

            <div class="footer-actions">
                <button type="button" class="scan-btn btn-secondary" onclick="closeDefectModal()">Cancel</button>
                <button type="button" class="scan-btn btn-primary" onclick="saveDefectInfo()" style="background:#c0392b;">Save Report</button>
            </div>
        </div>
    </div>
</div>

<div id="confirmModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <div class="modal-content-inner">
            <div style="font-size: 50px; color: #27ae60; margin-bottom: 15px;">
                <i class="fas fa-question-circle"></i>
            </div>
            <h3 style="margin-bottom: 10px; color: #333;">Confirm Return</h3>
            <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">
                Are you sure you want to return <br>
                <strong id="confirmItemCount" style="color: #27ae60; font-size: 20px;">0</strong> item(s)?
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button type="button" class="scan-btn btn-secondary" onclick="closeConfirmModal()" style="flex: 1; justify-content: center;">Cancel</button>
                <button type="button" class="scan-btn btn-primary" onclick="executeSubmission()" style="flex: 1; justify-content: center;">Yes, Return</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="hidden_user_id_return">

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/public/js/return.js"></script>

<?php require_once '../app/views/layouts/user_footer.php'; ?>