const SettingManager = {
    baseUrl: '',
    form: null,
    
    init(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
        this.form = document.getElementById('settingForm');
        
        if (this.form) {
            this.initEventListeners();
        }
    },
    
    initEventListeners() {
        this.form.onsubmit = e => this.handleSave(e);

        const cancelBtn = document.getElementById('cancelButton');
        if (cancelBtn) {
            cancelBtn.onclick = () => window.history.back();
        }

        const newPass = document.getElementById('newPass');
        if (newPass) {
            newPass.addEventListener('input', () => this.updateStrength());
        }
    },
    
    handleSave(e) {
        e.preventDefault();
        
        const currentPass = document.getElementById('currentPass').value;
        const newPass = document.getElementById('newPass').value;
        const confirmPass = document.getElementById('confirmPass').value;

        if (newPass || currentPass || confirmPass) {
            if (!currentPass) {
                this.showToast('Current password is required to change password', 'error');
                return;
            }
            if (newPass.length < 6) {
                this.showToast('New password must be at least 6 characters long', 'warning');
                return;
            }
            if (newPass !== confirmPass) {
                this.showToast('New passwords do not match', 'error');
                return;
            }
        }
        
        // Setup Tombol Loading
        const btn = this.form.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        btn.textContent = 'Saving...';
        btn.disabled = true;

        const fd = new FormData(this.form);
        
        const endpoint = this.baseUrl.includes('index.php') 
            ? `${this.baseUrl}&url=setting/update` 
            : `${this.baseUrl}/setting/update`;

        fetch(endpoint, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                this.showToast(d.message, 'success');
                
                // Reset field password setelah sukses
                document.getElementById('currentPass').value = '';
                document.getElementById('newPass').value = '';
                document.getElementById('confirmPass').value = '';
                
                const strengthContainer = document.getElementById('strengthContainer');
                if(strengthContainer) strengthContainer.style.display = 'none';
                
            } else {
                this.showToast(d.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            this.showToast('An error occurred. Please check console.', 'error');
        })
        .finally(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        });
    },

    updateStrength() {
        const pass = document.getElementById('newPass').value;
        const container = document.getElementById('strengthContainer');
        const bar = document.getElementById('strengthBar');
        const text = document.getElementById('strengthText');

        if (!pass) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        let strength = 0;
        let label = 'Weak';

        // Logika perhitungan kekuatan password
        if (pass.length >= 6) strength += 25;
        if (pass.match(/[a-z]+/)) strength += 25;
        if (pass.match(/[A-Z]+/)) strength += 25;
        if (pass.match(/[0-9]+/) || pass.match(/[\W]+/)) strength += 25;

        // Style Barnya
        if (strength < 50) {
            label = 'Weak';
            bar.style.backgroundColor = '#dc3545'; 
        } else if (strength < 75) {
            label = 'Medium';
            bar.style.backgroundColor = '#ffc107'; 
        } else {
            label = 'Strong';
            bar.style.backgroundColor = '#28a745'; 
        }

        bar.style.width = strength + '%';
        text.textContent = 'Strength: ' + label;
    },

    showToast(message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container'; 
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`; 
        
        // Ikon berdasarkan tipe
        let icon = 'check-circle';
        if (type === 'error') icon = 'times-circle';
        if (type === 'warning') icon = 'exclamation-circle';

        // Isi konten
        toast.innerHTML = `<i class="fas fa-${icon}" style="margin-right:8px;"></i> <span>${message}</span>`;
        
        // Style animasi JS
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = 'all 0.3s ease';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';

        // Masukkan ke container
        container.appendChild(toast);

        // Animasi Masuk
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        });

        // Hilang otomatis setelah 3 detik
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            
            // Hapus dari DOM
            setTimeout(() => {
                if (container.contains(toast)) {
                    container.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
};

// Init Script saat DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    const s = document.querySelector('script[data-base-url]');
    if (s && document.getElementById('settingForm')) {
        SettingManager.init(s.getAttribute('data-base-url'));
    }
});