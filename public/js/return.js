// VARIABEL KAMERA
let html5UserScanner = null;
let html5ItemScanner = null;

// HELPER : TOAST 
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    let icon = type === 'error' ? 'times-circle' : (type === 'warning' ? 'exclamation-circle' : 'check-circle');
    toast.innerHTML = `<i class="fas fa-${icon}"></i> <span>${message}</span>`;
    
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';
    toast.style.transition = 'all 0.3s ease';
    
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)'; 
        setTimeout(() => { if (toast.parentNode) toast.remove(); }, 300);
    }, 3000);
}

// HELPER: CLEAN URL
function extractCodeFromUrl(qrCode) {
    if (qrCode.includes('code=')) {
        let parts = qrCode.split('code=');
        let code = parts[parts.length - 1];
        if (code.includes('&')) code = code.split('&')[0];
        return decodeURIComponent(code);
    }
    return qrCode;
}

// NAVIGASI & AUTOFOCUS SCANNER
function goToStep(step) {
    document.querySelectorAll('.step-content').forEach(el => {
        el.classList.remove('active');
        el.classList.add('hidden');
    });
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
    
    if(html5UserScanner) stopUserCamera();
    if(html5ItemScanner) stopItemCamera();
    
    document.getElementById(`step-content-${step}`).classList.remove('hidden');
    document.getElementById(`step-content-${step}`).classList.add('active');
    
    for(let i=1; i<=step; i++) {
        document.getElementById(`step-ind-${i}`).classList.add('active');
    }

    setTimeout(() => {
        if (step === 1) {
            const userInput = document.getElementById('user_qr_return');
            if(userInput) userInput.focus();
        } else if (step === 2) {
            const itemInput = document.getElementById('item_qr_return');
            if(itemInput) itemInput.focus();
        }
    }, 300);
}

// STEP 1
const userQrInput = document.getElementById('user_qr_return');
if(userQrInput) {
    userQrInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') { handleUserReturnScan(this.value); this.value = ''; }
    });
}

function startUserCamera() {
    document.getElementById('user-placeholder').style.display = 'none';
    document.getElementById('reader-user').style.display = 'block';
    document.getElementById('btn-start-user').style.display = 'none';
    document.getElementById('btn-stop-user').style.display = 'inline-block';

    html5UserScanner = new Html5QrcodeScanner("reader-user", { fps: 10, qrbox: 250 });
    html5UserScanner.render((decodedText) => {
        handleUserReturnScan(decodedText);
        stopUserCamera();
    });
}

function stopUserCamera() {
    if(html5UserScanner) {
        html5UserScanner.clear().then(() => {
            document.getElementById('reader-user').style.display = 'none';
            document.getElementById('user-placeholder').style.display = 'flex';
            document.getElementById('btn-start-user').style.display = 'inline-block';
            document.getElementById('btn-stop-user').style.display = 'none';
            html5UserScanner = null;
        });
    }
}

function handleUserReturnScan(code) {
    code = extractCodeFromUrl(code);
    const msg = document.getElementById('return_msg');
    msg.textContent = "Searching...";
    
    const fd = new FormData();
    fd.append('qr_code', code); 

    fetch(`${BASE_URL}/return/scanUser`, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById('user_name_disp').textContent = data.user.full_name;
            document.getElementById('user_emp_disp').textContent = data.user.employee_no;
            document.getElementById('hidden_user_id_return').value = data.user.id;
            
            showToast(`User found: ${data.user.full_name}`, 'success');
            renderReturnTable(data.borrowed_items);
            goToStep(2);
        } else {
            msg.textContent = data.message;
            msg.style.color = 'red';
            showToast(data.message, 'error');
            setTimeout(() => { document.getElementById('user_qr_return').focus(); }, 100);
        }
    })
    .catch(err => { 
        console.error(err); 
        msg.textContent = "Error."; 
        setTimeout(() => { document.getElementById('user_qr_return').focus(); }, 100);
    });
}


// STEP 2
let borrowedList = []; 
let itemsToReturn = [];

