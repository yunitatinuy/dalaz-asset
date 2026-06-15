<?php require_once '../app/views/layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/asset.css">

<style>
    /* Style khusus untuk autocomplete */
    .autocomplete-wrapper {
        position: relative;
    }

    #autocompleteList {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .autocomplete-item {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
    }

    .autocomplete-item:last-child {
        border-bottom: none;
    }

    .autocomplete-item:hover {
        background-color: #f8f9fa;
    }

    .autocomplete-item strong {
        color: #333;
    }

    .autocomplete-item small {
        color: #666;
        display: block;
        margin-top: 2px;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">In / Out Stock (Inventory)</h1>
        <div class="page-actions">
            <button class="btn-add" style="background: #28a745;" onclick="window.openFormModal()">+ Record Transactions</button>
            <a href="<?= BASE_URL ?>/public/index.php?url=consumable" class="btn-back-inout" style="display: inline-block; background: #6c757d; color: white; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; margin-left: 10px;">← Back</a>
        </div>
    </div>

    <div class="info-box" style="background-color: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 4px; padding: 15px; color: #555; margin-bottom: 20px;">
        <div style="display: flex; align-items: flex-start;">
            <i class="fas fa-info-circle" style="font-size: 20px; margin-right: 10px; color: #2196F3; margin-top: 2px;"></i>
            <div>
                <strong style="display:block; margin-bottom:5px; color:#333;">Page Information</strong>
                <p style="margin: 0; font-size: 14px; line-height: 1.5;">
                    This page records incoming and outgoing transactions. <br>
                    The data below shows the <strong>latest status</strong> of each item that has ever been transacted.
                </p>
            </div>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="historyTable">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>TRANSACTION DATE</th>
                        <th>ASSET CODE</th>
                        <th>ASSET NAME</th>
                        <th>STATUS</th>
                        <th>REMARK/PIC</th>
                        <th>QTY IN/OUT</th>
                        <th>CURRENT STOCK</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr>
                        <td colspan="7" class="text-center">Loading history...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="paginationControls" class="pagination"></div>
    </div>
</div>

<div class="modal-overlay" id="inoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto; align-items:flex-start!important;">
    <div class="modal-content" style="margin:40px auto!important; max-width:600px; width:95%; background:#fff; border-radius:8px;">
        <div class="modal-content-inner">
            <div class="modal-header-custom">
                <h3 id="modalTitle">Record New Stock</h3>
                <button class="modal-close-btn" onclick="window.closeFormModal()">&times;</button>
            </div>

            <form id="inoutForm" enctype="multipart/form-data">
                <div style="padding: 20px;">

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-weight:600; margin-bottom:5px; display:block;">Search Item (Code or Name) <span style="color:red">*</span></label>
                        <div class="autocomplete-wrapper">
                            <input type="text" id="itemCodeSearch" placeholder="Type the asset code or name..." autocomplete="off" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                            <div id="autocompleteList" style="display:none;"></div>
                        </div>
                        <input type="hidden" id="item_id" name="item_id">
                    </div>

                    <div class="form-row" style="display:flex; gap:15px; margin-bottom:15px;">
                        <div class="form-group" style="flex:1;">
                            <label style="font-weight:600;">Current Stock</label>
                            <input type="text" id="current_stock" readonly style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9; font-weight:bold; color:#555;">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label style="font-weight:600;">Quantity <span style="color:red">*</span></label>
                            <input type="number" id="quantity" name="quantity" required min="1" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                    </div>

                    <div class="form-row" style="display:flex; gap:15px; margin-bottom:15px;">
                        <div class="form-group" style="flex:1;">
                            <label style="font-weight:600;">Date <span style="color:red">*</span></label>
                            <input type="date" id="date" name="date" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label style="font-weight:600;">Action <span style="color:red">*</span></label>
                            <select id="status" name="status" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                                <option value="">-- Select --</option>
                                <option value="in">IN</option>
                                <option value="out">OUT</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:15px;">
                        <label style="font-weight:600;">Remark</label>
                        <textarea id="remark" name="remark" rows="2" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="font-weight:600;">Supporting Document (PDF)</label>
                        <input type="file" id="doc_support" name="doc_support" accept=".pdf" style="width:100%; padding:5px; border:1px solid #ddd; border-radius:4px;">
                        <small style="color:#999; display:block; margin-top:5px;">Optional. Max 5MB.</small>
                    </div>

                </div>

                <div class="form-actions-modal" style="padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-cancel" onclick="window.closeFormModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/public/js/consumable_inout.js" data-base-url="<?= BASE_URL ?>"></script>

<?php require_once '../app/views/layouts/footer.php'; ?>