<?php require_once '../app/views/layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/asset.css">

<style>
    .log-section {
        margin-bottom: 20px;
    }

    .log-title {
        font-weight: bold;
        border-bottom: 2px solid #eee;
        padding-bottom: 5px;
        margin-bottom: 15px;
        color: #333;
    }

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
        <h1 class="page-title">Inventory Data</h1>
    </div>

    <div class="toolbar-container">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, code...">
        </div>

        <div class="btn-group">
            <a href="<?= BASE_URL ?>/consumable/inout" class="btn-save" style="background:#ffc107; color:#333; text-decoration:none; padding:8px 15px; border-radius:4px; display:inline-flex; align-items:center;">
                <i class="fas fa-exchange-alt" style="margin-right:5px;"></i> In/Out Stock
            </a>
            <button class="btn-cyan" onclick="window.openImportModal()">
                <i class="fas fa-file-csv"></i> Import CSV
            </button>
            <button class="btn-add" onclick="window.openAddModal()">+ Add</button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="consumableTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Asset Name</th>
                        <th>Code</th>
                        <th>Stock</th>
                        <th>Unit</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Condition</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="consumableTableBody">
                    <tr>
                        <td colspan="9" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="paginationControls" class="pagination"></div>
    </div>
</div>

<div class="modal-overlay" id="consumableModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto; align-items:flex-start!important;">
    <div class="modal-content" style="margin:40px auto!important; max-width:800px; width:95%; background:#fff; border-radius:8px;">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 id="modalTitle">Add Inventory</h3>
                <button class="modal-close-btn" onclick="window.closeModal()">&times;</button>
            </div>

            <form id="consumableForm" enctype="multipart/form-data">
                <input type="hidden" id="itemId" name="id">

                <div class="form-container" style="display:block;">

                    <div class="log-section">
                        <div class="log-title">I. General Information</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Asset Name <span style="color:red">*</span></label>
                                <input type="text" id="item_name" name="item_name" required>
                            </div>
                            <div class="form-group">
                                <label>Asset Code <span style="color:red">*</span></label>
                                <input type="text" id="item_code" name="item_code" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Merk</label>
                                <input type="text" id="merk" name="merk">
                            </div>
                            <div class="form-group">
                                <label>Responsible Person</label>
                                <input type="text" id="responsible_person" name="responsible_person">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>User</label>
                                <input type="text" id="assigned_to" name="assigned_to">
                            </div>
                            <div class="form-group">
                                <label>Unit</label>
                                <input type="text" id="uom" name="uom" placeholder="E.g : Pcs, Box, Rim">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Minimum Order</label>
                                <input type="number" id="min_order" name="min_order" value="6">
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" id="quantity" name="quantity" value="0">
                                <small style="font-size:11px; color:#666;">*Can only be edited when adding new items</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Location</label>
                                <select id="location_id" name="location_id">
                                    <option value="">-- Select Location --</option>
                                    <?php foreach ($data['locations'] as $l): ?>
                                        <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['location_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select id="category_id" name="category_id">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($data['categories'] as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Supporting Vendor</label>
                                <input type="text" id="supporting_vendor" name="supporting_vendor">
                            </div>
                            <div class="form-group">
                                <label>Condition</label>
                                <select id="condition_status" name="condition_status">
                                    <option value="good">Good</option>
                                    <option value="damaged">Damaged</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="log-section">
                        <div class="log-title">II. Documentation</div>

                        <div class="form-group full-width">
                            <label>Upload Photos (You can select multiple photos)</label>
                            <div style="border: 2px dashed #ddd; padding: 15px; border-radius: 6px; text-align: center; background: #fafafa;">
                                <input type="file" id="add-pictures" name="pictures[]" accept="image/*" multiple style="width:100%">
                                <div id="existing-pictures" style="margin-top:10px; text-align: left;"></div>
                                <div id="preview-container" style="margin-top:10px; text-align: left;"></div>
                            </div>
                        </div>

                        <div class="form-group full-width" style="margin-top: 15px;">
                            <label>Supporting Document (PDF)</label>

                            <input type="file" id="doc_support" name="doc_support" accept="application/pdf" style="border: 1px solid #ddd; padding: 8px; width: 100%; border-radius: 4px;">
                            <small style="color: #e74c3c; font-size: 11px; margin-top: 4px; display: block;">*Max file size: 2MB</small>

                            <div id="pdf-upload-info" style="margin-top: 5px; font-size: 12px; color: #27ae60; font-weight: bold; display: none;">
                                <i class="fas fa-check-circle"></i> Selected: <span id="pdf-filename"></span>
                            </div>

                            <div id="current-pdf-container" style="margin-top: 8px;">
                                <span style="font-size: 12px; color: #666; margin-right: 5px;">Current File:</span>
                                <a href="#" id="btn-view-pdf" target="_blank" style="font-size: 12px; color: #3498db; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-file-pdf"></i> View PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions-modal" style="justify-content:flex-end; border-top: 1px solid #eee; padding-top: 15px;">
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
                <h3 class="modal-title">Import CSV Data</h3>
                <button class="modal-close-btn" onclick="window.closeImportModal()">&times;</button>
            </div>

            <form id="importForm" enctype="multipart/form-data">
                <div style="padding: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 20px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px; color: #333;"><i class="fas fa-info-circle" style="color: #17a2b8;"></i> Import Instructions:</h4>

                        <ul style="font-size: 13px; color: #555; padding-left: 20px; line-height: 1.6; margin-bottom: 15px;">
                            <li>Use the provided <strong>CSV Template</strong>.</li>
                            <li><strong>ID Location</strong> and <strong>ID Category</strong> columns must be filled with <strong>NUMBERS</strong> (Check menu Location/Category to see IDs).</li>
                            <li><strong>Images & Documents</strong> cannot be imported (add manually via Edit).</li>
                            <li>Special symbols (±, °) are supported.</li>
                            <li>
                                Fill the <strong>Condition</strong> column (Must be in lowercase English):
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-top: 5px; background: #fff; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <div>✅ <code>good</code></div>
                                    <div>❌ <code>damaged</code></div>
                                </div>
                            </li>
                        </ul>

                        <a href="<?= BASE_URL ?>/consumable/downloadTemplate" style="display: block; text-align: center; background: #e3f2fd; color: #0277bd; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 1px dashed #0277bd; transition: all 0.2s;">
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

<div class="modal-overlay" id="deleteModal" style="display:none;">
    <div class="modal-content confirmation">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3>Confirm Delete</h3>
            </div>
            <div style="text-align:center; padding:20px;">
                <i class="fas fa-exclamation-triangle" style="font-size:40px; color:#dc3545; margin-bottom:10px;"></i>
                <p>Are you sure you want to delete this data?</p>
            </div>
            <div class="form-actions-modal" style="justify-content:center;">
                <button type="button" class="btn-cancel" onclick="window.closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-confirm-delete" onclick="window.confirmDelete()">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<?php require_once '../app/views/layouts/footer.php'; ?>