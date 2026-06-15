let html5UserScanner = null;
let html5EquipScanner = null;
let equipList = [];

function extractCodeFromQR(text) {
    if (!text) return '';
    if (text.includes('?code=')) {
        let codePart = text.split('?code=')[1].split('&')[0];
        return decodeURIComponent(codePart); 
    }
    return text.trim();
}

// TOAST NOTIFIKASI
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    let icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'times-circle' : 'exclamation-circle');
    toast.innerHTML = `<i class="fas fa-${icon}"></i> <span>${message}</span>`;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// NAVIGASI
function goToStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step-line').forEach(el => el.classList.remove('active'));

    if (html5UserScanner) stopUserCamera();
    if (html5EquipScanner) stopEquipCamera();

    document.getElementById(`step-${step}`).classList.add('active');
    
    for(let i=1; i<=step; i++) {
        document.getElementById(`ind-${i}`).classList.add('active');
        if(i < step) {
            const line = document.getElementById(`line-${i}`);
            if(line) line.classList.add('active');
        }
    }

    setTimeout(() => {
        if (step === 1) {
            document.getElementById('user_qr_input').focus();
        } else if (step === 2) {
            document.getElementById('no_jd').focus();
        } else if (step === 3) {
            document.getElementById('equip_qr_input').focus();
        }
    }, 300); 
}

// step 1: scan user ID
const userQrInput = document.getElementById('user_qr_input');
if(userQrInput) {
    userQrInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') { handleUserScan(this.value); this.value = ''; }
    });
}

function startUserCamera() {
    document.getElementById('user-placeholder').style.display = 'none';
    document.getElementById('reader-user').style.display = 'block';
    document.getElementById('btn-start-user').style.display = 'none';
    document.getElementById('btn-stop-user').style.display = 'inline-flex';

    html5UserScanner = new Html5QrcodeScanner("reader-user", { fps: 10, qrbox: 250 });
    html5UserScanner.render((decodedText) => {
        handleUserScan(decodedText);
        stopUserCamera(); 
    });
}

function stopUserCamera() {
    if(html5UserScanner) {
        html5UserScanner.clear().then(() => {
            document.getElementById('reader-user').style.display = 'none';
            document.getElementById('user-placeholder').style.display = 'block'; 
            document.getElementById('btn-start-user').style.display = 'inline-flex';
            document.getElementById('btn-stop-user').style.display = 'none';
            html5UserScanner = null;
        });
    }
}

function handleUserScan(rawCode) {
    const code = extractCodeFromQR(rawCode);

    const msg = document.getElementById('user_scan_msg');
    msg.textContent = "Verifying ID...";
    msg.style.color = "#B07A4B";
    
    const formData = new FormData();
    formData.append('qr_code', code);

    fetch(`${BASE_URL}/borrowed/scanUser`, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('hidden_user_id').value = data.user.id;
            const userNameDisplay = document.getElementById('user-name-display');
            if(userNameDisplay) userNameDisplay.textContent = `Welcome, ${data.user.full_name}`;
            showToast(`ID Verified: ${data.user.full_name}`, 'success');
            goToStep(2);
        } else {
            msg.textContent = data.message;
            msg.style.color = "#c0392b";
            showToast(data.message, 'error');
        }
    })
    .catch(err => console.error(err));
}

// step 3: scan equipment
const equipQrInput = document.getElementById('equip_qr_input');

if(equipQrInput) {
    equipQrInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') { handleEquipScan(this.value); this.value = ''; }
    });
}

function startEquipCamera() {
    document.getElementById('equip-placeholder').style.display = 'none';
    document.getElementById('reader-equip').style.display = 'block';
    document.getElementById('btn-start-equip').style.display = 'none';
    document.getElementById('btn-stop-equip').style.display = 'inline-flex';

    html5EquipScanner = new Html5QrcodeScanner("reader-equip", { fps: 10, qrbox: 250 });
    html5EquipScanner.render((decodedText) => {
        handleEquipScan(decodedText);
    });
}

