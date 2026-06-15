const DetailManager = {
    logs: [], 
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,
    baseUrl: '',

    init(logsData, baseUrl) {
        // Amankan data agar selalu berbentuk array
        this.logs = Array.isArray(logsData) ? logsData : (logsData ? Object.values(logsData) : []);
        this.baseUrl = baseUrl ? baseUrl.replace(/\/$/, '') : ''; 

        this.totalPages = Math.ceil(this.logs.length / this.itemsPerPage);
        if (this.totalPages === 0) this.totalPages = 1;

        this.renderTable();
        this.renderPagination();
    },

    renderTable() {
        const tbody = document.getElementById('detailHistoryBody');
        if (!tbody) return;

        // Jika data kosong -> Tampilkan Data not found
        if (this.logs.length === 0) {
            tbody.innerHTML = `<tr>
                <td colspan="6" class="text-center" style="color: #999; padding: 20px 10px;">
                    <span style="font-size: 15px;">Data not found.</span>
                </td>
            </tr>`;
            return;
        }

        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const pageData = this.logs.slice(start, end);

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
                statusBadge = `<span class="badge badge-in">STOCK IN</span>`;
                qtyChange = `<span style="color: #28a745; font-weight: bold;">+${amount}</span>`;
            } else {
                statusBadge = `<span class="badge badge-out">STOCK OUT</span>`;
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
            html += `<a href="#" onclick="event.preventDefault(); DetailManager.goToPage(${this.currentPage - 1})">« Prev</a>`;
        } else {
            html += `<span class="disabled">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                html += `<span class="current">${i}</span>`;
            } else {
                html += `<a href="#" onclick="event.preventDefault(); DetailManager.goToPage(${i})">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            html += `<a href="#" onclick="event.preventDefault(); DetailManager.goToPage(${this.currentPage + 1})">Next »</a>`;
        } else {
            html += `<span class="disabled">Next »</span>`;
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