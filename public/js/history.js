const HistoryPage = {
    historyData: [],
    
    // Paginasi
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 0,
    tableType: 'summary', // Default ke summary

    init() {
        // DETEKSI TABEL
        const detailTable = document.getElementById('detailTable');
        const summaryTableBody = document.querySelector('.data-table tbody');

        if (detailTable) {
            this.tableType = 'detail';
            this.tableBodySelector = '#detailTableBody';
        } else if (summaryTableBody) {
            this.tableType = 'summary';
            this.tableBodySelector = '.data-table tbody';
        } else {
            return; // Tidak ada tabel history di halaman ini
        }

        console.log(`HistoryPage initialized: ${this.tableType} mode`);
        this.loadDataFromTable();
        this.renderPagination();
    },

    loadDataFromTable() {
        const tbody = document.querySelector(this.tableBodySelector);
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr');
        
        this.historyData = Array.from(rows).map(row => {
            const cells = row.querySelectorAll('td');
            
            // halaman detail
            if (this.tableType === 'detail') {
                if (cells.length < 7) return null;
                return {
                    no: cells[0].textContent.trim(),
                    borrowerName: cells[1].textContent.trim(),
                    noJdHtml: cells[2].innerHTML, 
                    client: cells[3].textContent.trim(),
                    borrowDate: cells[4].textContent.trim(),
                    returnDateHtml: cells[5].innerHTML, 
                    statusHtml: cells[6].innerHTML 
                };
            } 
            
            // halaman index
            else {
                if (cells.length < 7) return null;
                return {
                    index: cells[0].textContent.trim(),
                    name: cells[1].innerHTML,
                    totalOwned: cells[2].textContent.trim(),
                    stockAvailable: cells[3].textContent.trim(),
                    qtyBorrowed: cells[4].textContent.trim(),
                    status: cells[5].innerHTML, 
                    action: cells[6].innerHTML  
                };
            }
        }).filter(item => item !== null);

        this.totalPages = Math.ceil(this.historyData.length / this.itemsPerPage);
        this.renderTable();
    },

    renderTable() {
        const tbody = document.querySelector(this.tableBodySelector);
        if (!tbody) return;
        
        if (!this.historyData || this.historyData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding:20px;">No history records found.</td></tr>';
            return;
        }

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedData = this.historyData.slice(startIndex, endIndex);

        // RENDER HTML SESUAI TIPE TABEL
        tbody.innerHTML = paginatedData.map((item, i) => {
            const displayNumber = startIndex + i + 1;
            
            // TAMPILAN KHUSUS DETAIL
            if (this.tableType === 'detail') {
                return `
                    <tr>
                        <td class="text-center">${displayNumber}</td>
                        <td class="text-center">${item.borrowerName}</td>
                        <td class="text-center">${item.noJdHtml}</td>
                        <td class="text-center">${item.client}</td>
                        <td class="text-center">${item.borrowDate}</td>
                        <td class="text-center">${item.returnDateHtml}</td>
                        <td class="text-center">${item.statusHtml}</td>
                    </tr>
                `;
            } 
            
            // TAMPILAN KHUSUS SUMMARY (INDEX)
            else {
                const stockVal = parseInt(item.stockAvailable) || 0;
                const borrowedVal = parseInt(item.qtyBorrowed) || 0;
                const stockColor = stockVal > 0 ? '#28a745' : '#dc3545';
                const borrowedColor = borrowedVal > 0 ? '#d9534f' : 'inherit';

                return `
                    <tr>
                        <td class="text-center">${displayNumber}</td>
                        <td class="text-center">${item.name}</td>
                        <td class="text-center" style="font-weight: 600;">${item.totalOwned}</td>
                        <td class="text-center" style="font-weight: 600; color: ${stockColor};">
                            ${item.stockAvailable}
                        </td>
                        <td class="text-center" style="font-weight: 600; color: ${borrowedColor};">
                            ${item.qtyBorrowed}
                        </td>
                        <td class="text-center">${item.status}</td>
                        <td class="text-center">${item.action}</td>
                    </tr>
                `;
            }
        }).join('');
    },

    renderPagination() {
        const paginationContainer = document.getElementById('paginationControls');
        if (!paginationContainer) return;

        paginationContainer.innerHTML = '';

        let paginationHTML = '';

        if (this.currentPage > 1) {
            paginationHTML += `<a onclick="HistoryPage.goToPage(${this.currentPage - 1})" class="page-btn">« Prev</a>`;
        } else {
            paginationHTML += `<span class="page-btn disabled">« Prev</span>`;
        }

        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                paginationHTML += `<span class="page-btn active">${i}</span>`;
            } else {
                paginationHTML += `<a onclick="HistoryPage.goToPage(${i})" class="page-btn">${i}</a>`;
            }
        }

        if (this.currentPage < this.totalPages) {
            paginationHTML += `<a onclick="HistoryPage.goToPage(${this.currentPage + 1})" class="page-btn">Next »</a>`;
        } else {
            paginationHTML += `<span class="page-btn disabled">Next »</span>`;
        }

        paginationContainer.innerHTML = paginationHTML;
    },

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.renderTable();
        this.renderPagination();
        
        // Scroll smooth ke atas tabel
        document.querySelector('.data-table')?.scrollIntoView({ behavior: 'smooth' });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    HistoryPage.init();
});