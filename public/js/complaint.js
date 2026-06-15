const ComplaintManager = {
    baseUrl: '',
    currentReturnId: null,
    complaintData: [],
    
    // PAGINASI
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,

    init(baseUrl) {
        this.baseUrl = baseUrl;
        this.loadDataFromTable();
        this.initEventListeners();
        this.renderPagination();
    },

    loadDataFromTable() {
        const tbody = document.getElementById('complaintTableBody'); 
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr');
        
        this.complaintData = Array.from(rows).map(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 8) return null; 
            
            return {
                no: cells[0].textContent.trim(),
                assetHtml: cells[1].innerHTML, 
                reportedBy: cells[2].textContent.trim(),
                description: cells[3].textContent.trim(),
                date: cells[4].textContent.trim(),
                statusHtml: cells[5].innerHTML, 
                adminResponse: cells[6].textContent.trim(),
                actionHtml: cells[7].innerHTML  
            };
        }).filter(item => item !== null);

        this.totalPages = Math.ceil(this.complaintData.length / this.itemsPerPage);
        this.renderPagination();
    },

    renderTable() {
        const tbody = document.getElementById('complaintTableBody');
        if (!tbody) return;

        if (!this.complaintData || this.complaintData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="padding: 20px; color: #999;">No complaints found.</td></tr>';
            return;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedData = this.complaintData.slice(startIndex, endIndex);
        const cellStyle = 'text-align: center; vertical-align: middle; font-size: 13px; padding: 10px; color: #555;';

        tbody.innerHTML = paginatedData.map((item, i) => {
            const displayNumber = startIndex + i + 1;
            
            return `
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="${cellStyle}">${displayNumber}</td>
                    <td style="${cellStyle}">${item.assetHtml}</td>
                    <td style="${cellStyle}">${item.reportedBy}</td>
                    <td style="${cellStyle}">${item.description}</td>
                    <td style="${cellStyle}">${item.date}</td>
                    <td style="${cellStyle}">${item.statusHtml}</td>
                    <td style="${cellStyle}">${item.adminResponse}</td>
                    <td style="${cellStyle}">${item.actionHtml}</td>
                </tr>
            `;
        }).join('');
    },

    renderPagination() {
        const paginationContainer = document.getElementById('paginationControls');
        if (!paginationContainer) return;

        paginationContainer.innerHTML = '';

        let paginationHTML = '';
        if (this.currentPage > 1) {
            paginationHTML += `<a href="#" onclick="event.preventDefault(); ComplaintManager.goToPage(${this.currentPage - 1})">« Prev</a>`;
        } else {
            paginationHTML += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                paginationHTML += `<span class="current">${i}</span>`;
            } else {
                paginationHTML += `<a href="#" onclick="event.preventDefault(); ComplaintManager.goToPage(${i})">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            paginationHTML += `<a href="#" onclick="event.preventDefault(); ComplaintManager.goToPage(${this.currentPage + 1})">Next »</a>`;
        } else {
            paginationHTML += `<span class="disabled">Next »</span>`;
        }

        paginationContainer.innerHTML = paginationHTML;
    },

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.renderTable();
        this.renderPagination();
        
        document.querySelector('#complaintTableBody')?.scrollIntoView({ behavior: 'smooth' });
    },

    initEventListeners() {
        const form = document.getElementById('responseForm');
        if (form) {
            form.onsubmit = e => this.submitResponse(e);
        }
    },

    openModal(returnId) {
        this.currentReturnId = returnId;
        
        const form = document.getElementById('responseForm');
        if (form) form.reset();
        
        const photoWrapper = document.getElementById('photo_container_wrapper');
        const photoContainer = document.getElementById('evidence_photos');
        if (photoWrapper) photoWrapper.style.display = 'none';
        if (photoContainer) photoContainer.innerHTML = '';

        // 2. Routing URL yang aman 
        let url = this.baseUrl.includes('index.php') 
            ? `${this.baseUrl}&url=complaint/getDetails&id=${returnId}` 
            : `${this.baseUrl.replace(/\/$/, '')}/complaint/getDetails?id=${returnId}`;
        
        fetch(url)
            .then(r => r.json())
            .then(d => {
                if (!d.success) throw new Error(d.message || 'Failed to fetch data');
                
                const data = d.data;
                
                const setVal = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = val || '';
                };

                // ISI INFO READ-ONLY
                setVal('return_id', data.return_id);
                setVal('asset_number', data.asset_number);
                setVal('equipment_name', data.equipment_name);
                setVal('user_name', data.user_name);
                setVal('return_date', data.return_date);
                setVal('return_time', data.return_time);

                // ISI DEFECT CAUSE
                let userReport = data.defect_cause;
                if (!userReport) {
                    userReport = "Status: " + (data.defect_description || 'Unknown');
                }
                setVal('defect_cause', userReport);

                // FOTO EVIDENCE
                if (data.photos) {
                    try {
                        const photos = JSON.parse(data.photos);
                        if (photos && photos.length > 0) {
                            if (photoWrapper) photoWrapper.style.display = 'block';
                            
                            const cleanBase = this.baseUrl.replace(/index\.php\?url=/g, '').replace(/\/$/, '');
                            
                            photos.forEach(photo => {
                                if (photoContainer) {
                                    photoContainer.innerHTML += `
                                        <a href="${cleanBase}/uploads/complaints/${photo}" target="_blank" title="Click to view full image">
                                            <img src="${cleanBase}/uploads/complaints/${photo}" 
                                                alt="Evidence" 
                                                style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; transition: transform 0.2s;"
                                                onmouseover="this.style.transform='scale(1.05)'"
                                                onmouseout="this.style.transform='scale(1)'">
                                        </a>
                                    `;
                                }
                            });
                        }
                    } catch (e) {
                        console.error("Error parsing photos JSON:", e);
                    }
                }

                // ISI BAGIAN ADMIN
                setVal('control_no', data.control_no);
                setVal('treatment', data.treatment);
                setVal('check_date', data.check_date);
                setVal('check_status', data.check_status);

                const modal = document.getElementById('responseModal');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.scrollTop = 0;
                    const content = modal.querySelector('.modal-content');
                    if (content) content.scrollTop = 0;
                }
            })
            .catch(e => this.showToast('Error: ' + e.message, 'error'));
    },

    closeModal() {
        const modal = document.getElementById('responseModal');
        if (modal) {
            modal.style.display = 'none';
        }
    },

    submitResponse(e) {
        e.preventDefault();
        
        const form = e.target;
        const fd = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        const txt = btn.textContent;
        
        btn.textContent = 'Saving...';
        btn.disabled = true;

        let url = this.baseUrl.includes('index.php') 
            ? `${this.baseUrl}&url=complaint/respond` 
            : `${this.baseUrl.replace(/\/$/, '')}/complaint/respond`;

        fetch(url, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                this.showToast(d.message || 'Success!', 'success');
                this.closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showToast(d.message || 'Failed', 'error');
            }
        })
        .catch(e => this.showToast('Error: ' + e.message, 'error'))
        .finally(() => {
            btn.textContent = txt;
            btn.disabled = false;
        });
    },

    showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
};

window.openResponseModal = (id) => ComplaintManager.openModal(id);
window.closeResponseModal = () => ComplaintManager.closeModal();
window.ComplaintManager = ComplaintManager;

document.addEventListener('DOMContentLoaded', () => {
    // Cek apakah tabel ada, baru init
    if (document.getElementById('complaintTableBody')) {
        const script = document.querySelector('script[data-base-url]');
        const baseUrl = script?.getAttribute('data-base-url');
        
        if (baseUrl) {
            ComplaintManager.init(baseUrl);
        } else {
            console.error('Base URL not found.');
        }
    }
});