<?php require_once '../app/views/layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/asset.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/history.css">

<div class="container history-container">
    <div class="page-header">
        <h1 class="page-title">Detail Inventory & History</h1>
        <div class="page-actions">
            <a href="<?= BASE_URL ?>/consumable" class="btn-back">← Back</a>
        </div>
    </div>

    <div class="info-box">
        <h2 class="info-box-title">
            <?= htmlspecialchars($data['item']['item_name'] ?? '-') ?>
        </h2>

        <div class="info-row">
            <div class="info-label">Asset Code</div>
            <div class="info-separator">:</div>
            <div class="info-value">
                <?= htmlspecialchars($data['item']['item_code'] ?? '-') ?>
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">Category</div>
            <div class="info-separator">:</div>
            <div class="info-value"><?= htmlspecialchars($data['item']['category_name'] ?? '-') ?></div>
        </div>

        <div class="info-row">
            <div class="info-label">Location</div>
            <div class="info-separator">:</div>
            <div class="info-value"><?= htmlspecialchars($data['item']['location_name'] ?? '-') ?></div>
        </div>

        <div class="info-row">
            <div class="info-label">Current Stock</div>
            <div class="info-separator">:</div>
            <div class="info-value" style="color: #28a745; font-weight: bold; font-size: 15px;">
                <?= htmlspecialchars($data['item']['quantity'] ?? '0') ?> <span style="font-size: 12px; color: #666; font-weight: 500;"><?= htmlspecialchars($data['item']['uom'] ?? '') ?></span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">Min. Order Alert</div>
            <div class="info-separator">:</div>
            <div class="info-value" style="color: #d35400; font-weight: bold;">
                <?= htmlspecialchars($data['item']['min_order'] ?? '0') ?>
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">Condition</div>
            <div class="info-separator">:</div>
            <div class="info-value">
                <?php if (($data['item']['condition_status'] ?? '') == 'damaged'): ?>
                    <span style="color: #dc3545; font-weight: 600;">Damaged</span>
                <?php else: ?>
                    <span style="color: #28a745; font-weight: 600;">Good</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive history-table-wrapper">
            <table class="table history-table" id="detailTable">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;">No</th>
                        <th width="15%" style="text-align: center;">Transaction Date</th>
                        <th width="10%" style="text-align: center;">Status</th>
                        <th width="20%" style="text-align: center;">Qty Change</th>
                        <th width="20%" style="text-align: center;">Current Stock</th>
                        <th width="30%" style="text-align: center;">Remark</th>
                    </tr>
                </thead>
                <tbody id="detailHistoryBody">

                </tbody>
            </table>
        </div>

        <div id="detailPagination" class="pagination" style="display: flex; justify-content: center; padding: 15px;"></div>
    </div>

</div>

<script>
    const DETAIL_LOGS_DATA = <?= json_encode($data['logs'] ?? []) ?>;
    const APP_BASE_URL = '<?= BASE_URL ?>';
</script>

<script src="<?= BASE_URL ?>/public/js/consumable_detail.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        DetailManager.init(DETAIL_LOGS_DATA, APP_BASE_URL);
    });
</script>

<?php require_once '../app/views/layouts/footer.php'; ?>