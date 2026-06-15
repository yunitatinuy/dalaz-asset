<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Equipment History</h1>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;">No</th>
                        <th width="20%" style="text-align: center;">Asset Name</th>
                        <th width="15%" style="text-align: center;">Quantity</th>
                        <th width="15%" style="text-align: center;">Available Stock</th>
                        <th width="15%" style="text-align: center;">Total Borrowed</th>
                        <th width="15%" style="text-align: center;">Status</th>
                        <th width="15%" style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['summary'])): ?>
                        <tr>
                            <td colspan="7" class="text-center">No equipment data.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['summary'] as $index => $item): ?>
                            <?php
                            $totalOwned = $item['stock_available'] + $item['total_borrowed'];

                            $statusText = 'Available';
                            $statusClass = 'status-returned';

                            if ($item['total_borrowed'] > 0) {
                                $statusText = 'In Use';
                                $statusClass = 'status-borrowed';
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?= $index + 1 ?></td>

                                <td class="text-center">
                                    <div style="font-weight: 600; color: #333; margin-bottom: 2px;">
                                        <?= htmlspecialchars($item['equipment_name']) ?>
                                    </div>
                                    <div style="font-size: 11px; color: #888; font-family: monospace; background: #f8f9fa; display: inline-block; padding: 2px 6px; border-radius: 4px; border: 1px solid #eee;">
                                        <?= htmlspecialchars($item['asset_number'] ?? '-') ?>
                                    </div>
                                </td>

                                <td class="text-center" style="font-weight: 600;"><?= $totalOwned ?></td>

                                <td class="text-center" style="font-weight: 600; color: <?= $item['stock_available'] > 0 ? '#28a745' : '#dc3545' ?>;">
                                    <?= $item['stock_available'] ?>
                                </td>

                                <td class="text-center" style="font-weight: 600; color: #d9534f;"><?= $item['total_borrowed'] ?></td>

                                <td class="text-center">
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>/history/detail/<?= $item['id'] ?>" class="btn-detail"><i class="fas fa-eye"></i> Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="paginationControls" style="display: flex; justify-content: center; padding: 15px;"></div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>