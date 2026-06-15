const InOutManager = {
    baseUrl: '',
    items: [],          
    historyData: [],    
    selectedItem: null,
    
    // Paginasi
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,

    init(url) {
        this.baseUrl = url.replace(/\/$/, '');
        console.log('InOutManager initialized. BaseURL:', this.baseUrl);
        
        this.loadItems();   
        this.loadHistory(); 
        this.initEventListeners();
        this.setTodayDate();
    },

    initEventListeners() {
        const form = document.getElementById('inoutForm');
        if (form) form.onsubmit = e => this.submitStockUpdate(e);

        const searchInput = document.getElementById('itemCodeSearch');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.searchItems());
            searchInput.addEventListener('focus', () => this.searchItems());
        }

        // Tutup autocomplete jika klik di luar
        document.addEventListener('click', e => {
            if (e.target.id !== 'itemCodeSearch') {
                const list = document.getElementById('autocompleteList');
                if (list) list.style.display = 'none';
            }
        });
    },

    setTodayDate() {
        const dateInput = document.getElementById('date');
        if (dateInput) dateInput.valueAsDate = new Date();
    },

    loadItems() {
        // Mengambil semua item untuk keperluan search
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=consumable/getAll` 
            : `${this.baseUrl}/consumable/getAll`;

        fetch(endpoint)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.items = d.data;
                }
            })
            .catch(e => console.error('Error loading items:', e));
    },

    loadHistory() {
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=consumable/getHistory` 
            : `${this.baseUrl}/consumable/getHistory`;

        const tbody = document.getElementById('historyTableBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading history...</td></tr>';

        fetch(endpoint)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.historyData = d.data;
                    this.currentPage = 1;
                    this.updatePaginationState();
                    this.renderHistory();
                    this.renderPagination();
                } else {
                    if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${d.message}</td></tr>`;
                }
            })
            .catch(e => {
                console.error('Error loading history:', e);
                if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Connection error</td></tr>';
            });
    },

    searchItems() {
        const input = document.getElementById('itemCodeSearch').value.toUpperCase();
        const list = document.getElementById('autocompleteList');
        
        if (!list) return;
        list.innerHTML = '';

        if (input.length === 0) {
            list.style.display = 'none';
            return;
        }

        const filtered = this.items.filter(item =>
            (item.item_code && item.item_code.toUpperCase().includes(input)) ||
            (item.item_name && item.item_name.toUpperCase().includes(input))
        );

        if (filtered.length === 0) {
            list.innerHTML = '<div class="autocomplete-item" style="color:#999; cursor:default;">Asset not found</div>';
            list.style.display = 'block';
            return;
        }

        list.innerHTML = filtered.map(item => `
            <div class="autocomplete-item" onclick='InOutManager.selectItem(${JSON.stringify(item).replace(/'/g, "&#39;")})'>
                <strong>${this.esc(item.item_code)}</strong> - ${this.esc(item.item_name)}
                <small>Stock : <strong>${item.quantity}</strong> | Location : ${this.esc(item.location_name || '-')}</small>
            </div>
        `).join('');
        
        list.style.display = 'block';
    },

    selectItem(item) {
        this.selectedItem = item;
        document.getElementById('itemCodeSearch').value = `${item.item_code} - ${item.item_name}`;
        document.getElementById('item_id').value = item.id;
        document.getElementById('current_stock').value = item.quantity;
        document.getElementById('autocompleteList').style.display = 'none';
    },

    submitStockUpdate(e) {
        e.preventDefault();
        
        if (!this.selectedItem) {
            AppHelpers.showToast('Please select an item first', 'warning');
            return;
        }

        const form = e.target;
        const fd = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        const txt = btn.textContent;

        btn.textContent = 'Saving...';
        btn.disabled = true;

        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=consumable/updateStock` 
            : `${this.baseUrl}/consumable/updateStock`;

        fetch(endpoint, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                AppHelpers.showToast('Stock successfully updated', 'success');
                this.closeForm();
                this.loadItems();   
                this.loadHistory(); 
            } else {
                AppHelpers.showToast(d.message || 'Failed to update stock', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            AppHelpers.showToast('System error occurred', 'error');
        })
        .finally(() => {
            btn.textContent = txt;
            btn.disabled = false;
        });
    },

    updatePaginationState() {
        this.totalPages = Math.ceil(this.historyData.length / this.itemsPerPage);
        if (this.totalPages === 0) this.totalPages = 1;
    },

    renderHistory() {
        const tbody = document.getElementById('historyTableBody');
        if (!tbody) return;

        if (this.historyData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No transaction history yet</td></tr>';
            return;
        }

        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const pageData = this.historyData.slice(start, end);

        tbody.innerHTML = pageData.map((row, index) => {
            const num = start + index + 1;
            
            // Badge Status
            let badge = '';
            if (row.status === 'in') {
                badge = '<span class="badge" style="background:#28a745; color:white; padding:4px 8px; border-radius:4px;">IN</span>';
            } else if (row.status === 'out') {
                badge = '<span class="badge" style="background:#dc3545; color:white; padding:4px 8px; border-radius:4px;">OUT</span>';
            } else {
                badge = '<span class="badge" style="background:#6c757d; color:white; padding:4px 8px; border-radius:4px;">-</span>';
            }

            return `
            <tr>
                <td>${num}</td>
                <td>${row.date || '-'}</td>
                <td><span class="badge badge-asset">${this.esc(row.item_code)}</span></td>
                <td><strong>${this.esc(row.item_name)}</strong></td>
                <td>${badge}</td>
                <td>${this.esc(row.remark || '-')}</td>
                <td><strong>${row.quantity}</strong></td>
                <td><strong style="color:#17a2b8;">${row.current_balance !== undefined ? row.current_balance : '-'}</strong></td>
            </tr>
            `;
        }).join('');
    },

    renderPagination() {
        const container = document.getElementById('paginationControls');
        if (!container) return;
        
        let html = '';
        
        if (this.currentPage > 1) {
            html += `<a href="#" onclick="event.preventDefault(); InOutManager.goToPage(${this.currentPage - 1})">« Prev</a>`;
        } else {
            html += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                html += `<span class="current">${i}</span>`;
            } else {
                html += `<a href="#" onclick="event.preventDefault(); InOutManager.goToPage(${i})">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            html += `<a href="#" onclick="event.preventDefault(); InOutManager.goToPage(${this.currentPage + 1})">Next »</a>`;
        } else {
            html += `<span class="disabled">Next »</span>`;
        }

        container.innerHTML = html;
    },

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.renderHistory();
        this.renderPagination();
    },

    openForm() {
        document.getElementById('inoutForm').reset();
        document.getElementById('item_id').value = '';
        document.getElementById('autocompleteList').style.display = 'none';
        this.selectedItem = null;
        this.setTodayDate();
        AppHelpers.showModal('inoutModal');
    },

    closeForm() {
        AppHelpers.closeModal('inoutModal');
    },

    esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
};

window.openFormModal = () => InOutManager.openForm();
window.closeFormModal = () => InOutManager.closeForm();

document.addEventListener('DOMContentLoaded', () => {
    const s = document.querySelector('script[data-base-url]');
    const baseUrl = s ? s.getAttribute('data-base-url') : 'http://localhost/dalaz-asset/public/index.php?url=';
    
    if (document.getElementById('historyTableBody')) {
        InOutManager.init(baseUrl);
    }
});