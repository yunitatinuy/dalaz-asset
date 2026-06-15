<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/asset.css">

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Damaged Office Equipment</h1>
        <div class="page-actions">
            <a href="<?= BASE_URL ?>/asset/company" class="btn-cancel">← Back</a>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Asset Name</th>
                        <th>Asset Code</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Condition</th>
                        <th style="min-width: 250px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['assets'])): ?>
                        <tr>
                            <td colspan="7" class="text-center">No damaged assets found..</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['assets'] as $index => $asset): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($asset['asset_name']) ?></td>
                                <td><?= htmlspecialchars($asset['asset_code']) ?></td>
                                <td><?= htmlspecialchars($asset['location_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($asset['category_name'] ?? '-') ?></td>
                                <td>
                                    <span class="badge-out">DAMAGED</span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; justify-content: center;">

                                        <button class="btn-save"
                                            style="padding: 8px 12px; font-size: 12px; min-width: auto;"
                                            onclick="markAsRepaired(<?= $asset['id'] ?>)"
                                            title="Kembalikan ke stok (Kondisi Baik)">
                                            <i class="fas fa-tools"></i> Repaired
                                        </button>

                                        <button class="btn-confirm-delete"
                                            style="padding: 8px 12px; font-size: 12px; min-width: auto;"
                                            onclick="disposeAsset(<?= $asset['id'] ?>)"
                                            title="Hapus permanen dari database">
                                            <i class="fas fa-trash"></i> Dispose
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
    const BASE_URL = '<?= BASE_URL ?>';

    function markAsRepaired(id) {
        if (confirm('Are you sure you want to mark this office equipment as REPAIRED? Status will change to "Good".')) {
            fetch(BASE_URL + '/asset/markRepaired', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        id: id
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => showToast('Network error', 'error'));
        }
    }

    function disposeAsset(id) {
        if (confirm('WARNING: This data will be PERMANENTLY DELETED from the database. Are you sure this asset has been discarded/disposed?')) {
            fetch(BASE_URL + '/asset/dispose', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        id: id
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => showToast('Network error', 'error'));
        }
    }

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.getElementById('toastContainer').appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>