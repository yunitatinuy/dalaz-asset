<?php require_once '../app/views/layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/asset.css">

<style>
    .toolbar-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 15px;
        flex-wrap: wrap;
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
        background: #17a2b8;
        color: white;
        padding: 10px 15px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
    }

    .btn-cyan:hover {
        background: #138496;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Vehicle Data</h1>
    </div>

    <div class="toolbar-container">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by license plate, code..." onkeyup="VehicleManager.handleSearch(this.value)">
        </div>
        <div class="btn-group">
            <button class="btn-cyan" onclick="window.openImportModal()">
                <i class="fas fa-file-csv"></i> Import CSV
            </button>
            <button class="btn-add" onclick="VehicleManager.add()">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="vehicleTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Asset Code</th>
                        <th>Type</th>
                        <th>Brand / Type</th>
                        <th>License Plate</th>
                        <th>User</th>
                        <th>Condition</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="8" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="paginationControls"></div>
    </div>
</div>

<div class="modal-overlay" id="modalVehicle" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto; align-items: flex-start !important;">
    <div class="modal-content" style="position: relative; margin: 40px auto !important; max-width: 900px; width: 95%; background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: block;">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 class="modal-title" id="modalTitle">Add New Vehicle</h3>
                <button class="modal-close-btn" onclick="VehicleManager.closeModal()">&times;</button>
            </div>

            <form id="formVehicle" enctype="multipart/form-data">
                <input type="hidden" id="id" name="id">
                <div class="form-container" style="display: block;">

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">I. General Information</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Asset Code <span style="color:red">*</span></label>
                                <input type="text" id="asset_code" name="asset_code" required>
                            </div>
                            <div class="form-group">
                                <label>Vehicle Type</label>
                                <input type="text" id="vehicle_type" name="vehicle_type">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Owner</label>
                                <input type="text" id="owner" name="owner">
                            </div>
                            <div class="form-group">
                                <label>User</label>
                                <select id="user_id" name="user_id">
                                    <option value="">-- Select User --</option>
                                    <?php if (!empty($data['users'])): foreach ($data['users'] as $u) : ?>
                                            <option value="<?= $u['id']; ?>"><?= $u['full_name']; ?></option>
                                    <?php endforeach;
                                    endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Brand / Type</label>
                                <input type="text" id="brand" name="brand">
                            </div>
                            <div class="form-group">
                                <label>License Plate</label>
                                <input type="text" id="license_plate" name="license_plate" style="text-transform: uppercase;">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Year</label>
                                <input type="number" id="year" name="year">
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
                                    <?php if (!empty($data['locations'])): foreach ($data['locations'] as $l) : ?>
                                            <option value="<?= $l['id']; ?>"><?= $l['location_name']; ?></option>
                                    <?php endforeach;
                                    endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select id="category_id" name="category_id">
                                    <option value="">-- Select Category --</option>
                                    <?php if (!empty($data['categories'])): foreach ($data['categories'] as $c) : ?>
                                            <option value="<?= $c['id']; ?>"><?= $c['category_name']; ?></option>
                                    <?php endforeach;
                                    endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group full-width" style="margin-bottom: 20px;">
                            <label>Equipment Details</label>
                            <textarea id="equipment_details" name="equipment_details" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">II. Technical Specifications</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>BPKB Number</label>
                                <input type="text" id="bpkb_number" name="bpkb_number">
                            </div>
                            <div class="form-group">
                                <label>STNK Number</label>
                                <input type="text" id="stnk_number" name="stnk_number">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Chassis Number</label>
                                <input type="text" id="chassis_number" name="chassis_number">
                            </div>
                            <div class="form-group">
                                <label>Engine Number</label>
                                <input type="text" id="engine_number" name="engine_number">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Condition</label>
                                <input type="text" id="condition" name="condition">
                            </div>
                            <div class="form-group">
                                <label>Maintenance Frequency</label>
                                <input type="text" id="maintenance_frequency" name="maintenance_frequency">
                            </div>
                        </div>
                    </div>

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">Upload Documents & Photos</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Upload BPKB (PDF/Image)</label>
                                <input type="file" id="bpkb_path" name="bpkb_path" accept=".jpg,.jpeg,.png,.pdf">
                                <small style="color:#e74c3c; font-size:11px;">*PDF Max 2MB</small>
                                <div id="link_bpkb" style="margin-top:5px;"></div>
                            </div>
                            <div class="form-group">
                                <label>Upload STNK (PDF/Image)</label>
                                <input type="file" id="stnk_path" name="stnk_path" accept=".jpg,.jpeg,.png,.pdf">
                                <small style="color:#e74c3c; font-size:11px;">*PDF Max 2MB</small>
                                <div id="link_stnk" style="margin-top:5px; font-size:12px;"></div>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label>Vehicle Photos</label>
                            <input type="file" id="photos" name="photos[]" multiple accept="image/*">
                            <div id="preview_photos" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;"></div>
                            <div id="existing-pictures" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;"></div>
                        </div>
                    </div>

                    <div class="form-actions-modal" style="justify-content: flex-end; padding-top: 15px; border-top: 1px solid #eee; display: flex; gap: 10px;">
                        <button type="button" class="btn-cancel" onclick="VehicleManager.closeModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save Data</button>
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
                <h3 class="modal-title">Import CSV Data</h3>
                <button class="modal-close-btn" onclick="window.closeImportModal()">&times;</button>
            </div>

            <form id="importForm" enctype="multipart/form-data">
                <div style="padding: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 20px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px; color: #333;"><i class="fas fa-info-circle" style="color: #17a2b8;"></i> Import Instructions:</h4>
                        <ul style="font-size: 13px; color: #555; padding-left: 20px; line-height: 1.6; margin-bottom: 15px;">
                            <li>Use the provided <strong>CSV Template</strong>.</li>
                            <li><strong>Date Format:</strong> <code>YYYY-MM-DD</code>.</li>
                            <li><strong>Location, Category and User</strong> columns must be IDs (Numbers).</li>
                        </ul>
                        <a href="<?= BASE_URL ?>/vehicle/downloadTemplate" class="btn-download-template" style="display: block; text-align: center; background: #e3f2fd; color: #0277bd; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 1px dashed #0277bd; transition: all 0.2s;">
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

<div class="modal-overlay" id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div class="modal-content confirmation" style="max-width: 400px; text-align: center; padding: 0; background: white; border-radius: 8px;">
        <div class="modal-content-inner">
            <div class="modal-header-custom" style="justify-content: center; border-bottom: 1px solid #f0f0f0;">
                <h3 class="modal-title">Confirm Delete</h3>
            </div>
            <div style="padding: 30px 20px;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #e74c3c; margin-bottom: 15px;"></i>
                <p style="font-size: 15px; color: #555; margin: 0;">Are you sure you want to delete this data?</p>
            </div>
            <div class="form-actions-modal" style="justify-content: center; gap: 15px; padding-bottom: 20px;">
                <button type="button" class="btn-cancel" onclick="VehicleManager.closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-confirm-delete" onclick="VehicleManager.confirmDelete()" style="background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="<?= BASE_URL ?>/public/js/vehicle.js" data-base-url="<?= BASE_URL ?>"></script>

<?php require_once '../app/views/layouts/footer.php'; ?>