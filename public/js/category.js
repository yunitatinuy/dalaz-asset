const CategoryManager = {
    baseUrl: '',
    currentId: null,
    isEdit: false,
    categoryData: [], 
    
    // PAGINASI
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,

    init(baseUrl) {
        this.baseUrl = baseUrl;
        console.log('CategoryManager started. BaseURL:', this.baseUrl);
        
        this.loadCategories();
        this.loadCategoryOptions();
        this.setupEvents();
    },

    setupEvents() {
        const form = document.getElementById('categoryForm');
        if (form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                this.saveCategory();
            };
        }

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.onkeypress = (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.searchAssets(1);
                }
            };
        }

        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter) {
            categoryFilter.onchange = () => this.searchAssets(1);
        }
    },

    // CRUD
    loadCategories() {
        fetch(`${this.baseUrl}category/getAll`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.categoryData = d.data;
                    this.totalPages = Math.ceil(this.categoryData.length / this.itemsPerPage);
                    this.renderTable();
                    this.renderCategoryPagination();
                } else {
                    this.showError('Failed to load categories');
                }
            })
            .catch(e => {
                console.error('Error:', e);
                this.showError('Error loading data');
            });
    },

    renderTable() {
        const tbody = document.getElementById('categoryTableBody');
        if (!tbody) return;

        if (!this.categoryData || this.categoryData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No data found</td></tr>';
            return;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedData = this.categoryData.slice(startIndex, endIndex);

        tbody.innerHTML = paginatedData.map((item, i) => {
            const displayNumber = startIndex + i + 1; 
            
            return `
            <tr>
                <td>${displayNumber}</td>
                <td><span class="badge" style="background:#e3f2fd;color:#0d47a1">#${item.id}</span></td>
                <td>${this.esc(item.category_name)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-edit" onclick="CategoryManager.editCategory(${item.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" onclick="CategoryManager.deleteCategory(${item.id})" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `}).join('');
    },

    renderCategoryPagination() {
        const paginationContainer = document.getElementById('categoryPaginationControls');
        if (!paginationContainer) return;

        const calculatedPages = Math.ceil(this.categoryData.length / this.itemsPerPage);
        this.totalPages = calculatedPages > 0 ? calculatedPages : 1;

        let paginationHTML = '';

        if (this.currentPage > 1) {
            paginationHTML += `<a onclick="CategoryManager.goToPage(${this.currentPage - 1})">« Prev</a>`;
        } else {
            paginationHTML += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                paginationHTML += `<span class="current">${i}</span>`;
            } else {
                paginationHTML += `<a onclick="CategoryManager.goToPage(${i})">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            paginationHTML += `<a onclick="CategoryManager.goToPage(${this.currentPage + 1})">Next »</a>`;
        } else {
            paginationHTML += `<span class="disabled">Next »</span>`;
        }

        paginationContainer.innerHTML = paginationHTML;
    },

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.renderTable();
        this.renderCategoryPagination();
        
        document.querySelector('#categoryTableBody')?.scrollIntoView({ behavior: 'smooth' });
    },

    addCategory() {
        this.isEdit = false;
        this.currentId = null;
        document.getElementById('modalTitle').textContent = 'Add New Category';
        document.getElementById('categoryForm').reset();
        this.openModal('categoryModal');
    },

    editCategory(id) {
        this.isEdit = true;
        this.currentId = id;
        document.getElementById('modalTitle').textContent = 'Edit Category';

        fetch(`${this.baseUrl}category/getById&id=${id}`)
            .then(r => r.json())
            .then(d => {
                if (d.success && d.data) {
                    document.getElementById('categoryId').value = d.data.id;
                    document.getElementById('categoryName').value = d.data.category_name;
                    this.openModal('categoryModal');
                } else {
                    this.showError('Category not found');
                }
            })
            .catch(e => this.showError('Error loading data'));
    },

    saveCategory() {
        const formData = new FormData(document.getElementById('categoryForm'));
        const url = this.isEdit 
            ? `${this.baseUrl}category/edit` 
            : `${this.baseUrl}category/add`;

        const btn = document.querySelector('#categoryForm button[type="submit"]');
        const originalText = btn.textContent;
        btn.textContent = 'Saving...';
        btn.disabled = true;

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.showSuccess(d.message || 'Saved successfully');
                    this.closeModal('categoryModal');
                    this.loadCategories();
                    this.loadCategoryOptions();
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

    deleteCategory(id) {
        this.currentId = id;
        this.openModal('deleteModal');
    },

    confirmDelete() {
        const formData = new FormData();
        formData.append('id', this.currentId);

        fetch(`${this.baseUrl}category/delete`, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.showSuccess('Category deleted!');
                    this.closeModal('deleteModal');
                    this.loadCategories();
                    this.loadCategoryOptions();
                } else {
                    this.showError(d.message || 'Failed to delete');
                }
            })
            .catch(e => this.showError('Error: ' + e.message));
    },

    // SEARCH ASSET
    loadCategoryOptions() {
        fetch(`${this.baseUrl}category/getAll`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    const select = document.getElementById('categoryFilter');
                    if (select) {
                        select.innerHTML = '<option value="">-- All Categories --</option>' +
                            d.data.map(cat => `<option value="${cat.id}">${this.esc(cat.category_name)}</option>`).join('');
                    }
                }
            })
            .catch(e => console.error('Error loading options:', e));
    },

    searchAssets(page = 1) {
        const search = document.getElementById('searchInput').value.trim();
        const categoryId = document.getElementById('categoryFilter').value;

        if (!search && !categoryId) {
            this.showWarning('Please enter keyword or select category');
            this.clearResults();
            return;
        }

        const url = `${this.baseUrl}category/searchAssets&search=${encodeURIComponent(search)}&category_id=${categoryId}&page=${page}`;

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
            
            let badgeStyle = 'background:#f8f9fa;color:#666;border:1px solid #ddd';

            if (item.type === 'Office Equipment') {
                badgeStyle = 'background:#e3f2fd;color:#1565c0;border:1px solid #bbdefb'; 
            } else if (item.type === 'Equipment') {
                badgeStyle = 'background:#d4edda;color:#155724;border:1px solid #c3e6cb'; 
            } else if (item.type === 'Inventory') {
                badgeStyle = 'background:#fff3cd;color:#856404;border:1px solid #ffeeba'; 
            }

            const cellStyle = 'vertical-align: middle; padding: 10px 8px; font-size: 13px;';

            return `
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="${cellStyle} text-align: center;">${startNum + i + 1}</td>
                    
                    <td style="${cellStyle} text-align: center;">
                        <span class="badge" style="${badgeStyle}; padding:4px 8px; border-radius:4px; font-size:10px; font-weight:700; white-space:nowrap; display:inline-block; text-transform:uppercase;">
                            ${item.type}
                        </span>
                    </td>

                    <td style="${cellStyle} text-align: center; padding-left: 15px;">
                    <span style="font-family:monospace; font-weight:600; color:#555; background:#f5f5f5; padding:2px 6px; border-radius:3px; border:1px solid #eee;">
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
            html += `<a onclick="CategoryManager.searchAssets(${page - 1})">« Prev</a>`;
        } else {
            html += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= safeTotalPages; i++) {
            if (i === page) {
                html += `<span class="current">${i}</span>`;
            } else {
                html += `<a onclick="CategoryManager.searchAssets(${i})">${i}</a>`;
            }
        }

        if (page < safeTotalPages) {
            html += `<a onclick="CategoryManager.searchAssets(${page + 1})">Next »</a>`;
        } else {
            html += `<span class="disabled">Next »</span>`;
        }

        container.innerHTML = html;
    },

    clearResults() {
        const tbody = document.getElementById('searchResultsBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Enter keyword or select category</td></tr>';
        }
        const pag = document.getElementById('paginationControls');
        if (pag) pag.innerHTML = '';
    },

    clearSearch() {
        document.getElementById('searchInput').value = '';
        document.getElementById('categoryFilter').value = '';
        this.clearResults();
        this.showSuccess('Search cleared');
    },

    // HELPER
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

// Auto-init
document.addEventListener('DOMContentLoaded', () => {
    const script = document.querySelector('script[data-base-url]');
    const baseUrl = script?.getAttribute('data-base-url');

    console.log('Page loaded. BaseURL:', baseUrl);

    if (baseUrl && document.getElementById('categoryTableBody')) {
        CategoryManager.init(baseUrl);
    } else {
        console.error('Failed to initialize CategoryManager');
    }
});
