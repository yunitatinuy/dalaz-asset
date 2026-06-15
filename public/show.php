<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'dalaz-asset');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_PATH', '/dalaz-asset');

$assetCode = isset($_GET['code']) ? trim($_GET['code']) : null;

if (!$assetCode) {
    http_response_code(404);
    die('Asset code not provided');
}

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        SELECT 
            a.asset_name,
            a.asset_code,
            a.quantity,
            a.description,
            a.pictures,
            a.qr_code,
            l.location_name,
            c.category_name
        FROM assets a
        LEFT JOIN location l ON a.location_id = l.id
        LEFT JOIN category c ON a.category_id = c.id
        WHERE a.asset_code = :code
        LIMIT 1
    ");

    $stmt->bindParam(':code', $assetCode);
    $stmt->execute();
    $asset = $stmt->fetch();

    if (!$asset) {
        http_response_code(404);
        die('Asset not found');
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error: ' . $e->getMessage());
}

$statusBadge = ($asset['description'] == 'good')
    ? '<span class="status-badge good">Good Condition</span>'
    : '<span class="status-badge damaged">Damaged</span>';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($asset['asset_name']) ?> - DALAZ Asset</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            overflow-y: scroll;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        html::-webkit-scrollbar {
            display: none;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            padding: 20px;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: linear-gradient(135deg, #BB1B1B 0%, #8B1515 100%);
            color: white;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
            background: white;
            padding: 6px;
        }

        .header-text h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .header-text p {
            font-size: 12px;
            opacity: 0.9;
        }

        .code-banner {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 16px 24px;
        }
        
        .code-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .code-col .label {
            font-size: 11px;
            color: #78350F;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .code-col .code-value {
            font-size: 20px;
            font-weight: 800;
            color: #BB1B1B;
            letter-spacing: 0.5px;
        }

        .code-banner .label {
            font-size: 11px;
            color: #78350F;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .code-banner .code {
            font-size: 22px;
            font-weight: 800;
            color: #BB1B1B;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .content {
            padding: 24px;
        }

        .asset-info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .asset-info-box {
            display: flex;
            flex-direction: column;
        }

        .asset-info-box .field-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .asset-info-box .field-value {
            font-size: 18px;
            color: #212529;
            font-weight: 700;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 24px;
            margin-bottom: 24px;
        }

        .field {
            display: flex;
            flex-direction: column;
        }

        .field-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .field-value {
            font-size: 15px;
            color: #212529;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.good {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.damaged {
            background: #f8d7da;
            color: #721c24;
        }

        .media-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 2px solid #f0f0f0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .media-box {
            text-align: center;
        }

        .media-box .field-label {
            text-align: center;
            margin-bottom: 12px;
        }

        .media-box img {
            max-width: 100%;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            background: #f9fafb;
            padding: 8px;
            object-fit: contain;
        }

        .qr-image {
            width: 160px;
            height: 160px;
        }

        .asset-image {
            max-height: 300px;
            width: auto;
        }

        .footer {
            background: #f9fafb;
            padding: 16px 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer p {
            font-size: 11px;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                border-radius: 8px;
            }

            .header {
                padding: 20px;
            }

            .header img {
                width: 40px;
                height: 40px;
            }

            .header-text h1 {
                font-size: 18px;
            }

            .asset-info-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .media-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .code-banner .code {
                font-size: 18px;
            }

            .code-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="<?= BASE_PATH ?>/public/images/logo.png" alt="DALAZ Logo">
            <div class="header-text">
                <h1>DALAZ Asset Management</h1>
                <p>Integrated Asset Information System</p>
            </div>
        </div>

        <div class="code-banner">
            <div class="code-row">
                <div class="code-col">
                    <div class="label">Asset Code</div>
                    <div class="code-value"><?= htmlspecialchars($asset['asset_code']) ?></div>
                </div>
                <div class="code-col">
                    <div class="label">Asset Name</div>
                    <div class="code-value"><?= htmlspecialchars($asset['asset_name']) ?></div>
                </div>
            </div>
        </div>


        <div class="content">
            <div class="grid">
                <div class="field">
                    <div class="field-label">Total Quantity</div>
                    <div class="field-value"><?= $asset['quantity'] ?> Units</div>
                </div>

                <div class="field">
                    <div class="field-label">Category</div>
                    <div class="field-value"><?= htmlspecialchars($asset['category_name'] ?? 'Uncategorized') ?></div>
                </div>

                <div class="field">
                    <div class="field-label">Location</div>
                    <div class="field-value"><?= htmlspecialchars($asset['location_name'] ?? 'Not Specified') ?></div>
                </div>

                <div class="field">
                    <div class="field-label">Condition Status</div>
                    <div class="field-value"><?= $statusBadge ?></div>
                </div>
            </div>

            <div class="media-section">
                <!-- QR Code -->
                <div class="media-box">
                    <div class="field-label">QR Code</div>
                    <?php if (!empty($asset['qr_code'])): ?>
                        <?php
                        if (strpos($asset['qr_code'], 'iVBOR') === 0 || strpos($asset['qr_code'], '/9j/') === 0) {
                            $qrSrc = 'data:image/png;base64,' . $asset['qr_code'];
                        } else {
                            $qrSrc = BASE_PATH . htmlspecialchars($asset['qr_code']);
                        }
                        ?>
                        <img src="<?= $qrSrc ?>" alt="QR Code" class="qr-image">
                    <?php else: ?>
                        <div style="color: #999; font-size: 13px; padding: 60px 0;">No QR Code</div>
                    <?php endif; ?>
                </div>

                <!-- Asset Picture -->
                <div class="media-box">
                    <div class="field-label">Asset Picture</div>
                    <?php if (!empty($asset['pictures'])): ?>
                        <?php
                        // Cek format pictures
                        if (strpos($asset['pictures'], 'iVBOR') === 0 || strpos($asset['pictures'], '/9j/') === 0) {
                            $picSrc = 'data:image/jpeg;base64,' . $asset['pictures'];
                        } else {
                            $picSrc = BASE_PATH . '/public/uploads/assets/' . basename($asset['pictures']);
                        }
                        ?>
                        <img src="<?= $picSrc ?>" alt="Asset Image" class="asset-image"
                            onerror="this.src='<?= BASE_PATH ?>/public/images/no-image.png'; this.alt='No Image Available';">
                    <?php else: ?>
                        <div style="color: #999; font-size: 13px; padding: 60px 0;">No Picture</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="footer">
            <p>&copy; <?= date('Y') ?> DALAZ Asset Management System - All Rights Reserved</p>
        </div>
    </div>
</body>

</html>