function renderReturnTable(items) {
    borrowedList = items; 
    itemsToReturn = []; 
    const tbody = document.getElementById('return-table-body');
    tbody.innerHTML = '';

    if(items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px;">No borrowed items found.</td></tr>';
        return;
    }

    items.forEach(item => {
        tbody.innerHTML += `
            <tr id="row-${item.borrowed_id}">
                <td>${item.equipment_name}</td>
                <td>${item.asset_number}</td>
                <td>
                    <select id="status-${item.borrowed_id}" class="status-select" disabled>
                        <option value="good">Good</option>
                        <option value="damaged">Damaged</option>
                        <option value="lost">Lost</option>
                    </select>
                </td>
                <td id="status-text-${item.borrowed_id}" style="color:#999;">Waiting...</td>
            </tr>
        `;
    });
}

const itemQrInput = document.getElementById('item_qr_return');
if(itemQrInput) {
    itemQrInput.addEventListener('keypress', function(e) {
        if(e.key === 'Enter') { 
            markForReturn(this.value); 
            this.value = ''; 
            setTimeout(() => { this.focus(); }, 10);
        }
    });
}

function startItemCamera() {
    document.getElementById('item-placeholder').style.display = 'none';
    document.getElementById('reader-item').style.display = 'block';
    document.getElementById('btn-start-item').style.display = 'none';
    document.getElementById('btn-stop-item').style.display = 'inline-block';

    html5ItemScanner = new Html5QrcodeScanner("reader-item", { fps: 10, qrbox: 250 });
    html5ItemScanner.render((decodedText) => {
        markForReturn(extractCodeFromUrl(decodedText));
    });
}

function stopItemCamera() {
    if(html5ItemScanner) {
        html5ItemScanner.clear().then(() => {
            document.getElementById('reader-item').style.display = 'none';
            document.getElementById('item-placeholder').style.display = 'flex';
            document.getElementById('btn-start-item').style.display = 'inline-block';
            document.getElementById('btn-stop-item').style.display = 'none';
            html5ItemScanner = null;
        });
    }
}

function markForReturn(assetCode) {
    assetCode = extractCodeFromUrl(assetCode);
    const item = borrowedList.find(i => i.asset_number === assetCode || i.equipment_id.toString() === assetCode); 
    
    if(!item) { showToast("Item not found!", 'warning'); return; }
    if(itemsToReturn.includes(item.borrowed_id)) { showToast("Already scanned.", 'warning'); return; }

    itemsToReturn.push(item.borrowed_id);
    const row = document.getElementById(`row-${item.borrowed_id}`);
    if(row) {
        row.classList.add('active-row');
        row.querySelector('select').disabled = false;
        const statusText = document.getElementById(`status-text-${item.borrowed_id}`);
        statusText.textContent = "Ready";
        statusText.style.color = "green";
        statusText.style.fontWeight = "bold";
        showToast("Item Marked!", 'success');
    }
}

let defectQueue = []; 
let currentDefectItem = null;
let currentDefectPhotos = []; 

async function compressImage(file, maxWidth = 800, maxHeight = 800, quality = 0.7) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height *= maxWidth / width));
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width *= maxHeight / height));
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    resolve(new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg' }));
                }, 'image/jpeg', quality);
            };
        };
    });
}

async function handleDefectPhotos(input) {
    const previewContainer = document.getElementById('defectPhotosPreview');
    const btnSave = document.querySelector('#defectModal .btn-primary');
    btnSave.disabled = true;
    btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Compressing...';

    for (let i = 0; i < input.files.length; i++) {
        const originalFile = input.files[i];
        if (!originalFile.type.startsWith('image/')) continue;

        try {
            const compressedFile = await compressImage(originalFile);
            currentDefectPhotos.push(compressedFile);

            const reader = new FileReader();
            reader.onload = (e) => {
                const imgWrap = document.createElement('div');
                imgWrap.style.cssText = "position:relative; width:60px; height:60px; border-radius:6px; overflow:hidden; border:1px solid #ccc;";
                imgWrap.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                previewContainer.appendChild(imgWrap);
            };
            reader.readAsDataURL(compressedFile);
        } catch (error) {
            console.error("Error compressing image:", error);
        }
    }
    
    btnSave.disabled = false;
    btnSave.innerHTML = 'Save Report';
}

function initiateReturnProcess() {
    if(itemsToReturn.length === 0) { showToast("Scan items first.", 'warning'); return; }

    defectQueue = [];
    itemsToReturn.forEach(borrowId => {
        const status = document.getElementById(`status-${borrowId}`).value;
        if(status !== 'good') defectQueue.push({ borrowId: borrowId, status: status, cause: '', photos: [] });
    });

    if (defectQueue.length > 0) processDefectQueue();
    else finalizeSubmission();
}

