<?php require_once '../app/views/layouts/user_header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/history.css">

<div class="main-content">
    <div class="history-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; padding-bottom:15px; border-bottom:1px solid #eee;">
            <h1 class="page-title" style="margin:0; border:none;">History of All Transactions</h1>
            <div style="color:#666; font-size:14px; font-weight:500;">
                Total Records: <strong><?= $data['pagination']['total_records'] ?? 0 ?></strong>
            </div>
        </div>

        <div class="history-table-wrapper">
            <table class="history-table">
                <thead>
                    <tr>
                        <th width="60" style="text-align:center;">No</th>
                        <th>Borrower</th>
                        <th>Asset Name</th>
                        <th>Asset Code</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['history'])): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding: 50px; color: #999;">
                                <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 15px; color:#ddd;"></i><br>
                                No transaction found in the database.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $no = ($data['pagination']['current_page'] - 1) * 10 + 1;
                        foreach ($data['history'] as $row):
                        ?>
                            <tr>
                                <td style="text-align:center; font-weight:600; color:#888;"><?= $no++ ?></td>

                                <td data-label="Borrower">
                                    <div style="font-weight:700; color:#333; font-size:15px;">
                                        <?= htmlspecialchars($row['user_name'] ?? 'Unknown User') ?>
                                    </div>
                                    <div style="font-size:13px; color:#888; margin-top:4px;">
                                        ID: <?= htmlspecialchars($row['employee_no'] ?? '-') ?>
                                    </div>
                                </td>

                                <td data-label="Asset Name" style="font-weight:600; color:#444;">
                                    <?= htmlspecialchars($row['asset_name'] ?? 'Unknown Asset') ?>
                                </td>

                                <td data-label="Asset Code">
                                    <span style="background:#f4f4f4; padding:6px 10px; border-radius:6px; font-size:13px; font-family:monospace; color:#555; border:1px solid #eee;">
                                        <?= htmlspecialchars($row['no_jd'] ?? '-') ?>
                                    </span>
                                </td>

                                <td data-label="Borrow Date"><?= date('d M Y', strtotime($row['borrow_date'])) ?></td>

                                <td data-label="Return Date">
                                    <?php if (!empty($row['return_date'])): ?>
                                        <span style="color:#2ecc71; font-weight:600;">
                                            <?= date('d M Y', strtotime($row['return_date'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#ccc;">-</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Status" style="text-align:center;">
                                    <?php
                                    // PERBAIKAN DI SINI (Melindungi strtolower dari null)
                                    $status = strtolower($row['status'] ?? 'unknown');
                                    $badgeClass = 'badge-borrowed';

                                    if ($status == 'returned') $badgeClass = 'badge-returned';
                                    if ($status == 'overdue') $badgeClass = 'badge-overdue';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($data['history'])): ?>
            <div class="pagination">

                <?php if ($data['pagination']['has_prev']): ?>
                    <a href="<?= BASE_URL ?>/history/user?page=<?= $data['pagination']['current_page'] - 1 ?>" class="page-btn">
                        « Prev
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled">« Prev</span>
                <?php endif; ?>

                <span class="page-btn active">
                    <?= $data['pagination']['current_page'] ?>
                </span>

                <?php if ($data['pagination']['has_next']): ?>
                    <a href="<?= BASE_URL ?>/history/user?page=<?= $data['pagination']['current_page'] + 1 ?>" class="page-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled">Next »</span>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../app/views/layouts/user_footer.php'; ?>