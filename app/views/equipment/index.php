<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<?php require_once '../app/views/layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/asset.css">

<style>
    .toolbar-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .search-box {
        position: relative;
        flex: 1;
        max-width: 300px;
    }

    .search-box input {
        width: 100%;
        padding: 10px 10px 10px 35px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }

    .search-box i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
    }

    .btn-group {
        display: flex;
        gap: 10px;
    }

    .btn-cyan {
        background-color: #17a2b8;
        color: white;
        padding: 10px 15px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.3s;
    }

    .btn-cyan:hover {
        background-color: #138496;
    }

    /* Quill Editor */
    .ql-toolbar {
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        background: #f8f9fa;
        border-color: #ddd;
    }

    .ql-container {
        border-bottom-left-radius: 6px;
        border-bottom-right-radius: 6px;
        background: white;
        border-color: #ddd;
        font-family: inherit;
        font-size: 14px;
    }

    .ql-editor {
        min-height: 150px;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Equipment Data</h1>
    </div>

    <div class="toolbar-container">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, asset number...">
        </div>

        <div class="btn-group">
            <button class="btn-cyan" onclick="window.openImportModal()">
                <i class="fas fa-file-csv"></i> Import CSV
            </button>
            <button class="btn-add" onclick="window.openAddModal()">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="equipmentTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Equipment Name</th>
                        <th>Asset Number</th>
                        <th>Serial Number</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Next Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="equipmentTableBody">
                    <tr>
                        <td colspan="9" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="paginationControls"></div>
    </div>
</div>

<div class="modal-overlay" id="equipmentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto; align-items: flex-start !important;">
    <div class="modal-content" style="position: relative; margin: 40px auto !important; max-width: 900px; width: 95%; background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: block;">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 class="modal-title" id="modalTitle">Add New Equipment</h3>
                <button class="modal-close-btn" onclick="window.closeModal()">&times;</button>
            </div>

            <form id="equipmentForm" enctype="multipart/form-data">
                <input type="hidden" id="equipmentId" name="id">

                <div class="form-container" style="display: block;">

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">I. General Information</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Equipment Name <span style="color:red">*</span></label>
                                <input type="text" id="equipment_name" name="equipment_name" required>
                            </div>
                            <div class="form-group">
                                <label>Asset Number (Code) <span style="color:red">*</span></label>
                                <input type="text" id="asset_number" name="asset_number" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Owner</label>
                                <input type="text" id="owner" name="owner">
                            </div>
                            <div class="form-group">
                                <label>Responsible Person</label>
                                <input type="text" id="responsible_person" name="responsible_person">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Merk / Type</label>
                                <input type="text" id="type" name="type">
                            </div>
                            <div class="form-group">
                                <label>Serial Number</label>
                                <input type="text" id="serial_number" name="serial_number">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Manufacturer</label>
                                <input type="text" id="manufacturer" name="manufacturer">
                            </div>
                            <div class="form-group">
                                <label>Purchase Date</label>
                                <input type="date" id="purchase_date" name="purchase_date">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Location</label>
                                <select id="location_id" name="location_id">
                                    <option value="">-- Select Location --</option>
                                    <?php foreach ($data['locations'] as $loc): ?>
                                        <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select id="category_id" name="category_id">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($data['categories'] as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group full-width" style="margin-bottom: 20px;">
                            <label>Equipment Details</label>
                            <input type="text" id="equipment_details" name="equipment_details">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Condition</label>
                                <select id="condition_status" name="condition_status">
                                    <option value="good">Good</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="repair">Repair</option>
                                    <option value="damaged">Damaged</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantity (Unit)</label>
                                <input type="number" id="quantity" name="quantity" min="1" step="1" value="1">
                                <small style="color: #666; font-size: 11px;">*If > 1, asset number automatically appends -01, -02, etc.</small>
                            </div>
                        </div>
                    </div>

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">II. Technical Specifications</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Capacity</label>
                                <input type="text" id="capacity" name="capacity">
                            </div>
                            <div class="form-group">
                                <label>Dimensions</label>
                                <input type="text" id="dimensions" name="dimensions">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Weight (kg)</label>
                                <input type="text" id="weight" name="weight">
                            </div>
                            <div class="form-group">
                                <label>Storage Temperature</label>
                                <input type="text" id="storage_temp" name="storage_temp">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label>Humidity</label>
                            <input type="text" id="humidity" name="humidity">
                        </div>
                    </div>

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">III. Maintenance Information</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Calibration Certificate Number</label>
                                <input type="text" id="calibration_cert_no" name="calibration_cert_no">
                            </div>
                            <div class="form-group">
                                <label>Calibration Date</label>
                                <input type="date" id="calibration_date" name="calibration_date">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Maintenance Frequency (Months)</label>
                                <input type="number" id="maintenance_frequency" name="maintenance_frequency">
                            </div>
                            <div class="form-group">
                                <label>Supporting Vendor</label>
                                <input type="text" id="supporting_vendor" name="supporting_vendor">
                            </div>
                        </div>
                    </div>

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">IV. Usage Steps</div>

                        <div class="form-group full-width">
                            <div id="add-usage-editor"></div>

                            <input type="hidden" id="usage_steps" name="usage_steps">
                        </div>
                    </div>

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">V. Documentation</div>

                        <div class="form-group full-width">
                            <label>Equipment Photos (Can select multiple)</label>
                            <div style="border: 2px dashed #ddd; padding: 15px; border-radius: 6px; text-align: center; background: #fafafa;">
                                <input type="file" id="add-pictures" name="pictures[]" accept="image/*" multiple style="width:100%">
                                <div id="existing-pictures" class="existing-pictures" style="margin-top: 10px; text-align: left;"></div>
                                <div id="add-preview-container" class="existing-pictures" style="margin-top: 10px; text-align: left;"></div>
                            </div>
                        </div>

                        <div class="form-group full-width" style="margin-top: 15px;">
                            <label>Supporting Document (PDF)</label>
                            <input type="file" id="doc_support" name="doc_support" accept="application/pdf" style="border: 1px solid #ddd; padding: 8px; width: 100%; border-radius: 4px;">
                            <small style="color: #e74c3c; font-size: 11px; margin-top: 4px; display: block;">*Max file size: 2MB</small>

                            <div id="current_doc_link" style="margin-top: 5px; font-size: 12px;"></div>
                        </div>
                    </div>

                    <div class="form-actions-modal" style="justify-content: flex-end; padding-top: 15px; border-top: 1px solid #eee;">
                        <button type="button" class="btn-cancel" onclick="window.closeModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save</button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="importModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div class="modal-content" style="max-width: 600px; width: 90%; background: white; border-radius: 8px; padding: 0;">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 class="modal-title">Import Data CSV</h3>
                <button class="modal-close-btn" onclick="window.closeImportModal()">&times;</button>
            </div>

            <form id="importForm" enctype="multipart/form-data">
                <div style="padding: 20px;">

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 20px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px; color: #333;"><i class="fas fa-info-circle" style="color: #17a2b8;"></i> Filling Instructions:</h4>

                        <ul style="font-size: 13px; color: #555; padding-left: 20px; line-height: 1.6; margin-bottom: 15px;">
                            <li>Use the provided <strong>CSV Template</strong>.</li>
                            <li><strong>Location ID</strong> & <strong>Category ID</strong> must be filled with numbers.</li>
                            <li>Date format must be: <code>YYYY-MM-DD</code> (Example: 2024-12-31).</li>
                            <li>If <strong>Quantity > 1</strong>, the system automatically creates a new row with a 2-digit suffix (Example: <code>EQ-001-01</code>).</li>
                            <li>Usage steps are in the last column (can be plain text or HTML).</li>
                            <li>
                                Filling in the <strong>Condition</strong> column (English required):
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-top: 5px; background: #fff; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <div>✅ <code>good</code></div>
                                    <div>⚠️ <code>maintenance</code></div>
                                    <div>❌ <code>damaged</code></div>
                                    <div>🔧 <code>repair</code></div>
                                </div>
                            </li>
                        </ul>

                        <a href="<?= BASE_URL ?>/equipment/downloadTemplate" class="btn-download-template" style="display: block; text-align: center; background: #e3f2fd; color: #0277bd; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 1px dashed #0277bd; transition: all 0.2s;">
                            <i class="fas fa-download"></i> Download CSV Template
                        </a>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Upload CSV File</label>
                        <input type="file" name="file_csv" accept=".csv" required style="width: 100%; border: 2px solid #ddd; padding: 10px; border-radius: 6px; background: white;">
                    </div>
                </div>

                <div class="form-actions-modal" style="justify-content: flex-end; padding: 15px 20px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; gap: 10px;">
                    <button type="button" class="btn-cancel" onclick="window.closeImportModal()">Cancel</button>
                    <button type="submit" class="btn-save" style="background-color: #17a2b8;">
                        <i class="fas fa-upload"></i> Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="qrModal">
    <div class="modal-content">
        <div class="modal-content-inner" style="text-align: center;">
            <div class="modal-header-custom" style="justify-content: center; border:none;">
                <h3 class="modal-title">QR Code</h3>
            </div>
            <div style="padding: 10px;">
                <img id="qrImage" src="" alt="QR Code" style="width: 200px; height: 200px; border: 2px solid #f0f0f0; border-radius: 8px;">
            </div>
            <div style="margin-top: 10px; color: #666; font-size: 13px;">
                <p id="qrEquipmentInfo" style="font-weight: bold;"></p>
            </div>
            <div class="form-actions" style="justify-content: center; margin-top: 20px;">
                <button type="button" class="btn-cancel" onclick="window.closeQRModal()">Close</button>
                <a href="#" id="qrDownloadBtn" class="btn-save" download="qr-code.png">Download</a>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-content confirmation">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 class="modal-title">Confirm Delete</h3>
            </div>

            <div style="text-align: center; padding: 20px 0;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #e74c3c; margin-bottom: 15px;"></i>
                <p style="font-size: 15px; color: #555; margin: 0;">Are you sure you want to delete this equipment?
            </div>

            <div class="form-actions-modal" style="justify-content: center; gap: 15px;">
                <button type="button" class="btn-cancel" onclick="window.closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-confirm-delete" onclick="window.confirmDelete()">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script src="<?= BASE_URL ?>/public/js/equipment.js" data-base-url="<?= BASE_URL ?>"></script>

<?php require_once '../app/views/layouts/footer.php'; ?>