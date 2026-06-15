const EquipmentManager = {
    baseUrl: '',
    currentId: null,
    isEdit: false,
    equipmentData: [], 
    filteredData: [], 
    usageEditor: null, // Quill Editor
    
    // PAGINASI
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,
    
    init(baseUrl) {
        this.baseUrl = baseUrl;
        this.loadEquipments();
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
        // Listener Form Submit (Tambah/Edit)
        const form = document.getElementById('equipmentForm');
        if (form) {
            form.onsubmit = e => this.submit(e);
        }

        // Inisialisasi Editor Quill
        this.initQuillEditor();

        // Preview Gambar saat memilih file
        const addPictures = document.getElementById('add-pictures');
        if (addPictures) {
            addPictures.addEventListener('change', (e) => {
                this.previewImages(e.target.files, 'add-preview-container');
            });
        }

        // validasi PDF
        const docInput = document.getElementById('doc_support');
        if (docInput) {
            docInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    // Batas 2MB
                    const maxSize = 2 * 1024 * 1024; 
                    
                    if (file.size > maxSize) {
                        AppHelpers.showToast('PDF files too large. Maximum size is 2MB.', 'warning');
                        this.value = ''; // Reset input agar tidak bisa disubmit
                    }
                }
            });
        }

        // Listener Form untuk Import CSV
        const importForm = document.getElementById('importForm');
        if (importForm) {
            importForm.onsubmit = e => this.importCSV(e);
        }
    },

    initQuillEditor() {
        // Cek apakah elemen container untuk editor ada
        const editorElement = document.getElementById('add-usage-editor');
        if (editorElement) {
            // Hapus instance lama jika ada (untuk menghindari duplikasi toolbar)
            if (this.usageEditor) {
                // Quill tidak memiliki method destroy yang sempurna, jadi kita kosongkan container
                editorElement.innerHTML = ''; 
            }

            this.usageEditor = new Quill('#add-usage-editor', {
                theme: 'snow',
                placeholder: 'Write usage steps here...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],        
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean'] // Tombol hapus format
                    ]
                }
            });
        }
    },

    loadEquipments() {
        const cleanBase = this.baseUrl.replace(/\/$/, ''); 
        const url = cleanBase.includes('?') ? `${cleanBase}&url=equipment/getAll` : `${cleanBase}/equipment/getAll`;

        fetch(url)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.equipmentData = d.data;
                    this.filteredData = d.data; 
                    this.currentPage = 1;
                    
                    this.updatePaginationState();
                    this.renderTable();
                    this.renderPagination();
                } else {
                    AppHelpers.showToast(d.message || 'Failed to load data', 'error');
                }
            })
            .catch(e => {
                console.error('Error fetching data:', e);
                AppHelpers.showToast('Error koneksi data', 'error');
                const tbody = document.querySelector('#equipmentTableBody');
                if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center">Failed to load data</td></tr>';
            });
    },

    handleSearch(keyword) {
        const term = keyword.toLowerCase();
        
        this.filteredData = this.equipmentData.filter(item => {
            return (
                (item.equipment_name && item.equipment_name.toLowerCase().includes(term)) ||
                (item.asset_number && item.asset_number.toLowerCase().includes(term)) ||
                (item.serial_number && item.serial_number.toLowerCase().includes(term)) ||
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

    // LOGIKA KALKULASI JATUH TEMPO
    calculateDueDate(calDate, freqStr) {
        if (!calDate || !freqStr) return '-';
        
        try {
            // 1. Parsing Tanggal Manual (YYYY-MM-DD) untuk menghindari masalah Timezone
            const parts = calDate.split('-');
            if (parts.length !== 3) return '-';
            
            const year = parseInt(parts[0]);
            const month = parseInt(parts[1]) - 1; // JS Month 0-11
            const day = parseInt(parts[2]);
            
            const lastCal = new Date(year, month, day);

            // 2. Parsing Frekuensi (Ambil angka saja)
            const freq = parseInt(String(freqStr).replace(/\D/g, ''));

            if (isNaN(lastCal.getTime()) || isNaN(freq) || freq === 0) return '-';

            // 3. Hitung Next Due Date
            const nextDue = new Date(year, month + freq, day);

            // 4. Hitung Warning Date (6 Bulan Sebelum)
            const warningDate = new Date(nextDue);
            warningDate.setMonth(nextDue.getMonth() - 6);

            const today = new Date();
            today.setHours(0,0,0,0);

            const options = { day: '2-digit', month: 'long', year: 'numeric' };
            const dateStr = nextDue.toLocaleDateString('en-GB', options);

            let style = 'color: green; font-weight: bold;'; 
            
            if (today >= nextDue) {
                // Lewat -> Merah
                style = 'color: #dc3545; font-weight: bold;'; 
            } else if (today >= warningDate) {
                // Warning -> Oranye
                style = 'color: #fd7e14; font-weight: bold;'; 
            }

            return `<span style="${style}">${dateStr}</span>`;
        } catch (e) {
            return '-';
        }
    },

    renderTable() {
        const tbody = document.querySelector('#equipmentTableBody');
        if (!tbody) return;

        if (this.filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">Data not found</td></tr>';
            return;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedData = this.filteredData.slice(startIndex, endIndex);

        tbody.innerHTML = paginatedData.map((e, i) => {
            const displayNumber = startIndex + i + 1;
            const qtyDisplay = e.quantity == 1 ? '1 Unit' : `${e.quantity} Unit`;
            
            let pdfLink = '';
            if (e.doc_support) {
                const cleanBase = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
                const pdfUrl = `${cleanBase}/${e.doc_support}`;
                pdfLink = `<br><a href="${pdfUrl}" target="_blank" style="font-size:11px; color:#007bff; text-decoration:none;"><i class="fas fa-paperclip"></i> Document</a>`;
            }

            const dueDateDisplay = this.calculateDueDate(e.calibration_date, e.maintenance_frequency);

            return `
            <tr>
                <td>${displayNumber}</td>
                <td>
                    <div style="font-weight:600;">${AppHelpers.esc(e.equipment_name)}</div>
                    ${pdfLink}
                </td>
                <td><span class="badge badge-in">${AppHelpers.esc(e.asset_number)}</span></td>
                <td>${AppHelpers.esc(e.serial_number) || '-'}</td>
                <td>${qtyDisplay}</td>
                <td>${AppHelpers.esc(e.location_name) || '-'}</td>
                <td>${AppHelpers.esc(e.category_name) || '-'}</td>
                <td>${dueDateDisplay}</td> 
                <td>
                    <div class="action-buttons">
                        <button class="btn-pdf" onclick="EquipmentManager.exportPDF(${e.id})" title="Export PDF"><i class="fas fa-file-pdf"></i></button>
                        <button class="btn-view-qr" onclick="EquipmentManager.showQRById(${e.id})" title="View QR"><i class="fas fa-qrcode"></i></button>
                        <button class="btn-edit" onclick="EquipmentManager.edit(${e.id})" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" onclick="EquipmentManager.confirmDel(${e.id})" title="Delete"><i class="fas fa-trash-alt"></i></button>
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
                return `<a href="#" onclick="event.preventDefault(); EquipmentManager.goToPage(${page})">${text}</a>`;
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

    showQRById(id) {
        const eq = this.equipmentData.find(item => item.id == id);
        if (!eq) {
            AppHelpers.showToast('Data not found', 'error');
            return;
        }
        
        const qrImg = document.getElementById('qrImage');
        const qrInfo = document.getElementById('qrEquipmentInfo');
        const downloadBtn = document.getElementById('qrDownloadBtn');
        
        const cleanBase = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
        
        qrImg.src = '';
        if (eq.qr_code && eq.qr_code.length > 50) {
            qrImg.src = `data:image/png;base64,${eq.qr_code}`;
        } else {
            qrImg.src = `${cleanBase}/qr_generator.php?code=${encodeURIComponent(eq.asset_number)}`;
        }

        qrInfo.textContent = `${eq.equipment_name} (${eq.asset_number})`;
        downloadBtn.href = qrImg.src;
        downloadBtn.download = `QR_${eq.asset_number}.png`;
        
        AppHelpers.showModal('qrModal');
    },

    exportPDF(id) {
        if (!id) return;
        const cleanBase = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
        const url = cleanBase.includes('?') ? `${cleanBase}&url=equipment/exportPDF&id=${id}` : `${cleanBase}/equipment/exportPDF/${id}`;
        window.open(url, '_blank');
    },

    // CRUD
    add() {
        this.isEdit = false;
        this.currentId = null;
        document.getElementById('modalTitle').textContent = 'Add New Equipment';
        document.getElementById('equipmentForm').reset();
        document.getElementById('equipmentId').value = '';

        const qtyField = document.getElementById('quantity');
        if (qtyField) {
            qtyField.disabled = false;
            qtyField.style.backgroundColor = '';
            qtyField.value = 1;
        }
        
        if (this.usageEditor) {
            this.usageEditor.setText('');
        }
        
        const addPreview = document.getElementById('add-preview-container');
        if (addPreview) addPreview.innerHTML = '';
        
        const existingPics = document.getElementById('existing-pictures');
        if (existingPics) existingPics.innerHTML = '';

        const docLinkDiv = document.getElementById('current_doc_link');
        if (docLinkDiv) docLinkDiv.innerHTML = '';
        
        AppHelpers.showModal('equipmentModal');
    },
    
    edit(id) {
        this.isEdit = true;
        this.currentId = id;
        document.getElementById('modalTitle').textContent = 'Edit Equipment';
        
        const cleanBase = this.baseUrl.replace(/\/$/, ''); 
        const url = cleanBase.includes('?') ? `${cleanBase}&url=equipment/getById&id=${id}` : `${cleanBase}/equipment/getById&id=${id}`;

        fetch(url)
            .then(r => r.json())
            .then(d => {
                if (!d.success) throw new Error(d.message || 'Failed to fetch data');
                
                const eq = d.data;
                document.getElementById('equipmentId').value = eq.id;

                const setVal = (domId, dbVal) => {
                    const el = document.getElementById(domId);
                    if(el) el.value = dbVal || '';
                };

                setVal('equipment_name', eq.equipment_name);
                setVal('asset_number', eq.asset_number);
                setVal('owner', eq.owner);
                setVal('responsible_person', eq.responsible_person);
                setVal('type', eq.type);
                setVal('serial_number', eq.serial_number);
                setVal('manufacturer', eq.manufacturer);
                setVal('purchase_date', eq.purchase_date);
                setVal('location_id', eq.location_id);
                setVal('category_id', eq.category_id);
                setVal('equipment_details', eq.equipment_details);
                setVal('condition_status', eq.condition_status);
                
                // Matiin Quantity saat Edit
                const qtyField = document.getElementById('quantity');
                if (qtyField) {
                    qtyField.value = eq.quantity;
                    qtyField.disabled = true;
                    qtyField.style.backgroundColor = '#e9ecef';
                }

                setVal('capacity', eq.capacity);
                setVal('dimensions', eq.dimensions);
                setVal('weight', eq.weight);
                setVal('storage_temp', eq.storage_temp);
                setVal('humidity', eq.humidity);
                setVal('calibration_cert_no', eq.calibration_cert_no);
                setVal('calibration_date', eq.calibration_date);
                setVal('maintenance_frequency', eq.maintenance_frequency);
                setVal('supporting_vendor', eq.supporting_vendor);
                
                // Isi Quill Editor dengan HTML dari DB 
                if (this.usageEditor && eq.usage_steps) {
                    this.usageEditor.root.innerHTML = eq.usage_steps;
                } else if (document.getElementById('usage_steps')) {
                    document.getElementById('usage_steps').value = eq.usage_steps || '';
                }
                
                // Tampilkan Gambar
                this.displayExistingPictures(eq.pictures);

                // Tampilkan Dokumen
                const docLinkDiv = document.getElementById('current_doc_link');
                const docInput = document.getElementById('doc_support');
                if (docInput) docInput.value = ''; 

                if (docLinkDiv) {
                    if (eq.doc_support) {
                        const baseUrlClean = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
                        const pdfUrl = `${baseUrlClean}/${eq.doc_support}`;
                        docLinkDiv.innerHTML = `Current file : <a href="${pdfUrl}" target="_blank" style="color:#007bff; text-decoration:underline;">View PDF</a>`;
                    } else {
                        docLinkDiv.innerHTML = '<span style="color:#999; font-size:11px;">No document yet</span>';
                    }
                }

                AppHelpers.showModal('equipmentModal');
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
        
        // Ambil isi HTML dari Quill dan masukkan ke FormData
        if (this.usageEditor) {
            fd.append('usage_steps', this.usageEditor.root.innerHTML);
        }

        if (this.isEdit && this.currentId) {
            fd.append('id', this.currentId);
        }

        const btn = form.querySelector('button[type="submit"]');
        const txt = btn.textContent;
        btn.textContent = 'Menyimpan...';
        btn.disabled = true;
        
        const endpoint = this.isEdit ? 'equipment/edit' : 'equipment/add';
        const cleanBase = this.baseUrl.replace(/\/$/, ''); 
        const url = cleanBase.includes('?') ? `${cleanBase}&url=${endpoint}` : `${cleanBase}/${endpoint}`;

        fetch(url, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                AppHelpers.showToast(data.message || 'Successfully saved', 'success');
                AppHelpers.closeModal('equipmentModal');
                this.loadEquipments();
            } else {
                AppHelpers.showToast(data.message || 'Failed to save', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            AppHelpers.showToast('A system error has occurred', 'error');
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
        
        const cleanBase = this.baseUrl.replace(/\/$/, ''); 
        const url = cleanBase.includes('?') ? `${cleanBase}&url=equipment/delete` : `${cleanBase}/equipment/delete`;

        fetch(url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    AppHelpers.showToast('Data deleted', 'success');
                    AppHelpers.closeModal('deleteModal');
                    this.loadEquipments();
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
                container.innerHTML = '<small style="color: #999;">No photos saved</small>';
                return;
            }
            
            pictureArray.forEach((pic) => {
                const baseUrlClean = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
                const cleanPic = pic.replace(/^\/?public\//, '');
                const imgUrl = `${baseUrlClean}/public/${cleanPic}`;
                
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
            container.innerHTML = '<small style="color: red;">Error loading image</small>';
        }
    },

    importCSV(e) {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.textContent;

        btn.textContent = 'Importing...';
        btn.disabled = true;

        const cleanBase = this.baseUrl.replace(/\/$/, ''); 
        const url = cleanBase.includes('?') ? `${cleanBase}&url=equipment/import` : `${cleanBase}/equipment/import`;

        fetch(url, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                let msg = data.message;
                if(data.debug_errors && data.debug_errors.length > 0) {
                    msg += '\nNote: Some rows failed (check console)';
                    console.warn('Import Errors:', data.debug_errors);
                }
                AppHelpers.showToast(msg, 'success');
                window.closeImportModal();
                this.loadEquipments(); 
                form.reset();
            } else {
                AppHelpers.showToast(data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            AppHelpers.showToast('Failed to import. Check connection.', 'error');
        })
        .finally(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        });
    }
};

window.openAddModal = () => EquipmentManager.add();
window.confirmDelete = () => EquipmentManager.delete();
window.closeModal = () => AppHelpers.closeModal('equipmentModal');
window.closeQRModal = () => AppHelpers.closeModal('qrModal');
window.closeDeleteModal = () => AppHelpers.closeModal('deleteModal');
window.openImportModal = () => AppHelpers.showModal('importModal');
window.closeImportModal = () => AppHelpers.closeModal('importModal');

document.addEventListener('DOMContentLoaded', () => {
    // Cek apakah kita ada di halaman equipment (cari tabelnya)
    if (document.getElementById('equipmentTableBody')) {
        const scriptTag = document.querySelector('script[data-base-url]');
        const baseUrl = scriptTag ? scriptTag.getAttribute('data-base-url') : 'http://localhost/dalaz-asset/public/index.php?url=';
        
        EquipmentManager.init(baseUrl);
    }
});