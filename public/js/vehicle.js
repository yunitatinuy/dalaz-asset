const VehicleManager = {
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

        const form = document.getElementById('formVehicle');
        if (form) form.onsubmit = (e) => { e.preventDefault(); this.saveData(); };

        const photoInput = document.getElementById('photos');
        if (photoInput) photoInput.addEventListener('change', (e) => this.previewImages(e.target.files));

        const importForm = document.getElementById('importForm');
        if (importForm) {
            importForm.onsubmit = (e) => {
                e.preventDefault();
                this.submitImport(new FormData(importForm));
            };
        }

        // Validasi PDF (Max 2MB)
        ['bpkb_path', 'stnk_path'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file && file.type === 'application/pdf' && file.size > 2 * 1024 * 1024) {
                        if(typeof AppHelpers !== 'undefined') AppHelpers.showToast('File PDF too large. Max 2MB.', 'warning');
                        else alert('File PDF too large. Max 2MB.');
                        this.value = '';
                    }
                });
            }
        });
    },

    submitImport(formData) {
        const btn = document.querySelector('#importForm button[type="submit"]');
        const oldText = btn.innerText;
        btn.innerText = 'Importing...';
        btn.disabled = true;

        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=vehicle/import` : `${this.baseUrl.replace(/\/$/, '')}/vehicle/import`;

        fetch(url, { method: 'POST', body: formData }).then(r => r.json()).then(res => {
            if (res.success) {
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                this.closeImportModal();
                this.loadData();
                document.getElementById('importForm').reset();
            } else {
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error');
            }
        }).finally(() => { btn.innerText = oldText; btn.disabled = false; });
    },

    loadData() {
        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=vehicle/getAll` : `${this.baseUrl.replace(/\/$/, '')}/vehicle/getAll`;
        fetch(url).then(r => r.json()).then(d => {
            if (d.success) {
                this.allData = d.data || [];
                this.filteredData = this.allData;
                this.currentPage = 1;
                this.updatePaginationState();
                this.renderTable();
                this.renderPagination();
            } else { this.showErrorTable(d.message); }
        }).catch(e => { this.showErrorTable('No vehicle data found.'); });
    },

    showErrorTable(msg) { 
        const tbody = document.getElementById('tableBody');
        if(tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center" style="padding:20px; color:#666">${msg}</td></tr>`; 
    },

    handleSearch(keyword) {
        const term = keyword.toLowerCase();
        this.filteredData = this.allData.filter(item => (
            (item.asset_code && item.asset_code.toLowerCase().includes(term)) ||
            (item.brand && item.brand.toLowerCase().includes(term)) ||
            (item.license_plate && item.license_plate.toLowerCase().includes(term))
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
            return `<tr>
                <td>${start + i + 1}</td>
                <td><span class="badge" style="background:#e3f2fd;color:#0d47a1;padding:5px 10px;border-radius:4px;font-weight:600;font-size:12px">${this.esc(item.asset_code)}</span></td>
                <td>${this.esc(item.vehicle_type)}</td>
                <td style="font-weight:600">${this.esc(item.brand)}</td>
                <td><span class="badge" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;padding:4px 8px;border-radius:4px;font-weight:600">${this.esc(item.license_plate)}</span></td>
                <td>${this.esc(item.user_name)}</td>
                <td>${this.esc(item.condition)}</td>
                <td>
                    <div class="action-buttons" style="display:flex;gap:5px;justify-content:center;">
                        <button class="btn-edit" onclick="VehicleManager.edit(${item.id})" style="background:#17a2b8;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" onclick="VehicleManager.askDelete(${item.id})" style="background:#dc3545;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer"><i class="fas fa-trash-alt"></i></button>
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
                return `<a href="#" onclick="event.preventDefault(); VehicleManager.goToPage(${page})">${text}</a>`;
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
        if(title) title.innerText = 'Add New Vehicle';
        
        const form = document.getElementById('formVehicle');
        if (form) {
            form.reset(); // Reset dasar

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
        
        this.clearPreviews();
        this.openModal();
    },

    edit(id) {
        this.isEdit = true;
        this.currentId = id;
        
        const title = document.getElementById('modalTitle');
        if(title) title.innerText = 'Edit Data';

        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=vehicle/getById&id=${id}` : `${this.baseUrl.replace(/\/$/, '')}/vehicle/getById?id=${id}`;
        
        fetch(url).then(r => r.json()).then(res => {
            if(res.success) {
                const d = res.data;
                
                const form = document.getElementById('formVehicle');
                if(form) {
                    Object.keys(d).forEach(key => {
                        const input = form.querySelector(`[name="${key}"], #${key}`);
                        if(input && input.type !== 'file') {
                            input.value = d[key] || '';
                        }
                    });
                }

                const idField = document.getElementById('id') || document.querySelector('input[name="id"]');
                if (idField) idField.value = d.id;

                const root = this.baseUrl.replace(/index\.php\?url=.*$/, '').replace(/\/$/, '');
                this.renderLink('link_bpkb', d.bpkb_path, 'BPKB', root);
                this.renderLink('link_stnk', d.stnk_path, 'STNK', root);
                this.renderPhotos(d.photos, root);
                
                this.openModal();
            } else { 
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast('Data not found', 'error'); 
            }
        });
    },

    saveData() {
        const form = document.getElementById('formVehicle');
        const fd = new FormData(form);
        
        if (!this.isEdit) {
            fd.delete('id');
        }

        const endpoint = this.isEdit ? 'vehicle/edit' : 'vehicle/add';
        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=${endpoint}` : `${this.baseUrl.replace(/\/$/, '')}/${endpoint}`;
        
        fetch(url, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) {
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                this.closeModal();
                this.loadData();
            } else { 
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error'); 
            }
        });
    },

    askDelete(id) { 
        this.currentId = id; 
        this.openDeleteModal(); 
    },
    
    confirmDelete() {
        const fd = new FormData();
        fd.append('id', this.currentId);
        let url = this.baseUrl.includes('index.php') ? `${this.baseUrl}&url=vehicle/delete` : `${this.baseUrl.replace(/\/$/, '')}/vehicle/delete`;
        
        fetch(url, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) {
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                this.closeDeleteModal();
                this.loadData();
            } else { 
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error'); 
            }
        });
    },

    openModal(id = 'modalVehicle') { 
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            modal.scrollTop = 0;
            const content = modal.querySelector('.modal-content');
            if (content) content.scrollTop = 0;
        }
    },

    closeModal(id = 'modalVehicle') { 
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'none'; 
    },

    openDeleteModal() { const m = document.getElementById('deleteModal'); if(m) m.style.display = 'flex'; },
    closeDeleteModal() { const m = document.getElementById('deleteModal'); if(m) m.style.display = 'none'; },
    openImportModal() { const m = document.getElementById('importModal'); if(m) m.style.display = 'flex'; },
    closeImportModal() { const m = document.getElementById('importModal'); if(m) m.style.display = 'none'; },

    esc(str) { return str ? str.replace(/</g, "&lt;") : '-'; },
    
    clearPreviews() { 
        ['link_bpkb', 'link_stnk', 'preview_photos', 'existing-pictures'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = '';
        });
    },

    renderLink(id, path, text, root) {
        const el = document.getElementById(id);
        if(!el) return;
        if(path) el.innerHTML = `<a href="${root}/${path}" target="_blank" style="color:blue;text-decoration:underline">View ${text}</a>`;
        else el.innerHTML = '';
    },

    renderPhotos(json, root) {
        const box = document.getElementById('existing-pictures');
        if(!box) return;
        box.innerHTML = '';
        if(!json) return;
        try { 
            JSON.parse(json).forEach(p => box.innerHTML += `<img src="${root}/${p}" style="width:80px;height:80px;object-fit:cover;border:1px solid #ddd;margin:5px;border-radius:4px">`); 
        } catch(e) {}
    },

    previewImages(files) {
        const box = document.getElementById('preview_photos');
        if(!box) return;
        box.innerHTML = '';
        Array.from(files).forEach(f => {
            const r = new FileReader();
            r.onload = e => box.innerHTML += `<img src="${e.target.result}" style="width:80px;height:80px;object-fit:cover;border:1px solid #ddd;margin:5px;border-radius:4px">`;
            r.readAsDataURL(f);
        });
    }
};

window.openAddModal = () => VehicleManager.add();
window.closeModal = () => VehicleManager.closeModal();
window.openImportModal = () => VehicleManager.openImportModal();
window.closeImportModal = () => VehicleManager.closeImportModal();
window.openDeleteModal = () => VehicleManager.openDeleteModal();
window.closeDeleteModal = () => VehicleManager.closeDeleteModal();
window.confirmDelete = () => VehicleManager.confirmDelete();

document.addEventListener('DOMContentLoaded', () => {
    const s = document.querySelector('script[data-base-url]');
    if(s) VehicleManager.init(s.getAttribute('data-base-url'));
});