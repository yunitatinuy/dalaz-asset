const AppHelpers = {
    showToast(msg, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) {
            console.error('The #toastContainer element was not found.!');
            return;
        }

        let cssClass = 'toast-warning'; 
        let icon = '！'; 
        
        if (type === 'success') {
            cssClass = 'toast-success';
            icon = '✓';
        } else if (type === 'error') {
            cssClass = 'toast-error';
            icon = '✕';
        }

        const toast = document.createElement('div');
        toast.className = `toast ${cssClass}`;
        
        const toastIcon = document.createElement('span');
        toastIcon.className = 'toast-icon';
        toastIcon.textContent = icon;
        
        const toastMessage = document.createElement('div');
        toastMessage.className = 'toast-message';
        toastMessage.textContent = msg;

        toast.appendChild(toastIcon);
        toast.appendChild(toastMessage);
        
        container.appendChild(toast);

        // Hapus toast setelah 3 detik
        setTimeout(() => {
            toast.classList.add('removing'); 
            toast.addEventListener('animationend', () => {
                toast.remove();
            });
        }, 3000);
    },
    
    showModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            
            // RESET SCROLL KE ATAS setiap kali modal dibuka
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
        }
    },
    
    closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
        }
    },
    
    esc(s) {
        return s ? String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])) : '';
    },

    renderPagination(containerId, pagination, searchFunctionName) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        if (!pagination || !pagination.totalPages || pagination.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        const { page, totalPages } = pagination;

        if (page > 1) {
            html += `<a href="#" onclick="event.preventDefault(); ${searchFunctionName}(${page - 1})">Prev</a>`;
        } else {
            html += `<span class="disabled">Prev</span>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            if (i === page) {
                html += `<span class="current">${i}</span>`;
            } else if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
                html += `<a href="#" onclick="event.preventDefault(); ${searchFunctionName}(${i})">${i}</a>`;
            } else if (i === page - 2 || i === page + 2) {
                html += `<span>...</span>`;
            }
        }

        if (page < totalPages) {
            html += `<a href="#" onclick="event.preventDefault(); ${searchFunctionName}(${page + 1})">Next »</a>`;
        } else {
            html += `<span class="disabled">Next »</span>`;
        }

        container.innerHTML = html;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    document.onkeydown = e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
        }
    };
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.onclick = e => {
            if (e.target === m) {
                m.style.display = 'none';
            }
        };
    });
});
