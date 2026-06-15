<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Location Data</h1>
        <div class="page-actions">
            <button class="btn-add" onclick="LocationManager.addLocation()">+ Add</button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table" id="locationTable">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>ID LOCATION</th>
                        <th>LOCATION NAME</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody id="locationTableBody">
                    <tr>
                        <td colspan="3" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="locationPaginationControls"></div>
    </div>

    <div class="search-section">
        <h2 class="section-title">Search assets by location</h2>

        <div class="search-form">
            <div class="search-input-group">
                <input type="text" id="searchInput" class="search-input" placeholder="Type asset name or code...">

                <select id="locationFilter" class="search-input">
                    <option value="">-- All Locations --</option>
                </select>

                <div class="search-btn-group">
                    <button class="btn-search" onclick="LocationManager.searchAssets(1)">Search</button>
                    <button class="btn-clear" onclick="LocationManager.clearSearch()">Clear</button>
                </div>
            </div>
        </div>

        <div class="data-table">
            <div class="table-responsive">
                <table class="table" id="searchResultsTable">
                    <thead>
                        <tr style="background-color: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                            <th width="5%" style="text-align: center;">NO</th>
                            <th width="10%" style="text-align: center;">TYPE</th>
                            <th width="15%" style="text-align: center; padding-left: 15px;">CODE</th>
                            <th width="20%" style="text-align: center;">NAME</th>
                            <th width="10%" style="text-align: center;">QTY</th>
                            <th width="20%" style="text-align: center;">LOCATION</th>
                            <th width="20%" style="text-align: center;">CATEGORY</th>
                        </tr>
                    </thead>
                    <tbody id="searchResultsBody">
                        <tr>
                            <td colspan="7" class="text-center" style="padding:20px; color:#999;">Enter a search keyword or select a location to see the results.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="paginationControls"></div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="locationModal">
    <div class="modal-content">
        <div class="modal-content-inner">
            <div class="modal-header" id="modalTitle">Add New Location</div>
            <form id="locationForm">
                <input type="hidden" id="locationId" name="id">

                <div class="form-group">
                    <label>Location Name <span class="required">*</span></label>
                    <input type="text" id="locationName" name="locationName" required placeholder="Enter location name">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="LocationManager.closeModal('locationModal')">Cancel</button>
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
            <p style="text-align: center; margin-bottom: 20px; color: #666;">This location will be permanently deleted.</p>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="LocationManager.closeModal('deleteModal')">Cancel</button>
                <button type="button" class="btn-ok btn-delete" onclick="LocationManager.confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<?php require_once '../app/views/layouts/footer.php'; ?>