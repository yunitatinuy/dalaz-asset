<?php require_once '../app/views/layouts/user_header.php';
$stats = $data['stats'];
?>

<div class="dashboard-container">
    <h2>Dashboard Overview</h2>

    <div class="stats-grid">
        <div class="stat-card bg-brown">
            <div class="stat-number"><?= $stats['borrowed_count'] ?></div>
            <div class="stat-label">Currently Borrowed</div>
        </div>
        <div class="stat-card bg-green">
            <div class="stat-number"><?= $stats['transactions_count'] ?></div>
            <div class="stat-label">Total Transactions</div>
        </div>
        <div class="stat-card bg-blue">
            <div class="stat-number"><?= $stats['overdue_count'] ?></div>
            <div class="stat-label">Overdue Items</div>
        </div>
        <div class="stat-card bg-pink">
            <div class="stat-number"><?= $stats['due_today_count'] ?></div>
            <div class="stat-label">Due Today</div>
        </div>
    </div>

    <div class="content-grid">

        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-bell"></i>Notifications
            </div>

            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($stats['notifications'] as $notif): ?>
                    <?php
                    // Styling berdasarkan tipe notifikasi
                    $bg = '#f8f9fa';
                    $border = '#ddd';
                    $color = '#333';
                    if ($notif['type'] == 'danger') {
                        $bg = '#fdeded';
                        $border = '#e74c3c';
                        $color = '#c0392b';
                    }
                    if ($notif['type'] == 'success') {
                        $bg = '#eafaf1';
                        $border = '#2ecc71';
                        $color = '#27ae60';
                    }
                    if ($notif['type'] == 'info') {
                        $bg = '#e3f2fd';
                        $border = '#3498db';
                        $color = '#2980b9';
                    }
                    ?>
                    <div style="padding:12px 15px; background:<?= $bg ?>; border-left:4px solid <?= $border ?>; border-radius:4px; font-size:13px; color:<?= $color ?>; display:flex; align-items:start; gap:10px;">
                        <i class="<?= $notif['icon'] ?>" style="margin-top:2px;"></i>
                        <div><?= $notif['msg'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-clipboard"></i>Recent Activities
            </div>

            <?php if (empty($stats['recent_history'])): ?>
                <div class="empty-state">
                    No transaction history found.
                </div>
            <?php else: ?>
                <ul style="list-style:none; padding:0; margin:0;">
                    <?php foreach ($stats['recent_history'] as $hist): ?>
                        <li style="padding:10px 0; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;">
                            <div style="flex:1;">
                                <div style="font-weight:600; color:#333; font-size:13px; display:flex; align-items:center; gap:5px;">
                                    <?= htmlspecialchars($hist['equipment_name'] ?? 'Unknown Item') ?>
                                    <span style="font-weight:normal; color:#888; font-size:11px;">(<?= htmlspecialchars($hist['asset_number'] ?? '-') ?>)</span>
                                </div>
                                <div style="font-size:11px; color:#666; margin-top:2px;">
                                    <i class="fas fa-user-circle" style="font-size:10px;"></i>
                                    <strong><?= htmlspecialchars($hist['borrower_name'] ?? 'Unknown') ?></strong>
                                    <span style="margin:0 5px;">•</span>
                                    <?= date('d M H:i', strtotime($hist['date'] . ' ' . $hist['time'])) ?>
                                </div>
                            </div>
                            <div style="min-width:80px; text-align:right;">
                                <?php if ($hist['status'] == 'returned' || !empty($hist['return_date'])): ?>
                                    <span style="font-size:10px; padding:3px 8px; background:#eafaf1; color:#2ecc71; border-radius:12px; font-weight:700;">RETURNED</span>
                                <?php else: ?>
                                    <span style="font-size:10px; padding:3px 8px; background:#fff8e1; color:#f39c12; border-radius:12px; font-weight:700;">BORROWED</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="content-grid">
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-exclamation-triangle"></i>Overdue Items
            </div>

            <?php
            $overdueItems = $stats['overdue_list'] ?? [];
            ?>

            <?php if (empty($overdueItems)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color:#2ecc71; font-size:24px; margin-bottom:10px;"></i>
                    <br>
                    No overdue items
                </div>
            <?php else: ?>
                <div class="asset-list" style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($overdueItems as $item): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; border-radius:8px; background:#fff5f5; border-left: 4px solid #e74c3c; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">

                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:36px; height:36px; background:#ffebee; color:#c0392b; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>

                                <div>
                                    <div style="font-weight:700; font-size:14px; color:#c0392b;">
                                        <?= htmlspecialchars($item['equipment_name'] ?? 'Unknown Item') ?>
                                    </div>
                                    <div style="font-size:11px; color:#666;">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($item['borrower_name'] ?? 'Unknown') ?>
                                        <span style="margin: 0 5px;">•</span>
                                        Code: <?= htmlspecialchars($item['asset_number'] ?? '-') ?>
                                    </div>
                                </div>
                            </div>

                            <div style="text-align:right;">
                                <span style="background: #c0392b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">
                                    <?= $item['days_overdue'] ?> days late
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-trophy"></i>Popular Assets
            </div>

            <?php if (empty($stats['popular_assets'])): ?>
                <div class="empty-state" style="padding: 40px 0;">
                    No data available.
                </div>
            <?php else: ?>
                <div class="asset-list">
                    <?php foreach ($stats['popular_assets'] as $index => $asset): ?>
                        <div class="asset-item">
                            <div class="asset-badge"><?= $index + 1 ?></div>
                            <div class="asset-count"><?= $asset['total_qty'] ?> <span style="font-size:9px;">Unit</span></div>
                            <div class="asset-name" style="margin-top:15px; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?= htmlspecialchars($asset['equipment_name'] ?? 'Unknown Item') ?>
                            </div>
                            <div class="asset-code"><?= htmlspecialchars($asset['asset_number'] ?? '-') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once '../app/views/layouts/user_footer.php'; ?>