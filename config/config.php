<?php
// Base URL Konfigurasi
define('BASE_URL', 'http://localhost/dalaz-asset');
define('BASE_PATH', dirname(__DIR__));

// Database Konfigurasi
define('DB_HOST', 'localhost');
define('DB_NAME', 'dalaz-asset');
define('DB_USER', 'root');
define('DB_PASS', '');

// App Konfigurasi
define('APP_NAME', 'Dalaz Asset Management');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Upload Konfigurasi
define('UPLOAD_PATH', BASE_PATH . '/public/uploads/');
define('QRCODE_PATH', BASE_PATH . '/public/qrcodes/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// untuk phpmailer
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', ''); // email yang digunakan untuk pengiriman email sistem
define('SMTP_PASS', ''); // 16 Digit App Password nya
