<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/complaint.css">

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Manage Complaints</h1>
    </div>

    <div class="data-table" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="complaintTable">
                <thead>
                    <tr style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                        <th width="5%" style="text-align: center; color: #666; font-size: 12px;">NO</th>
                        <th width="15%" style="text-align: center; color: #666; font-size: 12px;">ASSET INFO</th>
                        <th width="15%" style="text-align: center; color: #666; font-size: 12px;">REPORTED BY</th>
                        <th width="20%" style="text-align: center; color: #666; font-size: 12px;">DESCRIPTION</th>
                        <th width="10%" style="text-align: center; color: #666; font-size: 12px;">DATE</th>
                        <th width="10%" style="text-align: center; color: #666; font-size: 12px;">STATUS</th>
                        <th width="15%" style="text-align: center; color: #666; font-size: 12px;">RESPONSE</th>
                        <th width="10%" style="text-align: center; color: #666; font-size: 12px;">ACTION</th>
                    </tr>
                </thead>
                <tbody id="complaintTableBody">
                    <?php if (empty($data['complaints'])): ?>
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 20px; color: #999;">No complaints found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['complaints'] as $index => $item): ?>
                            <?php
                            // Logika status & warna badge
                            $statusLabel = 'Pending Response';
                            $statusClass = 'status-pending';
                            $isResponded = !empty($item['check_status']);

                            if ($isResponded) {
                                $statusLabel = ucfirst(strtolower($item['check_status']));
                                if (strcasecmp($statusLabel, 'Repair') == 0) $statusClass = 'status-warning';
                                elseif (strcasecmp($statusLabel, 'Replace') == 0) $statusClass = 'status-info';
                                elseif (strcasecmp($statusLabel, 'Disposal') == 0) $statusClass = 'status-danger';
                            }

                            $cellStyle = 'text-align: center; vertical-align: middle; font-size: 13px; padding: 10px;';
                            ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="<?= $cellStyle ?>"><?= $index + 1 ?></td>
                                <td style="<?= $cellStyle ?>">
                                    <div style="font-weight: 600; color: #333;"><?= htmlspecialchars($item['equipment_name'] ?? 'Unknown Asset') ?></div>
                                    <span style="font-family: monospace; background: #f5f5f5; padding: 2px 6px; border-radius: 3px; color: #555; font-size: 11px;">
                                        <?= htmlspecialchars($item['asset_number'] ?? '-') ?>
                                    </span>
                                </td>
                                <td style="<?= $cellStyle ?>">
                                    <div style="font-weight: 500;"><?= htmlspecialchars($item['user_name'] ?? 'Unknown User') ?></div>
                                </td>
                                <td style="<?= $cellStyle ?> color: #555;">
                                    <?= htmlspecialchars($item['defect_cause'] ?? $item['defect_description'] ?? '-') ?>
                                </td>
                                <td style="<?= $cellStyle ?> color: #666;">
                                    <?= date('d M Y', strtotime($item['return_date'])) ?>
                                </td>
                                <td style="<?= $cellStyle ?>">
                                    <span class="status-badge <?= $statusClass ?>" style="font-size: 10px; padding: 4px 8px;">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td style="<?= $cellStyle ?> color: #555;">
                                    <?= htmlspecialchars($item['treatment'] ?? '-') ?>
                                </td>
                                <td style="<?= $cellStyle ?>">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn-respond" onclick="window.openResponseModal(<?= $item['return_id'] ?>)"
                                            style="padding: 6px 10px; font-size: 12px;">
                                            <?= $isResponded ? 'Edit' : 'Response' ?>
                                        </button>

                                        <?php if ($isResponded): ?>
                                            <a href="<?= BASE_URL ?>/complaint/exportPdf/<?= $item['return_id'] ?>"
                                                class="btn-pdf" target="_blank" title="Download Report"
                                                style="background-color: #dc3545; color: white; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn-pdf disabled" disabled title="Not available yet"
                                                style="background-color: #e9ecef; color: #adb5bd; padding: 6px 10px; border-radius: 6px; border:none; cursor: not-allowed; font-size: 12px;">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="paginationControls"></div>
    </div>
</div>

<div class="modal-overlay" id="responseModal">
    <div class="modal-content">
        <div class="modal-content-inner">
            <h2 class="modal-title">Admin Response Form</h2>

            <form id="responseForm">
                <input type="hidden" id="return_id" name="return_id">
                <input type="hidden" id="user_id" name="user_id">
                <input type="hidden" id="equipment_id" name="equipment_id">

                <div class="form-row">
                    <div class="form-group">
                        <label>Asset Number</label>
                        <input type="text" id="asset_number" readonly>
                    </div>
                    <div class="form-group">
                        <label>Equipment Name</label>
                        <input type="text" id="equipment_name" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Reported by</label>
                        <input type="text" id="user_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Date of Complaint</label>
                        <input type="text" id="return_date" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label>Time of Complaint</label>
                    <input type="text" id="return_time" readonly>
                </div>

                <div class="form-group">
                    <label>Defect Cause / Chronology</label>
                    <textarea id="defect_cause" rows="3" readonly style="resize: none;"></textarea>
                </div>

                <div class="form-group" id="photo_container_wrapper" style="display: none; background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px dashed #ccc;">
                    <label style="margin-bottom: 10px;">Evidence Photos</label>
                    <div id="evidence_photos" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">

                <div class="form-row">
                    <div class="form-group">
                        <label>Control No <span class="required">*</span></label>
                        <input type="text" id="control_no" name="control_no" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Inspection <span class="required">*</span></label>
                        <input type="date" id="check_date" name="check_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Treatment / Action <span class="required">*</span></label>
                    <textarea id="treatment" name="treatment" rows="3" placeholder="Describe the treatment/action taken..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select id="check_status" name="check_status" required>
                        <option value="">-- Select Status --</option>
                        <option value="repair">Repaired</option>
                        <option value="replace">Replaced</option>
                        <option value="disposal">Disposed</option>
                    </select>
                </div>

                <div class="form-actions-modal">
                    <button type="button" class="btn-cancel" onclick="window.closeResponseModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= BASE_URL ?>/public/js/complaint.js" data-base-url="<?= BASE_URL ?>"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>