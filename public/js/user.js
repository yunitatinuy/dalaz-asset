const UserManager = {
    baseUrl: '',
    currentId: null,
    isEdit: false,
    userData: [], 
    
    // PAGINASI
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,

    init(baseUrl) {
        this.baseUrl = baseUrl;
        console.log('UserManager started. BaseURL:', this.baseUrl);
        
        this.loadUsers();
        this.setupEvents();
    },

    setupEvents() {
        const form = document.getElementById('userForm');
        if (form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                this.saveUser();
            };
        }

        const roleSelect = document.getElementById('role');
        if (roleSelect) {
            roleSelect.onchange = () => this.toggleCredentialFields();
        }
    },

    toggleCredentialFields() {
        const role = document.getElementById('role').value;
        const credentialFields = document.getElementById('credentialFields');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        if (role === 'admin') {
            credentialFields.style.display = 'block';
            usernameInput.required = !this.isEdit;
            emailInput.required = true; 
            passwordInput.required = !this.isEdit;
        } else {
            credentialFields.style.display = 'none';
            usernameInput.required = false;
            emailInput.required = false;
            passwordInput.required = false;
            
            usernameInput.value = '';
            emailInput.value = ''; 
            passwordInput.value = '';
        }
    },

    loadUsers() {
        fetch(`${this.baseUrl}user/getAll`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.userData = d.data;
                    this.totalPages = Math.ceil(this.userData.length / this.itemsPerPage);
                    this.renderTable();
                    this.renderPagination();
                } else {
                    this.showError('Failed to load users');
                }
            })
            .catch(e => {
                console.error('Error:', e);
                this.showError('Error loading data');
            });
    },

    renderTable() {
        const tbody = document.getElementById('userTableBody');
        if (!tbody) return;

        if (!this.userData || this.userData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No data found</td></tr>';
            return;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedData = this.userData.slice(startIndex, endIndex);

        tbody.innerHTML = paginatedData.map((item, i) => {
            const displayNumber = startIndex + i + 1;
            
            const roleText = item.role === 'admin' 
                ? '<span style="color:#dc3545; font-weight:700; font-size:12px;">ADMIN</span>'
                : '<span style="color:#28a745; font-weight:700; font-size:12px;">USER</span>';

            const hasQR = item.qr_code && item.qr_code !== 'null' && item.qr_code.length > 100;

            return `
                <tr>
                    <td>${displayNumber}</td>
                    <td><span class="badge" style="background:#e3f2fd; color:#0d47a1; padding: 5px 10px; border-radius: 4px; font-weight: 600;">#${item.id}</span></td>
                    <td><strong>${this.esc(item.employee_no || '-')}</strong></td>
                    <td>${this.esc(item.full_name)}</td>
                    <td>${this.esc(item.position || '-')}</td>
                    <td>${roleText}</td>
                    <td>
                        <div class="action-buttons">
                            ${hasQR ? `<button class="btn-action btn-view-qr" onclick="UserManager.showQR('${encodeURIComponent(item.qr_code)}', '${this.esc(item.employee_no)}', '${this.esc(item.full_name)}')">
                            <i class="fas fa-qrcode"></i>
                            </button>` : ''}
                            <button class="btn-edit" onclick="UserManager.editUser(${item.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="UserManager.deleteUser(${item.id})" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    renderPagination() {
        const paginationContainer = document.getElementById('paginationControls');
        if (!paginationContainer) return;

        const calculatedPages = Math.ceil(this.userData.length / this.itemsPerPage);
        this.totalPages = calculatedPages > 0 ? calculatedPages : 1;

        let paginationHTML = '';

        if (this.currentPage > 1) {
            paginationHTML += `<a onclick="UserManager.goToPage(${this.currentPage - 1})">« Prev</a>`;
        } else {
            paginationHTML += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                paginationHTML += `<span class="current">${i}</span>`;
            } else {
                paginationHTML += `<a onclick="UserManager.goToPage(${i})">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            paginationHTML += `<a onclick="UserManager.goToPage(${this.currentPage + 1})">Next »</a>`;
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
        document.querySelector('#userTableBody')?.scrollIntoView({ behavior: 'smooth' });
    },

    addUser() {
        this.isEdit = false;
        this.currentId = null;
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('userForm').reset();
        document.getElementById('role').value = 'user';
        this.toggleCredentialFields();
        this.openModal('userModal');
    },

    editUser(id) {
        this.isEdit = true;
        this.currentId = id;
        document.getElementById('modalTitle').textContent = 'Edit User';

        fetch(`${this.baseUrl}user/getById&id=${id}`)
            .then(r => r.json())
            .then(d => {
                if (d.success && d.data) {
                    const user = d.data;
                    document.getElementById('userId').value = user.id;
                    document.getElementById('username').value = user.username || '';
                    document.getElementById('email').value = user.email || ''; // Isi field email
                    document.getElementById('password').value = '';
                    document.getElementById('full_name').value = user.full_name;
                    document.getElementById('position').value = user.position || '';
                    document.getElementById('employee_no').value = user.employee_no || '';
                    document.getElementById('role').value = user.role;
                    
                    this.toggleCredentialFields();
                    this.openModal('userModal');
                } else {
                    this.showError('User not found');
                }
            })
            .catch(e => this.showError('Error loading data'));
    },

    saveUser() {
        const formData = new FormData(document.getElementById('userForm'));
        const url = this.isEdit 
            ? `${this.baseUrl}user/edit` 
            : `${this.baseUrl}user/add`;

        const btn = document.querySelector('#userForm button[type="submit"]');
        const originalText = btn.textContent;
        btn.textContent = 'Saving...';
        btn.disabled = true;

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.showSuccess(d.message || 'User saved successfully');
                    this.closeModal('userModal');
                    this.loadUsers();
                } else {
                    this.showError(d.message || 'Failed to save user');
                }
            })
            .catch(e => this.showError('Error: ' + e.message))
            .finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            });
    },

    showQR(qrBase64, employeeNo, fullName) {
        const qrImg = document.getElementById('qrImage');
        const qrLabel = document.getElementById('qrLabel');
        const downloadBtn = document.getElementById('downloadQRBtn');
        
        console.log('🔍 QR Data:', {
            base64Length: qrBase64 ? qrBase64.length : 0,
            employeeNo: employeeNo,
            fullName: fullName
        });
        
        if (!qrBase64 || qrBase64 === "null" || qrBase64.length < 100) {
            qrImg.src = '';
            qrLabel.textContent = 'QR Code not available';
            this.showWarning('QR Code not found. Please re-save this user.');
        } else {
            const cleanBase64 = decodeURIComponent(qrBase64);
            qrImg.src = `data:image/png;base64,${cleanBase64}`;
            qrLabel.textContent = `${fullName} (${employeeNo})`;
        }
        
        const baseUrlClean = this.baseUrl.replace('index.php?url=', '');
        downloadBtn.onclick = () => {
            window.location.href = `${baseUrlClean}download_qr.php?type=user&code=${encodeURIComponent(employeeNo)}&name=${encodeURIComponent(fullName)}`;
        };
        
        this.openModal('qrModal');
    },

    deleteUser(id) {
        this.currentId = id;
        this.openModal('deleteModal');
    },

    confirmDelete() {
        const formData = new FormData();
        formData.append('id', this.currentId);

        fetch(`${this.baseUrl}user/delete`, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.showSuccess('User deleted successfully');
                    this.closeModal('deleteModal');
                    this.loadUsers();
                } else {
                    this.showError(d.message || 'Failed to delete user');
                }
            })
            .catch(e => this.showError('Error: ' + e.message));
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

document.addEventListener('DOMContentLoaded', () => {
    const script = document.querySelector('script[data-base-url]');
    const baseUrl = script?.getAttribute('data-base-url');

    if (baseUrl && document.getElementById('userTableBody')) {
        UserManager.init(baseUrl);
    }
});