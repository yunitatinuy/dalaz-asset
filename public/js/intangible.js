const IntangibleManager = {
    baseUrl: '',
    currentId: null,
    isEdit: false,
    allData: [],
    filteredData: [],
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,

    init(baseUrl) {
        this.baseUrl = baseUrl;
        this.loadData();
        this.setupEvents();
    },

    setupEvents() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.addEventListener('keyup', (e) => this.handleSearch(e.target.value));

        const form = document.getElementById('formIntangible');
        if (form) form.onsubmit = (e) => { e.preventDefault(); this.saveData(); };

        const importForm = document.getElementById('importForm');
        if (importForm) {
            importForm.onsubmit = (e) => {
                e.preventDefault();
                this.submitImport(new FormData(importForm));
            };
        }

        // VALIDASI PDF SIZE (Max 2MB)
        const docInput = document.getElementById('document_file');
        if (docInput) {
            docInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    if (file.type !== 'application/pdf') {
                        if(typeof AppHelpers !== 'undefined') AppHelpers.showToast('Only PDF files are allowed!', 'warning');
                        else alert('Only PDF files are allowed!');
                        this.value = '';
                    } else if (file.size > 2 * 1024 * 1024) { // 2MB
                        if(typeof AppHelpers !== 'undefined') AppHelpers.showToast('File too large (Max 2MB)', 'warning');
                        else alert('File too large (Max 2MB)');
                        this.value = '';
                    }
                }
            });
        }
    },

    loadData() {
        let url = this.baseUrl.includes('index.php') 
            ? `${this.baseUrl}&url=intangible/getAll` 
            : `${this.baseUrl.replace(/\/$/, '')}/intangible/getAll`;

        fetch(url).then(r => r.json()).then(d => {
            if (d.success) {
                this.allData = d.data || [];
                this.filteredData = this.allData;
                this.currentPage = 1;
                this.updatePaginationState();
                this.renderTable();
                this.renderPagination();
            } else { this.showErrorTable(d.message); }
        }).catch(e => { this.showErrorTable('No data found.'); });
    },

    showErrorTable(msg) { document.getElementById('tableBody').innerHTML = `<tr><td colspan="8" class="text-center" style="padding:20px; color:#666">${msg}</td></tr>`; },

    handleSearch(keyword) {
        const term = keyword.toLowerCase();
        this.filteredData = this.allData.filter(item => (
            (item.asset_code && item.asset_code.toLowerCase().includes(term)) ||
            (item.document_name && item.document_name.toLowerCase().includes(term)) ||
            (item.certificate_number && item.certificate_number.toLowerCase().includes(term))
        ));
        this.currentPage = 1;
        this.updatePaginationState();
        this.renderTable();
        this.renderPagination();
    },

    updatePaginationState() {
        this.totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
        if(this.totalPages === 0) this.totalPages = 1;
    },

    renderTable() {
        const tbody = document.getElementById('tableBody');
        if (!tbody) return;
        if (this.filteredData.length === 0) { tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="padding:20px">No data found</td></tr>'; return; }

        const start = (this.currentPage - 1) * this.itemsPerPage;
        const data = this.filteredData.slice(start, start + this.itemsPerPage);

        tbody.innerHTML = data.map((item, i) => {
            let statusBadge = item.document_status === 'active' 
                ? '<span class="badge" style="background:#d4edda; color:#155724; padding:5px 10px; border-radius:4px; font-size:12px; font-weight:600;">Active</span>'
                : '<span class="badge" style="background:#f8d7da; color:#721c24; padding:5px 10px; border-radius:4px; font-size:12px; font-weight:600;">Expired</span>';

            return `<tr>
                <td>${start + i + 1}</td>
                <td><span class="badge" style="background:#e3f2fd;color:#0d47a1;padding:5px 10px;border-radius:4px;font-weight:600;font-size:12px">${this.esc(item.asset_code)}</span></td>
                <td style="font-weight:600">${this.esc(item.document_name)}</td>
                <td>${this.esc(item.issuing_agency)}</td>
                <td>${this.esc(item.expiration_date)}</td>
                <td>${this.esc(item.location_name)}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="action-buttons" style="display:flex;gap:5px;justify-content:center;">
                        <button onclick="IntangibleManager.edit(${item.id})" style="background:#17a2b8;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer"><i class="fas fa-edit"></i></button>
                        <button onclick="IntangibleManager.askDelete(${item.id})" style="background:#dc3545;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    },

    renderPagination() {
        const pagination = document.getElementById('paginationControls');
        if (!pagination) return;

        pagination.innerHTML = '';

        const createItem = (text, page, isCurrent, isDisabled) => {
            if (isCurrent) {
                return `<span class="current">${text}</span>`;
            } else if (isDisabled) {
                return `<span class="disabled">${text}</span>`;
            } else {
                return `<a href="#" onclick="event.preventDefault(); IntangibleManager.goToPage(${page})">${text}</a>`;
            }
        };

        let html = '';
        html += createItem('« Prev', this.currentPage - 1, false, this.currentPage === 1);
        for (let i = 1; i <= this.totalPages; i++) {
            html += createItem(i, i, i === this.currentPage, false);
        }
        html += createItem('Next »', this.currentPage + 1, false, this.currentPage === this.totalPages);
        
        pagination.innerHTML = html;
    },

    goToPage(p) {
        if(p < 1 || p > this.totalPages) return;
        this.currentPage = p;
        this.renderTable();
        this.renderPagination();
    },

    add() {
        this.isEdit = false;
        this.currentId = null;
        
        const title = document.getElementById('modalTitle');
        if(title) title.innerText = 'Add New Document';
        
        const form = document.getElementById('formIntangible');
        if(form) {
            form.reset();
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.type === 'file' || input.type === 'hidden' || input.type === 'text' || input.type === 'date' || input.type === 'number') {
                    input.value = '';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else if (input.tagName === 'TEXTAREA') {
                    input.value = '';
                }
            });
        }
        
        this.renderLink('link_document', null, '', '');
        this.openModal();
    },

    edit(id) {
        this.isEdit = true;
        this.currentId = id;
        
        const title = document.getElementById('modalTitle');
        if(title) title.innerText = 'Edit Document';
        
        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=intangible/getById&id=${id}` : `${this.baseUrl.replace(/\/$/, '')}/intangible/getById?id=${id}`;
        
        fetch(url).then(r => r.json()).then(res => {
            if(res.success) {
                const d = res.data;
                const set = (k, v) => { if(document.getElementById(k)) document.getElementById(k).value = v || ''; };
                
                set('id', d.id);
                set('asset_code', d.asset_code);
                set('document_name', d.document_name);
                set('location_id', d.location_id);
                set('category_id', d.category_id);
                set('certificate_number', d.certificate_number);
                set('issuing_agency', d.issuing_agency);
                set('issue_date', d.issue_date);
                set('effective_date', d.effective_date);
                set('expiration_date', d.expiration_date);
                set('document_status', d.document_status);

                const root = this.baseUrl.replace(/index\.php\?url=.*$/, '').replace(/\/$/, '');
                this.renderLink('link_document', d.document_path, 'Document', root);
                this.openModal();
            } else { if(typeof AppHelpers !== 'undefined') AppHelpers.showToast('Data not found', 'error'); }
        });
    },

    saveData() {
        const form = document.getElementById('formIntangible');
        const fd = new FormData(form);
        
        if (!this.isEdit) {
            fd.delete('id'); 
        }
        
        const endpoint = this.isEdit ? 'intangible/edit' : 'intangible/add';
        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=${endpoint}` : `${this.baseUrl.replace(/\/$/, '')}/${endpoint}`;
        
        fetch(url, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) {
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                this.closeModal();
                this.loadData();
            } else { if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error'); }
        });
    },

    submitImport(formData) {
        const btn = document.querySelector('#importForm button[type="submit"]');
        const oldText = btn.innerText;
        btn.innerText = 'Importing...';
        btn.disabled = true;

        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=intangible/import` : `${this.baseUrl.replace(/\/$/, '')}/intangible/import`;

        fetch(url, { method: 'POST', body: formData }).then(r => r.json()).then(res => {
            if (res.success) {
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                this.closeImportModal();
                this.loadData();
                document.getElementById('importForm').reset();
            } else { if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error'); }
        }).finally(() => { btn.innerText = oldText; btn.disabled = false; });
    },

    askDelete(id) { this.currentId = id; this.openDeleteModal(); },
    
    confirmDelete() {
        const fd = new FormData();
        fd.append('id', this.currentId);
        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=intangible/delete` : `${this.baseUrl.replace(/\/$/, '')}/intangible/delete`;
        
        fetch(url, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) {
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                this.closeDeleteModal();
                this.loadData();
            } else { if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error'); }
        });
    },

    openModal() { 
        const modal = document.getElementById('modalIntangible');
        if (modal) {
            modal.style.display = 'flex';
            modal.scrollTop = 0; 
            const content = modal.querySelector('.modal-content');
            if(content) content.scrollTop = 0;
        }
    },

    closeModal() { 
        const modal = document.getElementById('modalIntangible');
        if(modal) modal.style.display = 'none'; 
    },

    openDeleteModal() { const m = document.getElementById('deleteModal'); if(m) m.style.display = 'flex'; },
    closeDeleteModal() { const m = document.getElementById('deleteModal'); if(m) m.style.display = 'none'; },
    openImportModal() { const m = document.getElementById('importModal'); if(m) m.style.display = 'flex'; },
    closeImportModal() { const m = document.getElementById('importModal'); if(m) m.style.display = 'none'; },

    esc(str) { return str ? str.replace(/</g, "&lt;") : '-'; },
    
    renderLink(id, path, text, root) {
        const el = document.getElementById(id);
        if(!el) return;
        
        if (path) {
            const fullUrl = `${root}/${path}`;
            el.innerHTML = `
                <div style="margin-top: 8px; padding: 6px;">
                    <span style="font-size: 12px; color: #666; margin-right: 5px;">Current file:
                    </span>
                    <a href="${fullUrl}" target="_blank" style="font-size: 12px; color: #3498db; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-file-pdf"></i> View ${text}
                    </a>
                </div>`;
        } else {
            el.innerHTML = '';
        }
    }
};

window.openImportModal = () => IntangibleManager.openImportModal();
window.closeImportModal = () => IntangibleManager.closeImportModal();

document.addEventListener('DOMContentLoaded', () => {
    const s = document.querySelector('script[data-base-url]');
    if(s) IntangibleManager.init(s.getAttribute('data-base-url'));
});