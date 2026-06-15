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

    .form-container {
        display: block !important;
    }

    .log-section {
        display: block;
        margin-bottom: 25px;
    }

    .log-title {
        font-size: 16px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
        border-bottom: 2px solid #B04B4B;
        padding-bottom: 5px;
    }

    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
    }

    .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        width: 100%;
    }

    .upload-area {
        border: 2px dashed #ddd;
        padding: 15px;
        border-radius: 6px;
        text-align: center;
        background: #fafafa;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Land & Building Data</h1>
    </div>

    <div class="toolbar-container">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search name, code, location..." onkeyup="LandBuildingManager.handleSearch(this.value)">
        </div>
        <div class="btn-group">
            <button class="btn-cyan" onclick="window.openImportModal()">
                <i class="fas fa-file-csv"></i> Import CSV
            </button>
            <button class="btn-add" onclick="LandBuildingManager.add()">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="landBuildingTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Asset Code</th>
                        <th>Asset Name</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>User</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="9" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="paginationControls"></div>
    </div>
</div>

<div class="modal-overlay" id="modalLandBuilding" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto; align-items: flex-start !important;">
    <div class="modal-content" style="position: relative; margin: 40px auto !important; max-width: 900px; width: 95%; background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: block;">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 class="modal-title" id="modalTitle">Add New Land & Building</h3>
                <button class="modal-close-btn" onclick="LandBuildingManager.closeModal()">&times;</button>
            </div>

            <form id="formLandBuilding" enctype="multipart/form-data">
                <input type="hidden" name="id" id="id">

                <div class="form-container" style="display: block;">

                    <div class="log-section">
                        <div class="log-title">I. General Information</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Asset Code <span style="color:red">*</span></label>
                                <input type="text" name="asset_code" id="asset_code" required>
                            </div>
                            <div class="form-group">
                                <label>Asset Name <span style="color:red">*</span></label>
                                <input type="text" name="asset_name" id="asset_name" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Responsible Person</label>
                                <input type="text" name="responsible_person" id="responsible_person">
                            </div>
                            <div class="form-group">
                                <label>User</label>
                                <select name="user_id" id="user_id">
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
                                <label>Location</label>
                                <select name="location_id" id="location_id">
                                    <option value="">-- Select Location --</option>
                                    <?php if (!empty($data['locations'])): foreach ($data['locations'] as $l) : ?>
                                            <option value="<?= $l['id']; ?>"><?= $l['location_name']; ?></option>
                                    <?php endforeach;
                                    endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" id="category_id">
                                    <option value="">-- Select Category --</option>
                                    <?php if (!empty($data['categories'])): foreach ($data['categories'] as $c) : ?>
                                            <option value="<?= $c['id']; ?>"><?= $c['category_name']; ?></option>
                                    <?php endforeach;
                                    endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Certificate Number</label>
                                <input type="text" name="certificate_number" id="certificate_number">
                            </div>
                            <div class="form-group">
                                <label>Certificate Date</label>
                                <input type="date" name="certificate_date" id="certificate_date">
                            </div>
                        </div>

                        <div class="form-group full-width" style="margin-bottom: 20px;">
                            <label>Full Address</label>
                            <textarea name="address" id="address" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="log-section">
                        <div class="log-title">II. Technical Specifications</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Surface Area</label>
                                <input type="text" name="surface_area" id="surface_area">
                            </div>
                            <div class="form-group">
                                <label>Intended Use</label>
                                <input type="text" name="intended_use" id="intended_use">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Condition</label>
                                <input type="text" name="condition" id="condition">
                            </div>
                            <div class="form-group">
                                <label>Usage Status</label>
                                <select id="usage_status" name="usage_status" class="form-control">
                                    <option value="Used">Used</option>
                                    <option value="Not Used">Not Used</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="log-section">
                        <div class="log-title">Upload Documents & Photos</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Site Plan (Image/PDF)</label>
                                <input type="file" id="site_plan_path" name="site_plan_path" accept=".jpg,.jpeg,.png,.pdf">
                                <small style="color:#e74c3c; font-size:11px;">*Image will be compressed, PDF Max 2MB</small>
                                <div id="current_site_plan" style="margin-top:5px; font-size:12px;"></div>
                            </div>
                            <div class="form-group">
                                <label>Supporting Document (PDF)</label>
                                <input type="file" id="document_path" name="document_path" accept="application/pdf">
                                <small style="color:#e74c3c; font-size:11px;">*Max file size: 2MB</small>
                                <div id="current_document" style="margin-top:5px; font-size:12px;"></div>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label>Asset Photos</label>
                            <input type="file" id="photos" name="photos[]" multiple accept="image/*">
                            <div id="preview_photos"></div>
                        </div>
                    </div>

                    <div class="form-actions-modal" style="justify-content: flex-end; padding-top: 15px; border-top: 1px solid #eee; display: flex; gap: 10px;">
                        <button type="button" class="btn-cancel" onclick="LandBuildingManager.closeModal()">Cancel</button>
                        <button type="button" class="btn-save" onclick="document.getElementById('formLandBuilding').requestSubmit()">Save Data</button>
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
                            <li><strong>Location, Category and User</strong> columns must be IDs (Numbers).</li>
                            <li>Date format: <code>YYYY-MM-DD</code>.</li>
                            <li>Status: <code style="background:#e2e3e5; padding:2px 4px; border-radius:3px;">used</code> or <code style="background:#e2e3e5; padding:2px 4px; border-radius:3px;">not_used</code>.</li>
                        </ul>
                        <a href="<?= BASE_URL ?>/landbuilding/downloadTemplate" style="display: block; text-align: center; background: #e3f2fd; color: #0277bd; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 1px dashed #0277bd; transition: all 0.2s;">
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
                <button type="button" class="btn-cancel" onclick="LandBuildingManager.closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-confirm-delete" onclick="LandBuildingManager.confirmDelete()" style="background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="<?= BASE_URL ?>/public/js/land_building.js" data-base-url="<?= BASE_URL ?>"></script>

<?php require_once '../app/views/layouts/footer.php'; ?>