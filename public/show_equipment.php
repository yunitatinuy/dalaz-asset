<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'dalaz-asset');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_PATH', '/dalaz-asset');

// Ambil parameter code
$assetNumber = $_GET['code'] ?? '';

if (empty($assetNumber)) {
    http_response_code(400);
    die('Missing asset number parameter');
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Query data equipment
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            l.location_name,
            c.category_name
        FROM equipment e
        LEFT JOIN location l ON e.location_id = l.id
        LEFT JOIN category c ON e.category_id = c.id
        WHERE e.asset_number = :code
        LIMIT 1
    ");

    $stmt->bindParam(':code', $assetNumber);
    $stmt->execute();

    $equipment = $stmt->fetch();

    if (!$equipment) {
        http_response_code(404);
        die('Equipment not found');
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($equipment['equipment_name']) ?> - Data Teknis</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            padding: 20px;
            line-height: 1.6;
            color: #333;
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
            background: linear-gradient(135deg, #bb1b1b 0%, #8b1515 100%);
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
            background: #FFE5E5;
            border-left: 4px solid #bb1b1b;
            padding: 16px 24px;
        }

        .code-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .code-col .label {
            font-size: 11px;
            color: #8b1515;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .code-col .code-value {
            font-size: 20px;
            font-weight: 800;
            color: #bb1b1b;
        }

        .content {
            padding: 24px;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-section h2 {
            font-size: 18px;
            color: #bb1b1b;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 700;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #bb1b1b;
        }

        .info-label {
            font-weight: 700;
            color: #555;
            font-size: 11px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            word-break: break-word;
        }

        .text-content {
            background: #f8f9fa;
            padding: 18px;
            border-radius: 10px;
            border-left: 4px solid #bb1b1b;
        }

        .pictures-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .pictures-gallery img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .pdf-viewer {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
        }

        .btn-download {
            display: inline-block;
            padding: 12px 24px;
            background: #17a2b8;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            margin-top: 15px;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            color: #888;
            font-style: italic;
            border-radius: 8px;
        }

        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6c757d;
        }

        @media (max-width: 768px) {

            .code-row,
            .info-grid,
            .pictures-gallery {
                grid-template-columns: 1fr;
            }

            .pdf-viewer {
                height: 400px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="<?= BASE_PATH ?>/public/images/logo.png" alt="DALAZ Logo">
            <div class="header-text">
                <h1>Data Teknis Peralatan</h1>
                <p>DALAZ Equipment Information System</p>
            </div>
        </div>

        <div class="code-banner">
            <div class="code-row">
                <div class="code-col">
                    <div class="label">Asset Number</div>
                    <div class="code-value"><?= htmlspecialchars($equipment['asset_number']) ?></div>
                </div>
                <div class="code-col">
                    <div class="label">Equipment Name</div>
                    <div class="code-value"><?= htmlspecialchars($equipment['equipment_name']) ?></div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="info-section">
                <h2>I. General Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Equipment Name</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['equipment_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Asset Number</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['asset_number']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Owner</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['owner'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Brand/Type</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['type'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Serial Number</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['serial_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Manufacturer</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['manufacturer'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Location</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['location_name'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h2>II. Technical Specifications</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Capacity</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['capacity'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Dimensions</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['dimensions'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Weight (kg)</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['weight'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Storage Temperature</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['storage_temp'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Humidity</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['humidity'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h2>III. Maintenance Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Calibration Certificate</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['calibration_cert_no'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Calibration Date</div>
                        <div class="info-value"><?= $equipment['calibration_date'] ? date('d M Y', strtotime($equipment['calibration_date'])) : 'N/A' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Maintenance Frequency</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['maintenance_frequency'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Supporting Vendor</div>
                        <div class="info-value"><?= htmlspecialchars($equipment['supporting_vendor'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($equipment['usage_steps']) && trim(strip_tags($equipment['usage_steps'])) !== ''): ?>
                <div class="info-section">
                    <h2>IV. Usage Steps</h2>
                    <div class="text-content">
                        <?= $equipment['usage_steps'] ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="info-section">
                <h2>V. Documentation</h2>
                <?php if (!empty($equipment['pictures'])): ?>
                    <?php
                    $pictures = json_decode($equipment['pictures'], true);
                    if (!is_array($pictures)) $pictures = [$equipment['pictures']];
                    ?>
                    <div class="pictures-gallery">
                        <?php foreach ($pictures as $pic): ?>
                            <?php
                            $picPath = (strpos($pic, 'public/') === 0) ? BASE_PATH . '/' . $pic : BASE_PATH . '/public/uploads/equipment/' . basename($pic);
                            ?>
                            <img src="<?= $picPath ?>" alt="Equipment Image" onerror="this.style.display='none'">
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-images">No documentation images available.</div>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <h2>VI. Supporting Document</h2>
                <?php if (!empty($equipment['doc_support'])): ?>
                    <?php
                    $docPath = $equipment['doc_support'];
                    $fullDocPath = (strpos($docPath, 'public/') === 0) ? BASE_PATH . '/' . $docPath : BASE_PATH . '/public/uploads/documents/' . basename($docPath);
                    ?>
                    <div style="margin-top: 15px;">
                        <iframe src="<?= $fullDocPath ?>" class="pdf-viewer"></iframe>

                        <div style="text-align: center;">
                            <a href="<?= $fullDocPath ?>" target="_blank" class="btn-download">
                                Download / Open PDF Fullscreen
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-images">No supporting document uploaded</div>
                <?php endif; ?>
            </div>

        </div>

        <div class="footer">
            <p>&copy; <?= date('Y') ?> DALAZ Equipment Management System</p>
            <p>Asset ID: <?= htmlspecialchars($equipment['asset_number']) ?></p>
        </div>
    </div>
</body>

</html>