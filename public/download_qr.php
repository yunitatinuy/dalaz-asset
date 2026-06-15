<?php

ini_set('display_errors', 0);
error_reporting(0);

while (ob_get_level()) {
    ob_end_clean();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/QRHelper.php';

$code = $_GET['code'] ?? '';
$name = $_GET['name'] ?? 'QR-Code';

if (empty($code)) {
    die('Code required');
}

try {
    $base64QR = QRHelper::generate($code);

    if (!$base64QR) {
        throw new Exception('Failed to generate QR Code');
    }

    $imageData = base64_decode($base64QR);

    while (ob_get_level()) {
        ob_end_clean();
    }

    $fileName = "QR {$name} - {$code}.png";

    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . strlen($imageData));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo $imageData;
    die();
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
