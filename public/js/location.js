const LocationManager = {
    baseUrl: '',
    currentId: null,
    isEdit: false,
    locationData: [],
    
    // PAGINASI
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,

    init(baseUrl) {
        this.baseUrl = baseUrl;
        console.log('LocationManager started. BaseURL:', this.baseUrl);
        
        this.loadLocations();
        this.loadLocationOptions();
        this.setupEvents();
    },

    setupEvents() {
        const form = document.getElementById('locationForm');
        if (form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                this.saveLocation();
            };
        }

        // Enter untuk search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.onkeypress = (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.searchAssets(1);
                }
            };
        }

        // Auto search saat pilih location filter
        const locationFilter = document.getElementById('locationFilter');
        if (locationFilter) {
            locationFilter.onchange = () => this.searchAssets(1);
        }
    },

    // CRUD LOCATION
    loadLocations() {
        fetch(`${this.baseUrl}location/getAll`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.locationData = d.data;
                    this.totalPages = Math.ceil(this.locationData.length / this.itemsPerPage);
                    this.renderTable();
                    this.renderLocationPagination();
                } else {
                    this.showError('Failed to load locations');
                }
            })
            .catch(e => {
                console.error('Error:', e);
                this.showError('Error loading data');
            });
    },

    renderTable() {
        const tbody = document.getElementById('locationTableBody');
        if (!tbody) return;

        if (!this.locationData || this.locationData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No data found</td></tr>';
            return;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedData = this.locationData.slice(startIndex, endIndex);

        tbody.innerHTML = paginatedData.map((item, i) => {
            const displayNumber = startIndex + i + 1;
            
            return `
            <tr>
                <td>${displayNumber}</td>
                <td><span class="badge" style="background:#e3f2fd;color:#0d47a1">#${item.id}</span></td>
                <td>${this.esc(item.location_name)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-edit" onclick="LocationManager.editLocation(${item.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" onclick="LocationManager.deleteLocation(${item.id})" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `}).join('');
    },

    renderLocationPagination() {
        const paginationContainer = document.getElementById('locationPaginationControls');
        if (!paginationContainer) return;

        const calculatedPages = Math.ceil(this.locationData.length / this.itemsPerPage);
        this.totalPages = calculatedPages > 0 ? calculatedPages : 1;

        let paginationHTML = '';

        if (this.currentPage > 1) {
            paginationHTML += `<a onclick="LocationManager.goToPage(${this.currentPage - 1})">« Prev</a>`;
        } else {
            paginationHTML += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                paginationHTML += `<span class="current">${i}</span>`;
            } else {
                paginationHTML += `<a onclick="LocationManager.goToPage(${i})">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            paginationHTML += `<a onclick="LocationManager.goToPage(${this.currentPage + 1})">Next »</a>`;
        } else {
            paginationHTML += `<span class="disabled">Next »</span>`;
        }

        paginationContainer.innerHTML = paginationHTML;
    },

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.renderTable();
        this.renderLocationPagination();
        document.querySelector('#locationTableBody')?.scrollIntoView({ behavior: 'smooth' });
    },

    addLocation() {
        this.isEdit = false;
        this.currentId = null;
        document.getElementById('modalTitle').textContent = 'Add New Location';
        document.getElementById('locationForm').reset();
        this.openModal('locationModal');
    },

    editLocation(id) {
        this.isEdit = true;
        this.currentId = id;
        document.getElementById('modalTitle').textContent = 'Edit Location';

        fetch(`${this.baseUrl}location/getById&id=${id}`)
            .then(r => r.json())
            .then(d => {
                if (d.success && d.data) {
                    document.getElementById('locationId').value = d.data.id;
                    document.getElementById('locationName').value = d.data.location_name;
                    this.openModal('locationModal');
                } else {
                    this.showError('Location not found');
                }
            })
            .catch(e => this.showError('Error loading data'));
    },

    saveLocation() {
        const formData = new FormData(document.getElementById('locationForm'));
        const url = this.isEdit 
            ? `${this.baseUrl}location/edit` 
            : `${this.baseUrl}location/add`;

        const btn = document.querySelector('#locationForm button[type="submit"]');
        const originalText = btn.textContent;
        btn.textContent = 'Saving...';
        btn.disabled = true;

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.showSuccess(d.message || 'Saved successfully');
                    this.closeModal('locationModal');
                    this.loadLocations();
                    this.loadLocationOptions();
                } else {
                    this.showError(d.message || 'Failed to save');
                }
            })
            .catch(e => this.showError('Error: ' + e.message))
            .finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            });
    },

    deleteLocation(id) {
        this.currentId = id;
        this.openModal('deleteModal');
    },

    confirmDelete() {
        const formData = new FormData();
        formData.append('id', this.currentId);

        fetch(`${this.baseUrl}location/delete`, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.showSuccess('Location deleted!');
                    this.closeModal('deleteModal');
                    this.loadLocations();
                    this.loadLocationOptions();
                } else {
                    this.showError(d.message || 'Failed to delete');
                }
            })
            .catch(e => this.showError('Error: ' + e.message));
    },

    // SEARCH ASSET
    loadLocationOptions() {
        fetch(`${this.baseUrl}location/getAll`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    const select = document.getElementById('locationFilter');
                    if (select) {
                        select.innerHTML = '<option value="">-- All Locations --</option>' +
                            d.data.map(loc => `<option value="${loc.id}">${this.esc(loc.location_name)}</option>`).join('');
                    }
                }
            })
            .catch(e => console.error('Error loading options:', e));
    },

    searchAssets(page = 1) {
        const search = document.getElementById('searchInput').value.trim();
        const locationId = document.getElementById('locationFilter').value;

        if (!search && !locationId) {
            this.showWarning('Please enter keyword or select location');
            this.clearResults();
            return;
        }

        const url = `${this.baseUrl}location/searchAssets&search=${encodeURIComponent(search)}&location_id=${locationId}&page=${page}`;

        fetch(url)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.renderResults(d.data, page);
                    this.renderSearchPagination(d.pagination);
                } else {
                    this.showError(d.message || 'Search failed');
                }
            })
            .catch(e => {
                console.error('Search error:', e);
                this.showError('Error searching');
            });
    },

    renderResults(data, page) {
        const tbody = document.getElementById('searchResultsBody');
        if (!tbody) return;

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding:20px; color:#999;">No results found</td></tr>';
            return;
        }

        const startNum = (page - 1) * 10;
        tbody.innerHTML = data.map((item, i) => {
            
            // Logika Warna Badge
            let badgeStyle = 'background:#f8f9fa;color:#666;border:1px solid #ddd';

            if (item.type === 'Office Equipment') {
                badgeStyle = 'background:#e3f2fd;color:#1565c0;border:1px solid #bbdefb'; 
            } else if (item.type === 'Equipment') {
                badgeStyle = 'background:#d4edda;color:#155724;border:1px solid #c3e6cb'; 
            } else if (item.type === 'Inventory') {
                badgeStyle = 'background:#fff3cd;color:#856404;border:1px solid #ffeeba'; 
            }

            const cellStyle = 'vertical-align: middle; padding: 8px; font-size: 13px;';

            return `
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="${cellStyle} text-align: center;">${startNum + i + 1}</td>
                    
                    <td style="${cellStyle} text-align: center;">
                        <span class="badge" style="${badgeStyle}; padding:4px 8px; border-radius:4px; font-size:10px; font-weight:700; white-space:nowrap; display:inline-block; text-transform:uppercase;">
                            ${item.type}
                        </span>
                    </td>
                    
                    <td style="${cellStyle} text-align: center; padding-left: 15px;">
                        <span style="font-family:monospace; font-weight:600; color:#555; background:#f5f5f5; padding:2px 6px; border-radius:3px;">
                            ${this.esc(item.code || '-')}
                        </span>
                    </td>
                    
                    <td style="${cellStyle} text-align: center; font-weight:600; color:#333;">
                        ${this.esc(item.name)}
                    </td>
                    
                    <td style="${cellStyle} text-align: center;">
                        <strong style="color:${item.quantity < 5 ? '#dc3545' : '#28a745'}">
                            ${item.quantity || 0}
                        </strong>
                    </td>
                    
                    <td style="${cellStyle} text-align: center; color:#666;">
                        ${this.esc(item.location_name || '-')}
                    </td>
                    
                    <td style="${cellStyle} text-align: center; color:#666;">
                        ${this.esc(item.category_name || '-')}
                    </td>
                </tr>
            `;
        }).join('');
    },

    renderSearchPagination(pagination) {
        const container = document.getElementById('paginationControls');
        if (!container) return;

        const { page, totalPages } = pagination;
        const safeTotalPages = totalPages > 0 ? totalPages : 1;

        let html = '';

        if (page > 1) {
            html += `<a onclick="LocationManager.searchAssets(${page - 1})">« Prev</a>`;
        } else {
            html += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= safeTotalPages; i++) {
            if (i === page) {
                html += `<span class="current">${i}</span>`;
            } else {
                html += `<a onclick="LocationManager.searchAssets(${i})">${i}</a>`;
            }
        }

        if (page < safeTotalPages) {
            html += `<a onclick="LocationManager.searchAssets(${page + 1})">Next »</a>`;
        } else {
            html += `<span class="disabled">Next »</span>`;
        }

        container.innerHTML = html;
    },

    clearResults() {
        const tbody = document.getElementById('searchResultsBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Enter keyword or select location</td></tr>';
        }
        const pag = document.getElementById('paginationControls');
        if (pag) pag.innerHTML = '';
    },

    clearSearch() {
        document.getElementById('searchInput').value = '';
        document.getElementById('locationFilter').value = '';
        this.clearResults();
        this.showSuccess('Search cleared');
    },

    openModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'flex';
    },

    closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'none';
    },

    showSuccess(msg) {
        this.showToast(msg, 'success');
    },

    showError(msg) {
        this.showToast(msg, 'error');
    },

    showWarning(msg) {
        this.showToast(msg, 'warning');
    },

    showToast(message, type = 'info') {
        if (typeof AppHelpers !== 'undefined' && AppHelpers.showToast) {
            AppHelpers.showToast(message, type);
        } else {
            alert(message);
        }
    },

    esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

// Auto-init saat page load
document.addEventListener('DOMContentLoaded', () => {
    const script = document.querySelector('script[data-base-url]');
    const baseUrl = script?.getAttribute('data-base-url');

    console.log('Page loaded. BaseURL:', baseUrl);

    if (baseUrl && document.getElementById('locationTableBody')) {
        LocationManager.init(baseUrl);
    } else {
        console.error('Failed to initialize LocationManager');
    }
});
