<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container">
    <h1 class="dashboard-title">WELCOME TO DALAZ ASSET MANAGEMENT SYSTEM</h1>

    <div class="stat-cards">
        <div class="stat-card stat-card-1">
            <img src="https://img.icons8.com/ios/50/open-box.png" alt="Total Assets">
            <div class="stat-number"><?= $data['stats']['total_assets'] ?></div>
            <div class="stat-label">Total Assets</div>
        </div>

        <div class="stat-card stat-card-2">
            <img src="https://img.icons8.com/ios/50/supply-chain.png" alt="Borrowed Assets">
            <div class="stat-number"><?= $data['stats']['borrowed_assets'] ?></div>
            <div class="stat-label">Equipment Currently Borrowed</div>
        </div>

        <div class="stat-card stat-card-3">
            <img src="https://img.icons8.com/ios/50/packing.png" alt="Returned Today">
            <div class="stat-number"><?= $data['stats']['returned_today'] ?></div>
            <div class="stat-label">Equipment Returned Today</div>
        </div>

        <div class="stat-card stat-card-4">
            <img src="https://img.icons8.com/ios/50/holding-box.png" alt="Assets Running Low">
            <div class="stat-number"><?= $data['stats']['low_stock_count'] ?></div>
            <div class="stat-label">Inventory Minimum Order</div>
        </div>

        <div class="stat-card stat-card-5">
            <img src="https://img.icons8.com/ios/50/demand.png" alt="Damaged Assets">
            <div class="stat-number"><?= $data['stats']['damaged_count'] ?></div>
            <div class="stat-label">Damaged Equipment</div>
        </div>

        <div class="stat-card stat-card-6">
            <img src="https://img.icons8.com/ios/50/create-order--v1.png" alt="Borrowing Transaction">
            <div class="stat-number"><?= $data['stats']['transactions'] ?></div>
            <div class="stat-label">Total Transactions</div>
        </div>
    </div>

    <div class="dashboard-panels">

        <div class="panel">
            <div class="panel-header" style="background: linear-gradient(90deg, #d35400 0%, #e67e22 100%); border-bottom-color: #d35400;">
                <h3 class="panel-title"><i class="fas fa-exclamation-triangle"></i> Calibration Due Date Reminder</h3>
            </div>
            <div class="panel-body">
                <?php if (!empty($data['stats']['calibration_alerts'])): ?>
                    <table class="panel-table">
                        <thead>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Next Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['stats']['calibration_alerts'] as $item): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item['name'] ?? '-') ?>
                                        <div style="font-size: 11px; color: #888;"><?= htmlspecialchars($item['asset_number'] ?? '-') ?></div>
                                    </td>
                                    <td>
                                        <?= date('d M Y', strtotime($item['due_date'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($item['status'] == 'expired'): ?>
                                            <span class="badge-sm badge-red" style="background:#ffebee;">Overdue</span>
                                        <?php elseif ($item['status'] == 'danger'): ?>
                                            <span class="badge-sm badge-red"><?= $item['message'] ?></span>
                                        <?php else: ?>
                                            <span class="badge-sm badge-orange"><?= $item['message'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No calibration alerts (6 months ahead).</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">Equipment Currently Borrowed</h3>
            </div>
            <div class="panel-body">
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Equipment Name</th>
                            <th>Borrower</th>
                            <th>Borrow Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data['stats']['currently_borrowed'])): ?>
                            <?php foreach ($data['stats']['currently_borrowed'] as $item): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item['item_name'] ?? '-') ?>
                                        <div style="font-size: 11px; color: #888;"><?= htmlspecialchars($item['asset_code'] ?? '-') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($item['borrower_name'] ?? 'Unknown') ?></td>
                                    <td><?= date('d M Y', strtotime($item['borrow_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="empty-state">No active borrowing.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">Inventory Minimum Order (≤ 5)</h3>
            </div>
            <div class="panel-body">
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Inventory Name</th>
                            <th>Code</th>
                            <th>Remaining Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data['stats']['low_stock_items'])): ?>
                            <?php foreach ($data['stats']['low_stock_items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($item['code'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge-sm <?= ($item['quantity'] ?? 0) <= 2 ? 'badge-red' : 'badge-orange' ?>">
                                            <?= $item['quantity'] ?? 0 ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="empty-state">Stock is safe.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header" style="background: linear-gradient(90deg, #c0392b 0%, #e74c3c 100%); border-bottom-color: #c0392b;">
                <h3 class="panel-title">Equipment Damage Report</h3>
            </div>
            <div class="panel-body">
                <?php if (!empty($data['stats']['damaged_items'])): ?>
                    <table class="panel-table">
                        <thead>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Report By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['stats']['damaged_items'] as $item): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item['equipment_name'] ?? '-') ?>
                                        <div style="font-size: 11px; color: #888;"><?= htmlspecialchars($item['asset_number'] ?? '-') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($item['reported_by'] ?? 'Sistem') ?></td>
                                    <td><span class="badge-sm badge-red">Damaged</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No damage reports.</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($data['stats']['overdue_items'])): ?>
            <div class="panel panel-wide">
                <div class="panel-header" style="background: linear-gradient(90deg, #8e44ad 0%, #9b59b6 100%); border-bottom-color: #8e44ad;">
                    <h3 class="panel-title"><i class="fas fa-exclamation-triangle"></i> Late Returned Borrowing</h3>
                </div>
                <div class="panel-body">
                    <table class="panel-table">
                        <thead>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Borrower</th>
                                <th>Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['stats']['overdue_items'] as $item): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item['equipment_name'] ?? 'Unknown Item') ?>
                                        <div style="font-size: 11px; color: #888;">
                                            <?= htmlspecialchars($item['asset_number'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($item['borrower_name'] ?? 'Unknown') ?></td>
                                    <td><span class="badge-sm badge-red"><?= $item['days_overdue'] ?> days</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>