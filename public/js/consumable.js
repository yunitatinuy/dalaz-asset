const ConsumableManager = {
    baseUrl: '',
    currentId: null,
    isEdit: false,
    consumableData: [],
    filteredData: [],
    usageEditor: null, 
    
    // PAGINASI
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,
    
    init(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
        console.log('ConsumableManager initialized. BaseURL:', this.baseUrl);
        this.loadConsumables();
        this.initEventListeners();

        // Event Listener untuk Search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', (e) => {
                this.handleSearch(e.target.value);
            });
        }
    },
    
    initEventListeners() {
        // 1. Listener Form Submit (Tambah/Edit)
        const form = document.getElementById('consumableForm');
        if (form) {
            form.onsubmit = e => this.submit(e);
        }

        // 2. Listener Form Submit (Import CSV) 
        const importForm = document.getElementById('importForm');
        if (importForm) {
            importForm.onsubmit = e => this.importCSV(e);
        }

        // 3. Preview Gambar saat memilih file
        const addPictures = document.getElementById('add-pictures');
        if (addPictures) {
            addPictures.addEventListener('change', (e) => {
                this.previewImages(e.target.files, 'preview-container');
            });
        }

        // 4. Validasi PDF & Tampilkan Nama File
        const docInput = document.getElementById('doc_support');
        if (docInput) {
            docInput.addEventListener('change', function() {
                const file = this.files[0];
                const infoBox = document.getElementById('pdf-upload-info');
                const fileNameSpan = document.getElementById('pdf-filename');

                if (file) {
                    // Batas 2MB (2 * 1024 * 1024 bytes)
                    const maxSize = 2 * 1024 * 1024; 
                    
                    if (file.size > maxSize) {
                        if (typeof AppHelpers !== 'undefined' && AppHelpers.showToast) {
                            AppHelpers.showToast('PDF too large. Max 2MB.', 'warning');
                        } else {
                            alert('PDF too large. Max 2MB.');
                        }
                        
                        this.value = ''; // Reset input
                        if(infoBox) infoBox.style.display = 'none'; // Sembunyikan info
                    } else {
                        // Tampilkan nama file
                        if(fileNameSpan) fileNameSpan.textContent = file.name;
                        if(infoBox) infoBox.style.display = 'block';
                    }
                } else {
                    // Jika user cancel pilih file
                    if(infoBox) infoBox.style.display = 'none';
                }
            });
        }
    },

    loadConsumables() {
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=consumable/getAll` 
            : `${this.baseUrl}/consumable/getAll`;

        console.log('Fetching data from:', endpoint);

        const tbody = document.getElementById('consumableTableBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center">Loading data...</td></tr>';

        fetch(endpoint)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.consumableData = d.data;
                    this.filteredData = d.data; 
                    this.currentPage = 1;
                    
                    this.updatePaginationState();
                    this.renderTable();
                    this.renderPagination();
                } else {
                    AppHelpers.showToast(d.message || 'Failed to load data', 'error');
                    if (tbody) tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">${d.message}</td></tr>`;
                }
            })
            .catch(e => {
                console.error('Error fetching data:', e);
                AppHelpers.showToast('Error koneksi data', 'error');
                if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Failed to connect the server</td></tr>';
            });
    },

    handleSearch(keyword) {
        const term = keyword.toLowerCase();
        
        this.filteredData = this.consumableData.filter(item => {
            return (
                (item.item_name && item.item_name.toLowerCase().includes(term)) ||
                (item.item_code && item.item_code.toLowerCase().includes(term)) ||
                (item.merk && item.merk.toLowerCase().includes(term)) ||
                (item.location_name && item.location_name.toLowerCase().includes(term)) ||
                (item.category_name && item.category_name.toLowerCase().includes(term))
            );
        });

        this.currentPage = 1; 
        this.updatePaginationState();
        this.renderTable();
        this.renderPagination();
    },

    updatePaginationState() {
        this.totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
        if (this.totalPages === 0) this.totalPages = 1; 
    },

    renderTable() {
        const tbody = document.getElementById('consumableTableBody');
        if (!tbody) return;

        if (this.filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">Data not found</td></tr>';
            return;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedData = this.filteredData.slice(startIndex, endIndex);

        tbody.innerHTML = paginatedData.map((item, i) => {
            const displayNumber = startIndex + i + 1;
            
            // Format Kondisi
            const kondisi = item.condition_status === 'good' 
                ? '<span style="color:green;font-weight:bold;">Good</span>' 
                : '<span style="color:red;font-weight:bold;">Damaged</span>';

            const detailUrl = this.baseUrl.includes('?') 
                ? `${this.baseUrl}&url=consumable/detail/${item.id}` 
                : `${this.baseUrl}/consumable/detail/${item.id}`;

            return `
            <tr>
                <td>${displayNumber}</td>
                <td><strong>${this.esc(item.item_name)}</strong></td>
                <td><span class="badge badge-asset">${this.esc(item.item_code)}</span></td>
                <td><strong>${item.quantity || 0}</strong></td> 
                <td>${this.esc(item.uom || '-')}</td>
                <td>${this.esc(item.location_name || '-')}</td>
                <td>${this.esc(item.category_name || '-')}</td>
                <td>${kondisi}</td>
                <td>
                    <div class="action-buttons" style="display:flex; gap:5px; justify-content:center;">
                        <button style="background-color: #17a2b8; color: white;" onclick="window.location.href='${detailUrl}'" title="View Detail">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-edit" onclick="ConsumableManager.edit(${item.id})" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" onclick="ConsumableManager.confirmDel(${item.id})" title="Delete"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </td>
            </tr>
            `;
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
                return `<a href="#" onclick="event.preventDefault(); ConsumableManager.goToPage(${page})">${text}</a>`;
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

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.renderTable();
        this.renderPagination();
    },

    esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    },

    exportPDF(id) {
        if (!id) return;
        // Deteksi format URL
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=consumable/exportPDF&id=${id}` 
            : `${this.baseUrl}/consumable/exportPDF/${id}`;
        window.open(endpoint, '_blank');
    },

    // CRUD
    add() {
        this.isEdit = false;
        this.currentId = null;
        document.getElementById('modalTitle').textContent = 'Add Inventory';
        document.getElementById('consumableForm').reset();
        document.getElementById('itemId').value = '';

        const qtyField = document.getElementById('quantity');
        if (qtyField) {
            qtyField.readOnly = false;
            qtyField.style.backgroundColor = '';
            qtyField.value = 0;
        }
        
        document.getElementById('min_order').value = 6;
        
        const previewContainer = document.getElementById('preview-container');
        if (previewContainer) previewContainer.innerHTML = '';
        
        const existingPics = document.getElementById('existing-pictures');
        if (existingPics) existingPics.innerHTML = '';

        const docLinkDiv = document.getElementById('current_doc');
        if (docLinkDiv) docLinkDiv.innerHTML = '';
        
        AppHelpers.showModal('consumableModal');
    },
    
    edit(id) {
        this.isEdit = true;
        this.currentId = id;
        document.getElementById('modalTitle').textContent = 'Edit Inventory';
        
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=consumable/getById&id=${id}` 
            : `${this.baseUrl}/consumable/getById&id=${id}`;

        fetch(endpoint)
            .then(r => r.json())
            .then(d => {
                if (!d.success) throw new Error(d.message || 'Failed to fetch data');
                const item = d.data;
                document.getElementById('itemId').value = item.id;

                const setVal = (domId, dbVal) => {
                    const el = document.getElementById(domId);
                    if(el) el.value = dbVal || '';
                };

                setVal('item_name', item.item_name);
                setVal('item_code', item.item_code);
                setVal('merk', item.merk);
                setVal('responsible_person', item.responsible_person);
                setVal('assigned_to', item.assigned_to);
                setVal('uom', item.uom);
                setVal('min_order', item.min_order);
                
                const qtyField = document.getElementById('quantity');
                if (qtyField) {
                    qtyField.value = item.quantity;
                    qtyField.readOnly = true;
                    qtyField.style.backgroundColor = '#e9ecef';
                }

                setVal('location_id', item.location_id);
                setVal('category_id', item.category_id);
                setVal('supporting_vendor', item.supporting_vendor);
                setVal('condition_status', item.condition_status);
                
                // Tampilkan Gambar
                this.displayExistingPictures(item.pictures);

                // Tampilkan Dokumen PDF
                const docInput = document.getElementById('doc_support');
                const pdfInfoBox = document.getElementById('pdf-upload-info'); 
                const currentPdfContainer = document.getElementById('current-pdf-container'); 
                const viewPdfBtn = document.getElementById('btn-view-pdf'); 

                // 1. Reset input upload & sembunyikan info upload baru
                if (docInput) docInput.value = ''; 
                if (pdfInfoBox) pdfInfoBox.style.display = 'none';

                // 2. Cek apakah ada PDF lama di database
                if (currentPdfContainer && viewPdfBtn) {
                    if (item.doc_support) {
                        // Bersihkan URL agar valid
                        const cleanBase = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
                        const pdfUrl = `${cleanBase}/${item.doc_support}`;
                        
                        viewPdfBtn.href = pdfUrl;
                        currentPdfContainer.style.display = 'block'; // Tampilkan kotak "Current File"
                    } else {
                        currentPdfContainer.style.display = 'none'; // Sembunyikan jika tidak ada file
                    }
                }

                AppHelpers.showModal('consumableModal');
            })
            .catch(e => {
                console.error(e);
                AppHelpers.showToast('Error: ' + e.message, 'error');
            });
    },
    
    submit(e) {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);
        
        if (this.isEdit && this.currentId) {
            fd.append('id', this.currentId);
        }

        const btn = form.querySelector('button[type="submit"]');
        const txt = btn.textContent;
        btn.textContent = 'Saving...';
        btn.disabled = true;
        
        const action = this.isEdit ? 'consumable/edit' : 'consumable/add';
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=${action}` 
            : `${this.baseUrl}/${action}`;

        fetch(endpoint, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                AppHelpers.showToast(data.message || 'Successfully saved', 'success');
                AppHelpers.closeModal('consumableModal');
                this.loadConsumables();
            } else {
                AppHelpers.showToast(data.message || 'Failed to save', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            AppHelpers.showToast('System error occurred', 'error');
        })
        .finally(() => {
            btn.textContent = txt;
            btn.disabled = false;
        });
    },
    
    confirmDel(id) {
        this.currentId = id;
        AppHelpers.showModal('deleteModal');
    },
    
    delete() {
        const fd = new FormData();
        fd.append('id', this.currentId);
        
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=consumable/delete` 
            : `${this.baseUrl}/consumable/delete`;

        fetch(endpoint, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    AppHelpers.showToast('Data deleted', 'success');
                    AppHelpers.closeModal('deleteModal');
                    this.loadConsumables();
                } else {
                    AppHelpers.showToast(d.message || 'Failed to delete', 'error');
                }
            })
            .catch(e => AppHelpers.showToast('Error: ' + e.message, 'error'));
    },
    
    previewImages(files, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '';

        Array.from(files).forEach(file => {
            if (file.size > 2 * 1024 * 1024) {
                AppHelpers.showToast(`File ${file.name} too large (Max 2MB)`, 'warning');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'display:inline-block; position:relative; margin:5px;';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width:80px; height:80px; object-fit:cover; border-radius:4px; border:1px solid #ddd;';
                
                wrapper.appendChild(img);
                container.appendChild(wrapper);
            }
            reader.readAsDataURL(file);
        });
    },

    displayExistingPictures(pictures) {
        const container = document.getElementById('existing-pictures');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!pictures) return;
        
        try {
            let pictureArray = [];
            
            if (typeof pictures === 'string') {
                if (pictures.trim().startsWith('[') || pictures.trim().startsWith('{')) {
                    pictureArray = JSON.parse(pictures);
                } else {
                    pictureArray = [pictures];
                }
            } else if (Array.isArray(pictures)) {
                pictureArray = pictures;
            }
            
            if (pictureArray.length === 0) {
                return;
            }
            
            pictureArray.forEach((pic) => {
                const cleanBase = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
                const cleanPic = pic.replace(/^\/?public\//, '');
                const imgUrl = `${cleanBase}/public/${cleanPic}`;
                
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'display:inline-block; position:relative; margin:5px;';
                
                const img = document.createElement('img');
                img.src = imgUrl;
                img.style.cssText = 'width:80px; height:80px; object-fit:cover; border-radius:4px; border:1px solid #ddd; cursor:pointer;';
                img.onclick = () => window.open(imgUrl, '_blank'); 
                
                img.onerror = function() {
                    this.style.display = 'none'; 
                };
                
                wrapper.appendChild(img);
                container.appendChild(wrapper);
            });
        } catch (e) {
            console.error('Error parsing pictures:', e);
        }
    },

    importCSV(e) {
        e.preventDefault();
        const form = e.target;
        const fileInput = form.querySelector('input[type="file"]');
        
        if (!fileInput.files || fileInput.files.length === 0) {
            AppHelpers.showToast('Please select a CSV file first.', 'warning');
            return;
        }

        const formData = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML; 
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
        btn.disabled = true;

        // URL diarahkan ke consumable/import (bukan importCSV)
        const endpoint = this.baseUrl.includes('?') 
            ? `${this.baseUrl}&url=consumable/import` 
            : `${this.baseUrl}/consumable/import`;

        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(res => res.text()) 
        .then(text => {
            try {
                const data = JSON.parse(text); 
                if (data.success) {
                    let msg = data.message;
                    if(data.debug_errors && data.debug_errors.length > 0) {
                        AppHelpers.showToast(msg + ' (Some rows failed, check Console)', 'warning');
                        console.warn('Import Issues:\n' + data.debug_errors.join('\n'));
                    } else {
                        AppHelpers.showToast(msg, 'success');
                    }
                    window.closeImportModal();
                    this.loadConsumables(); 
                    form.reset();
                } else {
                    AppHelpers.showToast(data.message, 'error');
                }
            } catch (err) {
                console.error("PHP / SYSTEM ERROR OCCURRED:\n", text);
                AppHelpers.showToast('System Error! Press F12 and check the Console tab.', 'error');
            }
        })
        .catch(err => {
            console.error("NETWORK ERROR:", err);
            AppHelpers.showToast('Failed to process data. Please check your internet connection.', 'error');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
};

window.openAddModal = () => ConsumableManager.add();
window.confirmDelete = () => ConsumableManager.delete();
window.closeModal = () => AppHelpers.closeModal('consumableModal');
window.closeDeleteModal = () => AppHelpers.closeModal('deleteModal');
window.openImportModal = () => AppHelpers.showModal('importModal');
window.closeImportModal = () => AppHelpers.closeModal('importModal');

document.addEventListener('DOMContentLoaded', () => {
    // Cek apakah kita ada di halaman consumable (cari tabelnya)
    if (document.getElementById('consumableTableBody')) {
        const scriptTag = document.querySelector('script[data-base-url]');
        const baseUrl = scriptTag ? scriptTag.getAttribute('data-base-url') : 'http://localhost/dalaz-asset/public/index.php?url=';
        
        ConsumableManager.init(baseUrl);
    }
});