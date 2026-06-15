<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/css/search.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/history.css">

<div class="container history-container">
    <div class="page-header">
        <h1 class="page-title">Equipment Details History</h1>
        <div class="page-actions">
            <a href="<?= BASE_URL ?>/history" class="btn-back">← Back</a>
        </div>
    </div>

    <div class="info-box">
        <h2 class="info-box-title"><?= htmlspecialchars($data['equipment']['equipment_name']) ?></h2>

        <div class="info-row">
            <div class="info-label">Equipment Code</div>
            <div class="info-separator">:</div>
            <div class="info-value"><?= htmlspecialchars($data['equipment']['asset_number']) ?></div>
        </div>

        <div class="info-row">
            <div class="info-label">Category</div>
            <div class="info-separator">:</div>
            <div class="info-value"><?= htmlspecialchars($data['equipment']['category_name'] ?? 'N/A') ?></div>
        </div>

        <div class="info-row">
            <div class="info-label">Location</div>
            <div class="info-separator">:</div>
            <div class="info-value"><?= htmlspecialchars($data['equipment']['location_name'] ?? 'N/A') ?></div>
        </div>

        <div class="info-row">
            <div class="info-label">Total Quantity (Owned)</div>
            <div class="info-separator">:</div>
            <div class="info-value">
                <strong><?= (int)$data['equipment']['quantity'] + (int)$data['totalBorrowed'] ?></strong>
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">Currently Borrowed</div>
            <div class="info-separator">:</div>
            <div class="info-value" style="color: #d9534f; font-weight: bold;">
                <?= (int)$data['totalBorrowed'] ?>
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">Available in Stock</div>
            <div class="info-separator">:</div>
            <div class="info-value" style="color: #28a745; font-weight: bold;">
                <?= (int)$data['equipment']['quantity'] ?>
            </div>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/history/detail/<?= $data['equipment']['id'] ?>" method="GET" class="search-form search-history-form">
        <div class="search-input-group">
            <input type="text" name="name" placeholder="Search borrower name..." class="search-input search-box" value="<?= htmlspecialchars($data['filters']['name'] ?? '') ?>">
            <input type="date" name="date" class="search-input search-date" value="<?= htmlspecialchars($data['filters']['date'] ?? '') ?>">
            <button type="submit" class="btn-search">Search</button>
        </div>
    </form>

    <div class="data-table">
        <div class="table-responsive history-table-wrapper">
            <table class="table history-table" id="detailTable">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="20%" style="text-align: center;">Borrower Name</th>
                        <th width="10%" style="text-align: center;">No JD</th>
                        <th width="15%" style="text-align: center;">Client</th>
                        <th width="15%" style="text-align: center;">Borrow Date</th>
                        <th width="15%" style="text-align: center;">Return Date</th>
                        <th width="15%" style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody id="detailTableBody">
                    <?php if (empty($data['history'])): ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 30px;">No history records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['history'] as $index => $row): ?>
                            <?php
                            $statusText = '';
                            $statusClass = '';
                            if (!empty($row['return_date'])) {
                                $condition = strtolower($row['return_status'] ?? '');
                                $statusText = ucfirst($condition);
                                if (empty($statusText)) $statusText = 'Returned';
                                if ($condition === 'good' || $condition === 'ok' || $condition === 'functioning') {
                                    $statusClass = 'status-good';
                                } elseif (in_array($condition, ['damaged', 'broken', 'lost', 'cracked'])) {
                                    $statusClass = 'status-damaged';
                                } else {
                                    $statusClass = 'status-returned';
                                }
                            } else {
                                $statusText = 'Borrowed';
                                $statusClass = 'status-borrowed';
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?= $index + 1 ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['user_name']) ?></td>
                                <td class="text-center">
                                    <span style="font-family: monospace; font-weight:600; background: #f8f9fa; padding: 2px 6px; border-radius: 4px; border: 1px solid #eee;">
                                        <?= htmlspecialchars($row['no_jd']) ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($row['client']) ?></td>
                                <td class="text-center"><?= date('d M Y', strtotime($row['borrow_date'])) ?></td>
                                <td class="text-center">
                                    <?= !empty($row['return_date']) ? date('d M Y', strtotime($row['return_date'])) : '<span style="color:#999;">-</span>' ?>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($statusText) ?>
                                    </span>
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