function processDefectQueue() {
    const pendingItem = defectQueue.find(item => item.cause === '');
    if (pendingItem) {
        currentDefectItem = pendingItem;
        currentDefectPhotos = []; // Reset foto
        
        const itemInfo = borrowedList.find(i => i.borrowed_id === pendingItem.borrowId);
        document.getElementById('defectAssetName').textContent = itemInfo.equipment_name;
        document.getElementById('defectStatus').textContent = pendingItem.status.toUpperCase();
        
        document.getElementById('defectCauseInput').value = ''; 
        const photoInput = document.getElementById('defectPhotosInput');
        if(photoInput) photoInput.value = '';
        const preview = document.getElementById('defectPhotosPreview');
        if(preview) preview.innerHTML = '';

        // Sembunyikan form upload foto jika barang LOST
        const photoGroup = document.getElementById('photoUploadGroup');
        if (photoGroup) {
            if (pendingItem.status === 'lost') {
                photoGroup.style.display = 'none';
            } else {
                photoGroup.style.display = 'block';
            }
        }

        document.getElementById('defectModal').classList.add('active');
        setTimeout(() => { document.getElementById('defectCauseInput').focus(); }, 300);
    } else {
        finalizeSubmission();
    }
}

function saveDefectInfo() {
    const cause = document.getElementById('defectCauseInput').value.trim();
    
    if(!cause) { 
        showToast("Please describe the cause / chronology.", "warning"); 
        return; 
    }
    
    // Wajib foto jika status Damaged (Jika Lost, lewati validasi foto)
    if (currentDefectItem && currentDefectItem.status === 'damaged') {
        if(currentDefectPhotos.length === 0) {
            showToast("Please upload at least 1 photo of the defect/evidence.", "warning");
            return;
        }
    }

    if(currentDefectItem) {
        currentDefectItem.cause = cause;
        currentDefectItem.photos = [...currentDefectPhotos];
        
        document.getElementById('defectModal').classList.remove('active');
        
        // Kosongkan current item lalu lanjut proses antrean berikutnya
        currentDefectItem = null;
        processDefectQueue(); 
    }
}

function closeDefectModal() { document.getElementById('defectModal').classList.remove('active'); }

function finalizeSubmission() {
    document.getElementById('confirmItemCount').textContent = itemsToReturn.length;
    document.getElementById('confirmModal').classList.add('active');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

function executeSubmission() {
    closeConfirmModal();

    const userId = document.getElementById('hidden_user_id_return').value;
    const btn = document.getElementById('btnSubmitReturn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = 'Processing...';

    const promises = itemsToReturn.map(borrowId => {
        const item = borrowedList.find(i => i.borrowed_id === borrowId);
        const status = document.getElementById(`status-${borrowId}`).value;
        
        const fd = new FormData();
        fd.append('user_id', userId);
        fd.append('borrowed_id', borrowId);
        fd.append('equipment_id', item.equipment_id);
        fd.append('asset_number', item.asset_number);
        fd.append('quantity', item.quantity);
        fd.append('description', status);

        const defectInfo = defectQueue.find(d => d.borrowId === borrowId);
        if (defectInfo) {
            fd.append('defect_cause', defectInfo.cause);
            
            if(defectInfo.photos && defectInfo.photos.length > 0) {
                defectInfo.photos.forEach((file) => {
                    fd.append('defect_photos[]', file);
                });
            }
        }

        return fetch(`${BASE_URL}/return/process`, { method: 'POST', body: fd })
        .then(res => res.text())
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("FATAL PHP ERROR:", text); 
                throw new Error("Server Error (Check Console)");
            }
        });
    });

    Promise.all(promises).then(results => {
        const failed = results.filter(r => !r.success);
        
        if(failed.length === 0) {
            showToast("Return successfully saved!", "success");
            setTimeout(() => { window.location.reload(); }, 1500); 
        } else {
            const errMsg = failed[0].message || "Unknown error";
            showToast(`Failed: ${errMsg}`, 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }).catch(err => {
        console.error(err);
        showToast("System Error. See Console.", 'error');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const initialInput = document.getElementById('user_qr_return');
        if(initialInput) initialInput.focus();
    }, 500);
});