function stopEquipCamera() {
    if(html5EquipScanner) {
        html5EquipScanner.clear().then(() => {
            document.getElementById('reader-equip').style.display = 'none';
            document.getElementById('equip-placeholder').style.display = 'flex';
            document.getElementById('btn-start-equip').style.display = 'inline-flex';
            document.getElementById('btn-stop-equip').style.display = 'none';
            html5EquipScanner = null;
        });
    }
}

function handleEquipScan(rawCode) {
    const code = extractCodeFromQR(rawCode);

    const msg = document.getElementById('equip_scan_msg');
    const userId = document.getElementById('hidden_user_id').value;

    if(equipList.find(e => e.asset_number === code || e.qr_code === code)) {
        showToast("Item already in list!", 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('qr_code', code);
    formData.append('user_id', userId);

    fetch(`${BASE_URL}/borrowed/scanEquipment`, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            addEquipmentToList(data.equipment);
            msg.textContent = "Added: " + data.equipment.equipment_name;
            msg.style.color = "green";
            showToast("Item Added!", 'success');
            setTimeout(() => { msg.textContent = ""; }, 2000);
        } else {
            msg.textContent = data.message;
            msg.style.color = "#c0392b";
            showToast(data.message, 'error');
        }
    });
}

function addEquipmentToList(equip) {
    if(equipList.find(e => e.id === equip.id)) return;
    equipList.push(equip);
    renderEquipTable();
}

function renderEquipTable() {
    const tbody = document.getElementById('equip-list-body');
    const emptyMsg = document.getElementById('empty-list-msg');
    const countBadge = document.getElementById('item-count');
    
    tbody.innerHTML = '';
    if(countBadge) countBadge.textContent = equipList.length + " Items";

    if(equipList.length === 0) {
        emptyMsg.style.display = 'block';
    } else {
        emptyMsg.style.display = 'none';
        equipList.forEach((item, index) => {
            tbody.innerHTML += `
                <tr>
                    <td style="font-weight:600; color:#333;">${item.equipment_name}</td>
                    <td><span style="background:#eee; padding:4px 8px; border-radius:4px; font-size:12px; color:#555;">${item.asset_number}</span></td>
                    <td style="text-align:right;">
                        <button onclick="removeEquip(${index})" style="color:#c0392b; background:none; border:none; cursor:pointer; font-size:16px;" title="Remove Item">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }
}

function removeEquip(index) {
    equipList.splice(index, 1);
    renderEquipTable();
}

function submitBorrowing() {
    if(equipList.length === 0) {
        showToast("Please scan at least one item.", 'warning');
        return;
    }

    const noJd = document.getElementById('no_jd').value;
    const client = document.getElementById('client').value;
    const location = document.getElementById('location').value;
    const workingDays = document.getElementById('working_days').value;

    if(!noJd || !client || !location || !workingDays || workingDays < 1) {
        showToast("Please complete all Job Details (including Working Days)!", 'warning');
        goToStep(2);
        return;
    }

    document.getElementById('confirmItemCount').textContent = equipList.length;
    document.getElementById('confirmModal').classList.add('active');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

function executeBorrowing() {
    closeConfirmModal();

    const userId = document.getElementById('hidden_user_id').value;
    const noJd = document.getElementById('no_jd').value;
    const client = document.getElementById('client').value;
    const location = document.getElementById('location').value;
    const workingDays = document.getElementById('working_days').value;

    const btn = document.querySelector('.btn-success');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    const promises = equipList.map(item => {
        const fd = new FormData();
        fd.append('user_id', userId);
        fd.append('equipment_id', item.id);
        fd.append('asset_number', item.asset_number);
        fd.append('no_jd', noJd);
        fd.append('client', client);
        fd.append('location', location);
        fd.append('working_days', workingDays);
        fd.append('quantity', 1);

        return fetch(`${BASE_URL}/borrowed/process`, {
            method: 'POST',
            body: fd
        }).then(res => res.json());
    });

    Promise.all(promises)
        .then(results => {
            const failed = results.filter(r => !r.success);
            
            if(failed.length === 0) {
                showToast("Borrowing successfully saved!", "success");
                setTimeout(() => {
                    window.location.reload(); 
                }, 2000);
            } else {
                showToast(`Failed: ${failed.length} items. Please try again.`, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            console.error(err);
            showToast("Network Error occurred", 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}