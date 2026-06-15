const LandBuildingManager = {
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
        if (searchInput) {
            searchInput.addEventListener('keyup', (e) => this.handleSearch(e.target.value));
        }

        const form = document.getElementById('formLandBuilding');
        if (form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                this.saveData();
            };
        }

        const photoInput = document.getElementById('photos');
        if (photoInput) {
            photoInput.addEventListener('change', (e) => this.previewImages(e.target.files));
        }

        const importForm = document.getElementById('importForm');
        if (importForm) {
            importForm.onsubmit = (e) => {
                e.preventDefault();
                this.submitImport(new FormData(importForm));
            };
        }

        // Validasi Site Plan (Max 2MB)
        const sitePlanInput = document.getElementById('site_plan_path');
        if (sitePlanInput) {
            sitePlanInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file && file.type === 'application/pdf' && file.size > 2 * 1024 * 1024) {
                    if (typeof AppHelpers !== 'undefined' && AppHelpers.showToast) {
                        AppHelpers.showToast('Site Plan PDF too large. Max 2MB.', 'warning');
                    } else {
                        alert('Site Plan PDF too large. Max 2MB.');
                    }
                    this.value = ''; 
                }
            });
        }
        
        // Validasi Document (Max 2MB)
        const docInput = document.getElementById('document_path');
        if (docInput) {
            docInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file && file.size > 2 * 1024 * 1024) {
                    if (typeof AppHelpers !== 'undefined' && AppHelpers.showToast) {
                        AppHelpers.showToast('Document too large. Max 2MB.', 'warning');
                    } else {
                        alert('Document too large. Max 2MB.');
                    }
                    this.value = ''; 
                }
            });
        }
    },

    loadData() {
        let url = this.baseUrl.includes('index.php') 
            ? `${this.baseUrl}&url=landbuilding/getAll` 
            : `${this.baseUrl.replace(/\/$/, '')}/landbuilding/getAll`;

        fetch(url)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.allData = d.data || [];
                    this.filteredData = this.allData;
                    this.currentPage = 1;
                    this.updatePaginationState();
                    this.renderTable();
                    this.renderPagination();
                } else {
                    this.showErrorTable(d.message);
                }
            })
            .catch(e => {
                console.error(e);
                this.showErrorTable('Failed to load data.');
            });
    },

    showErrorTable(msg) {
        const tbody = document.getElementById('tableBody');
        if(tbody) tbody.innerHTML = `<tr><td colspan="9" class="text-center" style="padding:20px; color:#666">${msg}</td></tr>`;
    },

    renderTable() {
        const tbody = document.getElementById('tableBody');
        if (!tbody) return;

        if (this.filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center" style="padding:20px">No data found</td></tr>';
            return;
        }

        const start = (this.currentPage - 1) * this.itemsPerPage;
        const data = this.filteredData.slice(start, start + this.itemsPerPage);

        tbody.innerHTML = data.map((item, i) => {
            const statusRaw = (item.usage_status || '').toLowerCase().trim();
            let statusBadge = '';

            if (statusRaw === 'used' || statusRaw === 'digunakan' || statusRaw === 'active') {
                statusBadge = '<span class="badge badge-in" style="background:#d4edda; color:#155724; padding:5px 10px; border-radius:4px; font-weight:600; font-size:12px;">Used</span>';
            } else {
                statusBadge = '<span class="badge badge-out" style="background:#f8d7da; color:#721c24; padding:5px 10px; border-radius:4px; font-weight:600; font-size:12px;">Not Used</span>';
            }

            return `
                <tr>
                    <td>${start + i + 1}</td>
                    <td><span class="badge" style="background:#e3f2fd; color:#0d47a1; padding:5px 10px; border-radius:4px; font-weight:600; font-size:12px;">${this.esc(item.asset_code)}</span></td>
                    <td style="font-weight:600">${this.esc(item.asset_name)}</td>
                    <td>${this.esc(item.location_name)}</td>
                    <td>${this.esc(item.category_name)}</td>
                    <td>${this.esc(item.user_name)}</td>
                    <td>${this.esc(item.condition)}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="action-buttons" style="display:flex; gap:5px; justify-content:center;">
                            <button class="btn-edit" onclick="LandBuildingManager.edit(${item.id})" title="Edit" style="background:#17a2b8; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;"><i class="fas fa-edit"></i></button>
                            <button class="btn-delete" onclick="LandBuildingManager.askDelete(${item.id})" title="Delete" style="background:#dc3545; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    submitImport(formData) {
        const btn = document.querySelector('#importForm button[type="submit"]');
        const oldText = btn.innerText;
        btn.innerText = 'Importing...';
        btn.disabled = true;

        let url = this.baseUrl.includes('index.php') 
            ? `${this.baseUrl}&url=landbuilding/import` 
            : `${this.baseUrl.replace(/\/$/, '')}/landbuilding/import`;

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                    else alert(res.message);
                    
                    this.closeImportModal();
                    this.loadData();
                    document.getElementById('importForm').reset();
                } else {
                    if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error');
                    else alert(res.message);
                }
            })
            .catch(e => {
                console.error(e);
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast('Connection error', 'error');
            })
            .finally(() => { 
                btn.innerText = oldText; 
                btn.disabled = false; 
            });
    },

    // CRUD: ADD & EDIT
    add() {
        this.isEdit = false;
        this.currentId = null;
        
        const title = document.getElementById('modalTitle');
        if(title) title.innerText = 'Add New Data';
        
        const form = document.getElementById('formLandBuilding');
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

        this.clearPreviews();
        this.openModal();
    },

    edit(id) {
        this.isEdit = true;
        this.currentId = id;
        const title = document.getElementById('modalTitle');
        if(title) title.textContent = 'Edit Data';
        
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=landbuilding/getById&id=${id}` 
            : `${this.baseUrl}/landbuilding/getById&id=${id}`;

        fetch(endpoint)
            .then(r => r.json())
            .then(d => {
                if (!d.success) throw new Error(d.message);
                const item = d.data;

                const setVal = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = val || '';
                };

                setVal('id', item.id);
                setVal('asset_code', item.asset_code);
                setVal('asset_name', item.asset_name);
                setVal('responsible_person', item.responsible_person);
                setVal('user_id', item.user_id);
                setVal('location_id', item.location_id);
                setVal('category_id', item.category_id);
                setVal('certificate_number', item.certificate_number);
                setVal('certificate_date', item.certificate_date);
                setVal('address', item.address);
                setVal('surface_area', item.surface_area);
                setVal('intended_use', item.intended_use);
                setVal('condition', item.condition);

                const usageStatusEl = document.getElementById('usage_status');
                if (usageStatusEl) {
                    usageStatusEl.value = item.usage_status; 
                }

                // Reset Input File
                ['site_plan_path', 'document_path', 'photos'].forEach(f => {
                    const el = document.getElementById(f);
                    if(el) el.value = '';
                });

                // Link Site Plan Lama
                const spLink = document.getElementById('current_site_plan');
                if (spLink) {
                    spLink.innerHTML = item.site_plan_path 
                        ? `<a href="${this.cleanUrl(item.site_plan_path)}" target="_blank" style="color:blue">View Current Site Plan</a>` 
                        : '';
                }

                // Link Document Lama
                const docLink = document.getElementById('current_document');
                if (docLink) {
                    docLink.innerHTML = item.document_path 
                        ? `<a href="${this.cleanUrl(item.document_path)}" target="_blank" style="color:blue">View Current Document</a>` 
                        : '';
                }

                // Preview Photo
                this.displayExistingPhotos(item.photos);

                this.openModal('landBuildingModal');
            })
            .catch(e => {
                if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(e.message, 'error');
            });
    },

    saveData() {
        const form = document.getElementById('formLandBuilding');
        const fd = new FormData(form);
        
        // Jika Add, hapus ID lama
        if (!this.isEdit) {
            fd.delete('id');
        }

        const btn = form.querySelector('.btn-save');
        const oldText = btn ? btn.innerText : 'Save';
        
        if(btn) { btn.innerText = 'Saving...'; btn.disabled = true; }

        const endpoint = this.isEdit ? 'landbuilding/edit' : 'landbuilding/add';
        let url = this.baseUrl.includes('index.php') 
            ? `${this.baseUrl}&url=${endpoint}` 
            : `${this.baseUrl.replace(/\/$/, '')}/${endpoint}`;

        fetch(url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                    this.closeModal();
                    this.loadData();
                } else {
                    if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error');
                }
            })
            .catch(e => console.error(e))
            .finally(() => { 
                if(btn) { btn.innerText = oldText; btn.disabled = false; }
            });
    },

    // DELETE
    askDelete(id) {
        this.currentId = id;
        this.openDeleteModal();
    },

    confirmDelete() {
        const fd = new FormData();
        fd.append('id', this.currentId);
        let url = this.baseUrl.includes('index.php') 
            ? `${this.baseUrl}&url=landbuilding/delete` 
            : `${this.baseUrl.replace(/\/$/, '')}/landbuilding/delete`;

        fetch(url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'success');
                    this.closeDeleteModal();
                    this.loadData();
                } else {
                    if(typeof AppHelpers !== 'undefined') AppHelpers.showToast(res.message, 'error');
                }
            });
    },

    // SEARCH & PAGINASI
    handleSearch(keyword) {
        const term = keyword.toLowerCase();
        this.filteredData = this.allData.filter(item => {
            return (
                (item.asset_code && item.asset_code.toLowerCase().includes(term)) ||
                (item.asset_name && item.asset_name.toLowerCase().includes(term)) ||
                (item.location_name && item.location_name.toLowerCase().includes(term)) ||
                (item.user_name && item.user_name.toLowerCase().includes(term))
            );
        });
        this.currentPage = 1;
        this.updatePaginationState();
        this.renderTable();
        this.renderPagination();
    },

    updatePaginationState() {
        this.totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
        if(this.totalPages === 0) this.totalPages = 1;
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
                return `<a href="#" onclick="event.preventDefault(); LandBuildingManager.goToPage(${page})">${text}</a>`;
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

    openModal() { 
        const modal = document.getElementById('modalLandBuilding');
        if (modal) {
            modal.style.display = 'flex';
            modal.scrollTop = 0;
            const content = modal.querySelector('.modal-content');
            if (content) content.scrollTop = 0;
        }
    },

    closeModal() { 
        const modal = document.getElementById('modalLandBuilding');
        if (modal) modal.style.display = 'none'; 
    },

    openDeleteModal() { document.getElementById('deleteModal').style.display = 'flex'; },
    closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; },
    openImportModal() { document.getElementById('importModal').style.display = 'flex'; },
    closeImportModal() { document.getElementById('importModal').style.display = 'none'; },

    // HELPERS
    esc(str) { return str ? str.replace(/</g, "&lt;") : '-'; },
    
    clearPreviews() { 
        const els = ['current_site_plan', 'current_document', 'preview_photos'];
        els.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = '';
        });
    },

    displayExistingPhotos(jsonString) {
        const container = document.getElementById('preview_photos');
        if (!container) return;
        
        container.innerHTML = '';
        if (!jsonString) return;

        try {
            let photos = [];
            if (typeof jsonString === 'string') {
                photos = JSON.parse(jsonString);
            } else if (Array.isArray(jsonString)) {
                photos = jsonString;
            }

            if (Array.isArray(photos)) {
                photos.forEach(path => {
                    const fullUrl = this.cleanUrl(path);
                    const wrapper = document.createElement('div');
                    wrapper.style.cssText = 'display:inline-block; margin:5px;';
                    
                    wrapper.innerHTML = `
                        <a href="${fullUrl}" target="_blank">
                            <img src="${fullUrl}" style="width:80px; height:80px; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
                        </a>
                    `;
                    container.appendChild(wrapper);
                });
            }
        } catch (e) {
            console.error("Error parsing photos:", e);
        }
    },

    previewImages(files) {
        const box = document.getElementById('preview_photos');
        if(!box) return;
        box.innerHTML = '';
        Array.from(files).forEach(f => {
            const r = new FileReader();
            r.onload = e => box.innerHTML += `<img src="${e.target.result}" style="width:80px; height:80px; object-fit:cover; border:1px solid #ddd; margin:5px; border-radius:4px;">`;
            r.readAsDataURL(f);
        });
    }, 

    cleanUrl(path) {
        const base = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
        return `${base}/${path}`;
    },
};

window.openAddModal = () => LandBuildingManager.add();
window.openImportModal = () => LandBuildingManager.openImportModal();
window.closeImportModal = () => LandBuildingManager.closeImportModal();
window.openDeleteModal = () => LandBuildingManager.openDeleteModal();
window.closeDeleteModal = () => LandBuildingManager.closeDeleteModal();
window.confirmDelete = () => LandBuildingManager.confirmDelete();

document.addEventListener('DOMContentLoaded', () => {
    const s = document.querySelector('script[data-base-url]');
    if(s) LandBuildingManager.init(s.getAttribute('data-base-url'));
});