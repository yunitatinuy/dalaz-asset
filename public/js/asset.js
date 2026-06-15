const AssetManager = {
    baseUrl: '',
    currentId: null,
    isEdit: false,
    assetData: [],
    filteredData: [],

    // PAGINASI
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,
    
    // SEARCH
    searchTimeout: null,

    init(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
        this.loadAssets();
        this.initEventListeners();
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', (e) => {
                this.handleSearch(e.target.value);
            });
        }
    },

    initEventListeners() {
        // Form Submit (Tambah/Edit)
        const form = document.getElementById('assetForm');
        if (form) {
            form.onsubmit = e => this.submit(e);
        }

        // Preview Image saat pilih file baru
        const picInput = document.getElementById('pictures');
        if (picInput) {
            picInput.onchange = function() { window.previewImage(this); };
        }

        // Form untuk Import CSV
        const importForm = document.getElementById('importForm');
        if (importForm) {
            importForm.onsubmit = e => this.submitImport(e);
        }
    },

    loadAssets() {
        const url = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=asset/getAll` 
            : `${this.baseUrl}/asset/getAll`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.assetData = data.data;
                    this.filteredData = data.data;
                    this.currentPage = 1;
                    
                    this.updatePaginationState();
                    this.renderTable();
                    this.renderPagination();
                } else {
                    AppHelpers.showToast('Failed to load data: ' + data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                const tbody = document.querySelector('#assetTable tbody');
                if(tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center">Failed to contact server</td></tr>';
            });
    },

    // SEARCH
    handleSearch(query) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            const term = query.toLowerCase();
            this.filteredData = this.assetData.filter(item => {
                return (
                    (item.asset_name && item.asset_name.toLowerCase().includes(term)) ||
                    (item.asset_code && item.asset_code.toLowerCase().includes(term)) ||
                    (item.owner && item.owner.toLowerCase().includes(term)) ||
                    (item.brand && item.brand.toLowerCase().includes(term))
                );
            });
            this.currentPage = 1;
            this.updatePaginationState();
            this.renderTable();
            this.renderPagination();
        }, 300);
    },

    renderTable() {
        const tbody = document.querySelector('#assetTable tbody');
        if (!tbody) return;

        if (this.filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">Data not found</td></tr>';
            return;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const pageData = this.filteredData.slice(startIndex, endIndex);

        tbody.innerHTML = pageData.map((item, index) => {
            const no = startIndex + index + 1;
            
            let statusBadge = '';
            const status = (item.description || '').toLowerCase();
            
            if (status === 'good') statusBadge = '<span class="badge badge-in">Good</span>';
            else if (status === 'damaged') statusBadge = '<span class="badge badge-out">Damaged</span>';
            else if (status === 'maintenance') statusBadge = '<span class="badge badge-warning">Maintenance</span>';
            else if (status === 'repair') statusBadge = '<span class="badge badge-info">Repair</span>';
            else statusBadge = `<span class="badge badge-secondary">${item.description || '-'}</span>`;

            return `
                <tr>
                    <td>${no}</td>
                    <td>
                        <div style="font-weight:600;">${AppHelpers.esc(item.asset_name)}</div>
                    </td>
                    <td>${AppHelpers.esc(item.asset_code)}</td>
                    <td>${item.quantity} Unit</td>
                    <td>${AppHelpers.esc(item.location_name) || '-'}</td>
                    <td>${AppHelpers.esc(item.category_name) || '-'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-view-qr" onclick="AssetManager.showQR(${item.id})" title="View QR Code">
                                <i class="fas fa-qrcode"></i>
                            </button>
                            <button class="btn-edit" onclick="AssetManager.edit(${item.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="AssetManager.confirmDel(${item.id})" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    updatePaginationState() {
        this.totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
        if (this.totalPages === 0) this.totalPages = 1;
    },

    renderPagination() {
        const container = document.getElementById('paginationControls');
        if (!container) return;
        
        let html = '';
        
        if (this.currentPage > 1) {
            html += `<a href="#" onclick="event.preventDefault(); AssetManager.goToPage(${this.currentPage - 1})">« Prev</a>`;
        } else {
            html += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                html += `<span class="current">${i}</span>`;
            } else {
                html += `<a href="#" onclick="event.preventDefault(); AssetManager.goToPage(${i})">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            html += `<a href="#" onclick="event.preventDefault(); AssetManager.goToPage(${this.currentPage + 1})">Next »</a>`;
        } else {
            html += `<span class="disabled">Next »</span>`;
        }

        container.innerHTML = html;
    },

    goToPage(page) {
        this.currentPage = page;
        this.renderTable();
        this.renderPagination();
    },

    // CRUD 
    add() {
        this.isEdit = false;
        this.currentId = null;
        document.getElementById('modalTitle').innerText = 'Add Office Equipment';
        document.getElementById('assetForm').reset();
        document.getElementById('assetId').value = '';
        
        // Reset preview gambar
        const imgPreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        if(imgPreview) {
            imgPreview.style.display = 'none';
            previewImg.src = '';
        }
        
        // Enable quantity
        const qty = document.getElementById('totalQuantity');
        if(qty) {
            qty.value = 1;
            qty.disabled = false;
            qty.style.backgroundColor = '';
        }

        AppHelpers.showModal('assetModal');
    },

    edit(id) {
        this.isEdit = true;
        this.currentId = id;
        document.getElementById('modalTitle').innerText = 'Edit Office Equipment';

        const url = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=asset/getById&id=${id}` 
            : `${this.baseUrl}/asset/getById&id=${id}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const d = data.data;
                    document.getElementById('assetId').value = d.id;
                    
                    // Helper Set Value
                    const setVal = (k, v) => { const el = document.getElementById(k); if(el) el.value = v || ''; };

                    setVal('assetName', d.asset_name);
                    setVal('assetCode', d.asset_code);
                    setVal('owner', d.owner);
                    setVal('responsible_person', d.responsible_person);
                    setVal('assigned_to', d.assigned_to);
                    setVal('brand', d.brand);
                    setVal('serial_number', d.serial_number);
                    setVal('purchase_date', d.purchase_date);
                    setVal('location', d.location_id);
                    setVal('category', d.category_id);
                    setVal('details', d.details);
                    setVal('status', d.description); 

                    // Quantity Disabled saat Edit
                    const qty = document.getElementById('totalQuantity');
                    if(qty) {
                        qty.value = d.quantity;
                        qty.disabled = true;
                        qty.style.backgroundColor = '#e9ecef';
                    }

                    // Spesifikasi
                    setVal('capacity', d.capacity);
                    setVal('dimensions', d.dimensions);
                    setVal('weight', d.weight);
                    setVal('color', d.color);
                    setVal('maintenance_frequency', d.maintenance_frequency);
                    setVal('vendor', d.vendor);

                    const imgPreview = document.getElementById('imagePreview');
                    const previewImg = document.getElementById('previewImg');
                    
                    if (d.pictures) {                        
                        let rootUrl = this.baseUrl;
                        if (rootUrl.includes('index.php')) {
                            rootUrl = rootUrl.split('index.php')[0]; 
                        }
                        if (!rootUrl.endsWith('/')) {
                            rootUrl += '/';
                        }
                        
                        let imagePath = d.pictures;
                        if (!imagePath.includes('uploads/')) {
                            imagePath = 'uploads/assets/' + imagePath;
                        }
                        imagePath = imagePath.replace(/^public\//, '');

                        previewImg.src = rootUrl + imagePath;
                        imgPreview.style.display = 'block';
                    } else {
                        imgPreview.style.display = 'none';
                        previewImg.src = '';
                    }

                    AppHelpers.showModal('assetModal');
                } else {
                    AppHelpers.showToast('Failed to retrieve data', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                AppHelpers.showToast('Connection error', 'error');
            });
    },

    submit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        if (this.isEdit) {
            formData.append('id', this.currentId);
        }

        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerText;
        btn.innerText = 'Saving...';
        btn.disabled = true;

        const endpoint = this.isEdit ? 'asset/edit' : 'asset/add';
        const url = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=${endpoint}` 
            : `${this.baseUrl}/${endpoint}`;

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                AppHelpers.showToast(data.message || 'Saved successfully', 'success');
                AppHelpers.closeModal('assetModal');
                this.loadAssets();
            } else {
                AppHelpers.showToast(data.message || 'Failed to save', 'error');
            }
        })
        .catch(err => AppHelpers.showToast('System error', 'error'))
        .finally(() => {
            btn.innerText = originalText;
            btn.disabled = false;
        });
    },

    confirmDel(id) {
        this.currentId = id;
        AppHelpers.showModal('deleteModal');
    },

    delete() {
        const formData = new FormData();
        formData.append('id', this.currentId);

        const url = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=asset/delete` 
            : `${this.baseUrl}/asset/delete`;

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                AppHelpers.showToast('Data successfully deleted', 'success');
                AppHelpers.closeModal('deleteModal');
                this.loadAssets();
            } else {
                AppHelpers.showToast(data.message || 'Failed to delete', 'error');
            }
        })
        .catch(err => AppHelpers.showToast('Connection error', 'error'));
    },

    // IMPOR CSV
    submitImport(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;

        const url = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=asset/import` 
            : `${this.baseUrl}/asset/import`;

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let msg = data.message;
                if(data.errors && data.errors.length > 0) {
                    msg += ' (Check the console for details on specific error lines.)';
                    console.warn('Import Errors:', data.errors);
                }
                AppHelpers.showToast(msg, 'success');
                window.closeImportModal();
                this.loadAssets();
                form.reset();
            } else {
                AppHelpers.showToast(data.message, 'error');
            }
        })
        .catch(err => AppHelpers.showToast('Failed to import: ' + err, 'error'))
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    },

    // QR CODE
    showQR(id) {
        const item = this.assetData.find(x => x.id == id);
        if (!item) return;

        const qrImg = document.getElementById('qrImage');
        const qrInfo = document.getElementById('qrAssetInfo');
        const dlBtn = document.getElementById('qrDownloadBtn');
        
        let rootUrl = this.baseUrl;
        if (rootUrl.includes('index.php')) {
            rootUrl = rootUrl.split('index.php')[0];
        }
        if (!rootUrl.endsWith('/')) {
            rootUrl += '/';
        }
        
        if (item.qr_code && item.qr_code.length > 50) {
            qrImg.src = `data:image/png;base64,${item.qr_code}`;
        } else {
            // Fallback ke generator jika base64 kosong
            qrImg.src = `${rootUrl}qr_generator.php?code=${encodeURIComponent(item.asset_code)}`;
        }
        
        qrInfo.innerText = `${item.asset_name} (${item.asset_code})`;
        
        // Set tombol download ke gambar yang ditampilkan
        dlBtn.href = qrImg.src; 
        dlBtn.download = `QR_${item.asset_code}.png`;

        AppHelpers.showModal('qrModal');
    },

    // Preview gambar (Helper)
    previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const img = document.getElementById('previewImg');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
        }
    }
};

window.AssetManager = AssetManager;
window.openAddModal = () => AssetManager.add();
window.openImportModal = () => AppHelpers.showModal('importModal');
window.closeImportModal = () => AppHelpers.closeModal('importModal');
window.closeModal = () => AppHelpers.closeModal('assetModal');
window.closeQRModal = () => AppHelpers.closeModal('qrModal');
window.closeDeleteModal = () => AppHelpers.closeModal('deleteModal');
window.confirmDelete = () => AssetManager.delete();
window.previewImage = (input) => AssetManager.previewImage(input);

// Init saat DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('assetTable')) {
        const scriptTag = document.querySelector('script[data-base-url]');
        const baseUrl = scriptTag ? scriptTag.getAttribute('data-base-url') : 'http://localhost/dalaz-asset/public/index.php?url=';
        AssetManager.init(baseUrl);
    }
});