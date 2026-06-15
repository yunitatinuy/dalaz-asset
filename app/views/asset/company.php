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
        <h1 class="page-title">Office Equipment Data</h1>
    </div>

    <div class="toolbar-container">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search name, code, merk..." onkeyup="AssetManager.handleSearch(this.value)">
        </div>
        <div class="btn-group">
            <button class="btn-cyan" onclick="window.openImportModal()">
                <i class="fas fa-file-csv"></i> Import CSV
            </button>
            <a href="<?= BASE_URL ?>/asset/damaged" class="btn-view">
                <i class="fas fa-exclamation-triangle"></i> View Damaged
            </a>
            <button class="btn-add" onclick="window.openAddModal()">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="assetTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Asset Name</th>
                        <th>Asset Code</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Condition</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="paginationControls"></div>
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
                            <li><strong>Location ID</strong> and <strong>Category ID</strong> columns must be filled with <strong>NUMBERS</strong> (Check Location/Category menu for IDs).</li>
                            <li>Date Format: <code>YYYY-MM-DD</code> (Example: 2024-01-31).</li>
                            <li>If <strong>Quantity > 1</strong>, the system automatically creates new rows with sequential codes (Example: <code>AST-001-01</code>, <code>AST-001-02</code>).</li>
                            <li><strong>Images & Documents</strong> cannot be imported (add manually via Edit).</li>
                            <li>Special symbols (±, °) are supported.</li>
                            <li>
                                Fill the <strong>Condition</strong> column (Must be in English lowercase):
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-top: 5px; background: #fff; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <div>✅ <code>good</code></div>
                                    <div>⚠️ <code>maintenance</code></div>
                                    <div>❌ <code>damaged</code></div>
                                    <div>🔧 <code>repair</code></div>
                                </div>
                            </li>
                        </ul>

                        <a href="<?= BASE_URL ?>/asset/downloadTemplate" class="btn-download-template" style="display: block; text-align: center; background: #e3f2fd; color: #0277bd; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 1px dashed #0277bd; transition: all 0.2s;">
                            <i class="fas fa-download"></i> Download CSV Template
                        </a>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Upload the Completed CSV File</label>
                        <input type="file" name="csv_file" accept=".csv" required style="width: 100%; border: 2px solid #ddd; padding: 10px; border-radius: 6px; background: white;">
                        <small style="color: #888; font-size: 12px; margin-top: 5px; display: block;">The file format must be .csv</small>
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

<div class="modal-overlay" id="assetModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto; align-items: flex-start !important;">
    <div class="modal-content" style="position: relative; margin: 40px auto !important; max-width: 900px; width: 95%; background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: block;">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 class="modal-title" id="modalTitle">Add New Office Equipment</h3>
                <button class="modal-close-btn" onclick="window.closeModal()">&times;</button>
            </div>

            <form id="assetForm" enctype="multipart/form-data">
                <input type="hidden" id="assetId" name="id">
                <div class="form-container" style="display: block;">
                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">I. General Information</div>

                        <div class="form-row">
                            <div class="form-group"><label>Asset Name <span style="color:red">*</span></label><input type="text" id="assetName" name="assetName" required></div>
                            <div class="form-group"><label>Asset Code (Number) <span style="color:red">*</span></label><input type="text" id="assetCode" name="assetCode" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Owner</label><input type="text" id="owner" name="owner"></div>
                            <div class="form-group"><label>Responsible Person</label><input type="text" id="responsible_person" name="responsible_person"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>User</label><input type="text" id="assigned_to" name="assigned_to"></div>
                            <div class="form-group"><label>Brand</label><input type="text" id="brand" name="brand"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Serial Number</label><input type="text" id="serial_number" name="serial_number"></div>
                            <div class="form-group"><label>Purchase Date</label><input type="date" id="purchase_date" name="purchase_date"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Location</label><select id="location" name="location">
                                    <option value="">-- Choose Location --</option><?php foreach ($data['locations'] as $l): ?><option value="<?= $l['id'] ?>"><?= $l['location_name'] ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="form-group"><label>Category</label><select id="category" name="category">
                                    <option value="">-- Choose Category --</option><?php foreach ($data['categories'] as $c): ?><option value="<?= $c['id'] ?>"><?= $c['category_name'] ?></option><?php endforeach; ?>
                                </select></div>
                        </div>
                        <div class="form-group full-width" style="margin-bottom: 20px;"><label>Detail</label><textarea id="details" name="details" rows="2"></textarea></div>
                        <div class="form-row">
                            <div class="form-group"><label>Status</label><select id="status" name="status">
                                    <option value="good">Good</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="repair">Repair</option>
                                    <option value="damaged">Damaged</option>
                                </select></div>
                            <div class="form-group">
                                <label>Quantity (Unit)</label>
                                <input type="number" id="totalQuantity" name="totalQuantity" value="1" min="1">
                                <small style="font-size:10px; color:#666;">*If > 1, the asset code will be increased by a 2-digit sequence (e.g.: -01)</small>
                            </div>
                        </div>
                    </div>

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">II. Technical Specifications</div>
                        <div class="form-row">
                            <div class="form-group"><label>Capacity</label><input type="text" id="capacity" name="capacity"></div>
                            <div class="form-group"><label>Dimensions</label><input type="text" id="dimensions" name="dimensions"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Weight</label><input type="text" id="weight" name="weight"></div>
                            <div class="form-group"><label>Color</label><input type="text" id="color" name="color"></div>
                        </div>
                    </div>

                    <div class="log-section" style="display: block; margin-bottom: 25px;">
                        <div class="log-title">III. Maintenance</div>
                        <div class="form-row">
                            <div class="form-group"><label>Maintenance Frequency</label><input type="text" id="maintenance_frequency" name="maintenance_frequency"></div>
                            <div class="form-group"><label>Vendor</label><input type="text" id="vendor" name="vendor"></div>
                        </div>
                        <div class="form-group full-width"><label>Photo</label><input type="file" id="pictures" name="pictures" onchange="window.previewImage(this)"></div>
                        <div id="imagePreview" style="display:none; text-align:center; margin-top:10px;"><img id="previewImg" src="" style="max-height:150px;"></div>
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

<div class="modal-overlay" id="qrModal">
    <div class="modal-content">
        <div class="modal-content-inner" style="text-align: center;">
            <div class="modal-header-custom" style="justify-content: center; border:none;">
                <h3 class="modal-title">QR Code</h3>
            </div>
            <div style="padding:10px;"><img id="qrImage" src="" style="width:200px;"></div>
            <p id="qrAssetInfo" style="font-weight:bold;"></p>
            <div class="form-actions" style="justify-content:center;"><button type="button" class="btn-cancel" onclick="window.closeQRModal()">Close</button><a href="#" id="qrDownloadBtn" class="btn-save">Download</a></div>
        </div>
    </div>
</div>
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content confirmation">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 class="modal-title">Delete Asset?</h3>
            </div>

            <div style="text-align: center; padding: 20px 0;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #e74c3c; margin-bottom: 15px;"></i>
                <p style="font-size: 15px; color: #555; margin: 0;">Are you sure you want to delete this asset data?
            </div>

            <div class="form-actions-modal" style="justify-content: center; gap: 15px;">
                <button type="button" class="btn-cancel" onclick="window.closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-confirm-delete" onclick="window.confirmDelete()">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<?php require_once '../app/views/layouts/footer.php'; ?>