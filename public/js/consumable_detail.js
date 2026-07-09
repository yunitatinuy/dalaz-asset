const DetailManager = {
    logs: [], 
    filteredLogs: [],
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,
    baseUrl: '',

    init(logsData, baseUrl) {
        // Amankan data agar selalu berbentuk array
        this.logs = Array.isArray(logsData) ? logsData : (logsData ? Object.values(logsData) : []);
        this.filteredLogs = [...this.logs];
        this.baseUrl = baseUrl ? baseUrl.replace(/\/$/, '') : ''; 

        this.initEventListeners();
        this.updatePaginationState();
        this.renderTable();
        this.renderPagination();
    },

    initEventListeners() {
        document.getElementById('filterDate')?.addEventListener('change', () => this.applyFilters());
        document.getElementById('filterStatus')?.addEventListener('change', () => this.applyFilters());
        document.getElementById('filterRemark')?.addEventListener('input', () => this.applyFilters());
    },

    applyFilters() {
        const dateVal = document.getElementById('filterDate').value;
        const statusVal = document.getElementById('filterStatus').value.toLowerCase();
        const remarkVal = document.getElementById('filterRemark').value.toLowerCase();

        // Lakukan penyaringan data
        this.filteredLogs = this.logs.filter(log => {
            const logDate = log.date || log.created_at || log.transaction_date || '';
            
            // Cek kecocokan (Jika input kosong, berarti cocok semua)
            const matchDate = !dateVal || logDate.startsWith(dateVal);
            const matchStatus = !statusVal || (log.status && log.status.toLowerCase() === statusVal);
            const matchRemark = !remarkVal || (log.remark && log.remark.toLowerCase().includes(remarkVal));

            return matchDate && matchStatus && matchRemark;
        });

        // Reset halaman ke 1 setiap kali filter digunakan
        this.currentPage = 1;
        this.updatePaginationState();
        this.renderTable();
        this.renderPagination();
    },

    resetFilters() {
        const fd = document.getElementById('filterDate');
        const fs = document.getElementById('filterStatus');
        const fr = document.getElementById('filterRemark');
        
        if(fd) fd.value = '';
        if(fs) fs.value = '';
        if(fr) fr.value = '';
        
        this.applyFilters(); // Terapkan ulang filter kosong
    },

    updatePaginationState() {
        this.totalPages = Math.ceil(this.filteredLogs.length / this.itemsPerPage);
        if (this.totalPages === 0) this.totalPages = 1;
    },

    renderTable() {
        const tbody = document.getElementById('detailHistoryBody');
        if (!tbody) return;

        if (this.filteredLogs.length === 0) {
            tbody.innerHTML = `<tr>
                <td colspan="6" class="text-center" style="color: #999; padding: 20px 10px;">
                    <span style="font-size: 15px;">No transaction records match your filter.</span>
                </td>
            </tr>`;
            return;
        }

        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const pageData = this.filteredLogs.slice(start, end);

        tbody.innerHTML = pageData.map((log, index) => {
            const rowNumber = start + index + 1;
            
            // Tangkap tanggal
            const dateRaw = log.date || log.created_at || log.transaction_date;
            const date = dateRaw ? new Date(dateRaw).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
            
            let statusBadge = '';
            let qtyChange = '';
            
            const amount = log.qty_change || log.quantity || 0;
            const currentBal = log.current_balance || log.current_stock || log.balance || '-';
            
            if (log.status === 'IN' || log.status === 'in') {
                statusBadge = `<span class="badge badge-in" style="background:#28a745; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">STOCK IN</span>`;
                qtyChange = `<span style="color: #28a745; font-weight: bold;">+${amount}</span>`;
            } else {
                statusBadge = `<span class="badge badge-out" style="background:#dc3545; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">STOCK OUT</span>`;
                qtyChange = `<span style="color: #dc3545; font-weight: bold;">-${amount}</span>`;
            }

            return `
                <tr>
                    <td style="text-align: center;">${rowNumber}</td>
                    <td style="text-align: center;">${date}</td>
                    <td style="text-align: center;">${statusBadge}</td>
                    <td style="text-align: center;">${qtyChange}</td>
                    <td style="text-align: center; font-size: 15px;"><strong style="color: #17a2b8;">${currentBal}</strong></td>
                    <td style="text-align: center;">${log.remark || '-'}</td> 
                </tr>
            `;
        }).join('');
    },

    renderPagination() {
        const container = document.getElementById('detailPagination');
        if (!container) return;

        let html = '';

        if (this.currentPage > 1) {
            html += `<a href="#" onclick="event.preventDefault(); DetailManager.goToPage(${this.currentPage - 1})" style="padding: 5px 10px; margin: 0 5px; background: #eee; border-radius: 4px; text-decoration: none; color: #333;">« Prev</a>`;
        } else {
            html += `<span class="disabled" style="padding: 5px 10px; margin: 0 5px; color: #ccc;">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                html += `<span class="current" style="padding: 5px 10px; margin: 0 5px; background: #dc3545; color: white; border-radius: 4px; font-weight: bold;">${i}</span>`;
            } else {
                html += `<a href="#" onclick="event.preventDefault(); DetailManager.goToPage(${i})" style="padding: 5px 10px; margin: 0 5px; background: #eee; border-radius: 4px; text-decoration: none; color: #333;">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            html += `<a href="#" onclick="event.preventDefault(); DetailManager.goToPage(${this.currentPage + 1})" style="padding: 5px 10px; margin: 0 5px; background: #eee; border-radius: 4px; text-decoration: none; color: #333;">Next »</a>`;
        } else {
            html += `<span class="disabled" style="padding: 5px 10px; margin: 0 5px; color: #ccc;">Next »</span>`;
        }

        container.innerHTML = html;
    },

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.renderTable();
        this.renderPagination();
    }
};