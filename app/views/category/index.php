<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Category Data</h1>
        <div class="page-actions">
            <button class="btn-add" onclick="CategoryManager.addCategory()">+ Add</button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="categoryTable">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>ID CATEGORY</th>
                        <th>CATEGORY NAME</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody id="categoryTableBody">
                    <tr>
                        <td colspan="4" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="categoryPaginationControls"></div>
    </div>

    <div class="search-section">
        <h2 class="section-title">Search Assets by Category</h2>

        <div class="search-form">
            <div class="search-input-group">
                <input type="text" id="searchInput" class="search-input" placeholder="Type asset name or code...">

                <select id="categoryFilter" class="search-input">
                    <option value="">-- All Categories --</option>
                </select>

                <div class="search-btn-group">
                    <button class="btn-search" onclick="CategoryManager.searchAssets(1)">Search</button>
                    <button class="btn-clear" onclick="CategoryManager.clearSearch()">Clear</button>
                </div>
            </div>
        </div>

        <div class="table-responsive" id="assetsTableContainer" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-top: 20px;">
            <table class="table table-striped table-hover" id="searchResults">
                <thead>
                    <tr style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                        <th width="5%" style="text-align: center; font-size: 12px; color: #666;">NO</th>
                        <th width="10%" style="text-align: center; font-size: 12px; color: #666;">TYPE</th>
                        <th width="15%" style="text-align: center; padding-left: 15px; font-size: 12px; color: #666;">CODE</th>
                        <th width="20%" style="text-align: center; font-size: 12px; color: #666;">NAME</th>
                        <th width="10%" style="text-align: center; font-size: 12px; color: #666;">QTY</th>
                        <th width="20%" style="text-align: center; font-size: 12px; color: #666;">LOCATION</th>
                        <th width="20%" style="text-align: center; font-size: 12px; color: #666;">CATEGORY</th>
                    </tr>
                </thead>
                <tbody id="searchResultsBody">
                    <tr>
                        <td colspan="7" class="text-center" style="padding:20px; color:#999;">Enter a search keyword or select a category to see the results.</td>
                    </tr>
                </tbody>
            </table>
            <div class="pagination" id="paginationControls"></div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="categoryModal">
    <div class="modal-content">
        <div class="modal-content-inner">
            <div class="modal-header" id="modalTitle">Add New Category</div>
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="id">

                <div class="form-group">
                    <label>Category Name <span class="required">*</span></label>
                    <input type="text" id="categoryName" name="categoryName" required placeholder="Enter category name">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="CategoryManager.closeModal('categoryModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-content confirmation">
        <div class="modal-content-inner">
            <div class="modal-header">Are you sure?</div>
            <p style="text-align: center; margin-bottom: 20px; color: #666;">This category will be permanently deleted.</p>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="CategoryManager.closeModal('deleteModal')">Cancel</button>
                <button type="button" class="btn-ok btn-delete" onclick="CategoryManager.confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<?php require_once '../app/views/layouts/footer.php'; ?>