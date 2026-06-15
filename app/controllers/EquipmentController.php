<?php

require_once __DIR__ . '/../helpers/QRHelper.php';

class EquipmentController extends Controller
{
    private $equipmentModel;
    private $locationModel;
    private $categoryModel;
    private $borrowedModel;

    public function __construct()
    {
        $this->equipmentModel = $this->model('Equipment');
        $this->locationModel = $this->model('Location');
        $this->categoryModel = $this->model('Category');
        $this->borrowedModel = $this->model('Borrowed');
    }

    public function index()
    {
        $this->requireAdmin();

        $data = [
            'title' => 'Equipment Data',
            'pageCSS' => 'asset',
            'locations' => $this->locationModel->getAll(),
            'categories' => $this->categoryModel->getAll(),
            'pageJS' => 'equipment'
        ];

        $this->view('equipment/index', $data);
    }

    public function getAll()
    {
        header('Content-Type: application/json');
        try {
            $equipments = $this->equipmentModel->getAllWithDetails();
            echo json_encode(['success' => true, 'data' => $equipments]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getById()
    {
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID required'], 400);
        }
        try {
            $equipment = $this->equipmentModel->getById($id);
            if ($equipment) {
                $this->jsonResponse(['success' => true, 'data' => $equipment]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Equipment not found'], 404);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        exit;
    }

    // CSV
    public function downloadTemplate()
    {
        $filename = 'template_equipment.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM untuk support simbol spesial
        fputs($output, "\xEF\xBB\xBF");

        $headers = [
            'Equipment Name*',
            'Asset Code*',
            'Quantity (Min 1)',
            'Location ID (Number)',
            'Category ID (Number)',
            'Owner',
            'Person in Charge',
            'Brand / Type',
            'Serial Number',
            'Manufacturer',
            'Purchase Date (YYYY-MM-DD)',
            'Condition (good/damaged)',
            'Equipment Details',
            'Capacity',
            'Dimensions',
            'Weight',
            'Storage Temp.',
            'Humidity',
            'No Calibration Certificate',
            'Calibration Date (YYYY-MM-DD)',
            'Maintenance Frequency (Months)',
            'Vendor Support',
            'Usage Steps (HTML/Text)'
        ];

        fputcsv($output, $headers);
        fclose($output);
        exit;
    }

    private function formatDateForDB($dateStr)
    {
        if (empty($dateStr) || $dateStr == '-') return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
        $timestamp = strtotime($dateStr);
        return ($timestamp !== false) ? date('Y-m-d', $timestamp) : null;
    }

    public function import()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file_csv'])) {
            $this->jsonResponse(['success' => false, 'message' => 'File not valid']);
            exit;
        }

        $file = $_FILES['file_csv']['tmp_name'];
        $handle = fopen($file, "r");
        $bom = fread($handle, 3);
        if ($bom != "\xEF\xBB\xBF") rewind($handle);

        fgetcsv($handle); // Skip Header

        $successCount = 0;
        $failCount = 0;
        $errors = [];
        $rowNumber = 1;

        try {
            while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
                $rowNumber++;

                // MAPPING DATA
                $equipName      = trim($data[0] ?? '');
                $baseAssetNo    = trim($data[1] ?? '');
                $quantity       = intval($data[2] ?? 1);
                $locationId     = intval($data[3] ?? 0);
                $categoryId     = intval($data[4] ?? 0);
                $owner          = trim($data[5] ?? '');
                $respPerson     = trim($data[6] ?? '');
                $brandType      = trim($data[7] ?? '');
                $serialNo       = trim($data[8] ?? '');
                $manufacturer   = trim($data[9] ?? '');
                $purchaseDate   = $this->formatDateForDB($data[10] ?? '');
                $condition      = strtolower(trim($data[11] ?? 'good'));
                $details        = trim($data[12] ?? '');
                $capacity       = trim($data[13] ?? '');
                $dimensions     = trim($data[14] ?? '');
                $weight         = trim($data[15] ?? '');
                $storeTemp      = trim($data[16] ?? '');
                $humidity       = trim($data[17] ?? '');
                $calCertNo      = trim($data[18] ?? '');
                $calDate        = $this->formatDateForDB($data[19] ?? '');
                $maintFreq      = trim($data[20] ?? '');
                $vendor         = trim($data[21] ?? '');
                $usageSteps     = trim($data[22] ?? ''); // Kolom baru

                if (empty($equipName) || empty($baseAssetNo)) {
                    $failCount++;
                    $errors[] = "Row $rowNumber: Name/Number of empty assets.";
                    continue;
                }

                if ($quantity < 1) $quantity = 1;

                for ($i = 1; $i <= $quantity; $i++) {
                    $uniqueAssetNo = ($quantity > 1) ? $baseAssetNo . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) : $baseAssetNo;

                    if ($this->equipmentModel->assetNumberExists($uniqueAssetNo)) {
                        $failCount++;
                        $errors[] = "Row $rowNumber: Asset $uniqueAssetNo already exists.";
                        continue;
                    }

                    $insertData = [
                        'equipment_name'    => $equipName,
                        'quantity'          => 1,
                        'location_id'       => ($locationId > 0) ? $locationId : null,
                        'category_id'       => ($categoryId > 0) ? $categoryId : null,
                        'asset_number'      => $uniqueAssetNo,
                        'owner'             => $owner,
                        'responsible_person' => $respPerson,
                        'type'              => $brandType,
                        'serial_number'     => $serialNo,
                        'manufacturer'      => $manufacturer,
                        'purchase_date'     => $purchaseDate,
                        'condition_status'  => $condition,
                        'equipment_details' => $details,
                        'capacity'          => $capacity,
                        'dimensions'        => $dimensions,
                        'weight'            => $weight,
                        'storage_temp'      => $storeTemp,
                        'humidity'          => $humidity,
                        'calibration_cert_no' => $calCertNo,
                        'calibration_date'  => $calDate,
                        'maintenance_frequency' => $maintFreq,
                        'supporting_vendor' => $vendor,
                        'usage_steps'       => $usageSteps,
                        'pictures'          => null,
                        'doc_support'       => null,
                        'qr_code'           => QRHelper::generateEquipment($uniqueAssetNo)
                    ];

                    if ($this->equipmentModel->insert($insertData)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                }
            }
            fclose($handle);

            $msg = "Import completed. Success: $successCount.";
            if ($failCount > 0) $msg .= " Failed: $failCount.";

            $this->jsonResponse(['success' => true, 'message' => $msg, 'debug_errors' => $errors]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // CRUD

    public function add()
    {
        $this->processForm('add');
    }
    public function edit()
    {
        $this->processForm('edit');
    }

    // HELPER: Cek apakah File (Gambar atau PDF) dipakai oleh barang lain
    private function isFileShared($filePath, $excludeId, $column = 'pictures')
    {
        $db = new Database();

        // Ambil nama file-nya saja (contoh: eq_12345.jpg)
        $fileName = basename($filePath);

        if ($column === 'pictures') {
            $db->query("SELECT id FROM equipment WHERE pictures LIKE :file AND id != :id LIMIT 1");
        } else {
            $db->query("SELECT id FROM equipment WHERE doc_support LIKE :file AND id != :id LIMIT 1");
        }

        // Cari kemiripan nama file di database
        $db->bind(':file', '%' . $fileName . '%');
        $db->bind(':id', $excludeId);

        return $db->single() !== false;
    }

    private function processForm($mode)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid Request']);
        }

        try {
            $data = $this->collectPostData();

            // Validasi Dasar
            if (empty($data['equipment_name']) || empty($data['asset_number'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Name & Number of Asset Required']);
            }

            // 1. Cek Apakah Ada Upload Gambar Baru?
            $picturesJson = $this->handleUpload('pictures');

            // 2. Cek Apakah Ada Upload Dokumen Baru?
            $pdfPath = null;
            if (isset($_FILES['doc_support']) && $_FILES['doc_support']['error'] == 0) {
                $uploadPdf = $this->processPdfUpload($_FILES['doc_support']);
                if ($uploadPdf['status']) $pdfPath = $uploadPdf['path'];
                else {
                    $this->jsonResponse(['success' => false, 'message' => $uploadPdf['message']]);
                    exit;
                }
            }

            // LOGIKA TAMBAH (ADD)
            if ($mode == 'add') {
                $quantity = intval($data['quantity']);
                $baseAssetNo = $data['asset_number'];
                $successCount = 0;

                if ($quantity == 1 && $this->equipmentModel->assetNumberExists($baseAssetNo)) {
                    $this->jsonResponse(['success' => false, 'message' => "Number of Asset $baseAssetNo already used"]);
                    exit;
                }

                for ($i = 1; $i <= $quantity; $i++) {
                    $uniqueNo = ($quantity > 1) ? $baseAssetNo . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) : $baseAssetNo;
                    if ($this->equipmentModel->assetNumberExists($uniqueNo)) continue;

                    $unitData = $data;
                    $unitData['asset_number'] = $uniqueNo;
                    $unitData['quantity'] = 1;
                    $unitData['qr_code'] = QRHelper::generateEquipment($uniqueNo);
                    $unitData['pictures'] = $picturesJson;
                    $unitData['doc_support'] = $pdfPath;

                    if ($this->equipmentModel->insert($unitData)) $successCount++;
                }
                $this->jsonResponse(['success' => true, 'message' => "$successCount units successfully added"]);
            }

            // LOGIKA EDIT (EDIT)
            else {
                $id = intval($_POST['id']);

                // Cek duplikat nomor aset (kecuali milik sendiri)
                if ($this->equipmentModel->assetNumberExists($data['asset_number'], $id)) {
                    $this->jsonResponse(['success' => false, 'message' => 'Number of Asset already used']);
                    exit;
                }

                // Ambil data lama untuk cek file yang sudah ada
                $oldData = $this->equipmentModel->getById($id);

                // LOGIKA GANTI GAMBAR (REPLACE)
                if ($picturesJson) {
                    if (!empty($oldData['pictures'])) {
                        $oldPics = json_decode($oldData['pictures'], true);
                        if (is_array($oldPics)) {
                            foreach ($oldPics as $p) {
                                // PERBAIKAN: Cek apakah foto dipakai alat lain
                                if (!$this->isFileShared($p, $id, 'pictures')) {
                                    $filePath = __DIR__ . '/../../' . $p;
                                    if (file_exists($filePath)) @unlink($filePath);
                                }
                            }
                        }
                    }
                    $data['pictures'] = $picturesJson;
                } else {
                    $data['pictures'] = $oldData['pictures'];
                }

                // LOGIKA GANTI DOKUMEN PDF (REPLACE)
                if ($pdfPath) {
                    if (!empty($oldData['doc_support'])) {
                        // PERBAIKAN: Cek apakah PDF dipakai alat lain
                        if (!$this->isFileShared($oldData['doc_support'], $id, 'doc_support')) {
                            $oldPdfPath = __DIR__ . '/../../' . $oldData['doc_support'];
                            if (file_exists($oldPdfPath)) @unlink($oldPdfPath);
                        }
                    }
                    $data['doc_support'] = $pdfPath;
                } else {
                    $data['doc_support'] = $oldData['doc_support'];
                }

                // Update QR Code jika nomor aset berubah
                $data['qr_code'] = QRHelper::generateEquipment($data['asset_number']);

                // Eksekusi Update
                $result = $this->equipmentModel->update($id, $data);
                $this->jsonResponse(['success' => $result, 'message' => $result ? 'Updated successfully' : 'Update failed']);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID Required']);
            exit;
        }

        // Jika alat sedang dipinjam, tolak penghapusan
        if ($this->borrowedModel->checkIsBorrowed($id)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Cannot delete! This equipment is currently being borrowed.'
            ]);
            exit;
        }

        $equipment = $this->equipmentModel->getById($id);

        if ($equipment) {
            // Hapus gambar hanya jika tidak dipakai alat lain
            if (!empty($equipment['pictures'])) {
                $pics = json_decode($equipment['pictures'], true);
                if (is_array($pics)) {
                    foreach ($pics as $p) {
                        if (!$this->isFileShared($p, $id, 'pictures')) {
                            $filePath = __DIR__ . '/../../' . $p;
                            if (file_exists($filePath)) @unlink($filePath);
                        }
                    }
                }
            }
            // Hapus dokumen hanya jika tidak dipakai alat lain
            if (!empty($equipment['doc_support'])) {
                if (!$this->isFileShared($equipment['doc_support'], $id, 'doc_support')) {
                    $pdfPathToRemove = __DIR__ . '/../../' . $equipment['doc_support'];
                    if (file_exists($pdfPathToRemove)) @unlink($pdfPathToRemove);
                }
            }
        }

        // Hapus Data dari Database
        if ($this->equipmentModel->delete($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Deleted']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete']);
        }
    }

    // Export PDF
    public function exportPDF()
    {
        $equipmentId = $this->getRouteParam(2);
        if (!$equipmentId && isset($_GET['id'])) $equipmentId = intval($_GET['id']);
        if (!$equipmentId) die('ID required');

        $equipment = $this->equipmentModel->getById($equipmentId);
        if (!$equipment) die('Data not found');

        require_once __DIR__ . '/../helpers/PDFHelper.php';
        $pdf = new PDFHelper();

        $logoPath = realpath(__DIR__ . '/../../public/images/logo.png');
        $footerImagePath = realpath(__DIR__ . '/../../public/images/footer.png');
        $pdf->setupHeaderFooter('Data Teknis Peralatan', $logoPath, $footerImagePath);

        // Data Processing
        $statusRaw = strtolower($equipment['condition_status'] ?? '');
        $kondisiIndo = 'Baik';
        if ($statusRaw == 'maintenance') $kondisiIndo = 'Perlu Pemeliharaan';
        elseif ($statusRaw == 'repair') $kondisiIndo = 'Dalam Perbaikan';
        elseif ($statusRaw == 'damaged') $kondisiIndo = 'Rusak';

        $tglKalibrasi = $equipment['calibration_date'];
        $freq = intval(preg_replace('/\D/', '', $equipment['maintenance_frequency'] ?? 0));
        $jatuhTempoStr = '-';
        if ($tglKalibrasi && $tglKalibrasi != '0000-00-00' && $freq > 0) {
            $date = new DateTime($tglKalibrasi);
            $date->modify("+$freq months");
            $jatuhTempoStr = $date->format('d-m-Y');
        }

        // BAGIAN I - III
        $pdf->addSection('I. Informasi Umum');
        $pdf->addInfoRow('Nama Peralatan', $equipment['equipment_name'] ?? '-');
        $pdf->addInfoRow('Nomor Aset', $equipment['asset_number'] ?? '-');
        $pdf->addInfoRow('Pemilik', $equipment['owner'] ?? '-');
        $pdf->addInfoRow('Penanggung Jawab', $equipment['responsible_person'] ?? '-');
        $pdf->addInfoRow('Merek / Tipe', $equipment['type'] ?? '-');
        $pdf->addInfoRow('Nomor Seri', $equipment['serial_number'] ?? '-');
        $pdf->addInfoRow('Pabrik Pembuat', $equipment['manufacturer'] ?? '-');
        $pdf->addInfoRow('Tanggal Pembelian', ($equipment['purchase_date'] && $equipment['purchase_date'] != '0000-00-00') ? date('d-m-Y', strtotime($equipment['purchase_date'])) : '-');
        $pdf->addInfoRow('Kondisi', $kondisiIndo);
        $pdf->getPDF()->Ln(3);

        $pdf->addSection('II. Spesifikasi Teknis');
        $pdf->addInfoRow('Kapasitas', $equipment['capacity'] ?? '-');
        $pdf->addInfoRow('Dimensi', $equipment['dimensions'] ?? '-');
        $pdf->addInfoRow('Berat (kg)', $equipment['weight'] ?? '-');
        $pdf->addInfoRow('Temperatur Penyimpanan', $equipment['storage_temp'] ?? '-');
        $pdf->addInfoRow('Kelembapan', $equipment['humidity'] ?? '-');
        $pdf->getPDF()->Ln(3);

        $pdf->addSection('III. Informasi Pemeliharaan');
        $pdf->addInfoRow('No Sertifikat Kalibrasi', $equipment['calibration_cert_no'] ?? '-');
        $pdf->addInfoRow('Tanggal Kalibrasi', ($equipment['calibration_date'] && $equipment['calibration_date'] != '0000-00-00') ? date('d-m-Y', strtotime($equipment['calibration_date'])) : '-');
        $pdf->addInfoRow('Frekuensi Pemeliharaan', ($equipment['maintenance_frequency'] ?? '-') . ' Bulan');
        $pdf->addInfoRow('Jatuh Tempo Berikutnya', $jatuhTempoStr);
        $pdf->addInfoRow('Vendor Pendukung', $equipment['supporting_vendor'] ?? '-');
        $pdf->getPDF()->Ln(3);

        // BAGIAN IV: Langkah Pemakaian
        $pdf->addSection('IV. Langkah Pemakaian');
        $rawUsage = $equipment['usage_steps'];

        if (trim(strip_tags($rawUsage)) == '') {
            $pdf->addHTMLBox("Tidak ada langkah pemakaian");
        } else {
            $pdf->addHTMLBox($rawUsage);
        }
        $pdf->getPDF()->Ln(3);

        // BAGIAN V: Dokumentasi
        $pdf->checkPageBreak(60);

        $pdf->addSection('V. Dokumentasi');
        $images = [];
        if (!empty($equipment['pictures'])) {
            $pics = json_decode($equipment['pictures'], true);
            if (is_array($pics)) {
                foreach ($pics as $p) {
                    $relativePath = __DIR__ . '/../../' . $p;
                    $absolutePath = realpath($relativePath);
                    if ($absolutePath && file_exists($absolutePath)) {
                        $images[] = $absolutePath;
                    }
                }
            }
        }

        $pdf->addImagesAsHTML($images, 4);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '', $equipment['asset_number']);
        $pdf->output('Equipment_' . $safeName . '.pdf');
    }

    // HELPER
    private function collectPostData()
    {
        $fields = [
            'equipment_name',
            'quantity',
            'location_id',
            'category_id',
            'asset_number',
            'owner',
            'responsible_person',
            'type',
            'serial_number',
            'manufacturer',
            'equipment_details',
            'purchase_date',
            'condition_status',
            'capacity',
            'dimensions',
            'weight',
            'storage_temp',
            'humidity',
            'calibration_cert_no',
            'calibration_date',
            'maintenance_frequency',
            'supporting_vendor',
            'usage_steps'
        ];

        $data = [];
        foreach ($fields as $f) $data[$f] = !empty($_POST[$f]) ? trim($_POST[$f]) : null;

        $data['quantity'] = intval($data['quantity'] ?? 1);
        $data['location_id'] = !empty($data['location_id']) ? intval($data['location_id']) : null;
        $data['category_id'] = !empty($data['category_id']) ? intval($data['category_id']) : null;

        return $data;
    }

    private function handleUpload($key)
    {
        // Cek apakah ada file yang diupload
        if (isset($_FILES[$key]) && !empty($_FILES[$key]['name'][0])) {

            $uploadedPaths = []; // Array untuk menampung path file yang berhasil

            // Loop setiap file yang dikirim (karena multiple)
            foreach ($_FILES[$key]['name'] as $i => $name) {

                // Cek jika tidak ada error pada file ke-i
                if ($_FILES[$key]['error'][$i] == 0) {

                    $tmpName = $_FILES[$key]['tmp_name'][$i];

                    // Panggil fungsi kompresi untuk satu file ini
                    $savedPath = $this->compressImage($tmpName, $name);

                    if ($savedPath) {
                        $uploadedPaths[] = $savedPath;
                    }
                }
            }

            // Kembalikan JSON jika ada file yang berhasil, atau null jika kosong
            return !empty($uploadedPaths) ? json_encode($uploadedPaths) : null;
        }
        return null;
    }

    private function compressImage($source, $originalName)
    {
        $uploadDir = __DIR__ . '/../../public/uploads/equipment/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // Ambil info gambar
        $imageInfo = getimagesize($source);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = uniqid('eq_') . '.' . $ext;

        $targetAbsolutePath = $uploadDir . $fileName; // Path untuk fungsi PHP
        $dbPath = 'public/uploads/equipment/' . $fileName; // Path untuk disimpan di Database

        // Validasi: Jika bukan gambar valid, coba upload biasa (fallback)
        if (!$imageInfo) {
            if (move_uploaded_file($source, $targetAbsolutePath)) {
                return $dbPath;
            }
            return null;
        }

        $mime = $imageInfo['mime'];

        // Setting Kompresi
        $maxWidth = 800; // Resize lebar maks 800px
        $quality = 70;   // Kualitas JPG 70%

        // Buat resource gambar dari source
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            default:
                // Jika format lain (misal WEBP/BMP), upload biasa tanpa kompresi
                if (move_uploaded_file($source, $targetAbsolutePath)) {
                    return $dbPath;
                }
                return null;
        }

        // LOGIKA RESIZE
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = floor($height * ($maxWidth / $width));

            $image_p = imagecreatetruecolor($newWidth, $newHeight);

            // Handle Transparansi PNG agar tidak hitam
            if ($mime == 'image/png') {
                imagealphablending($image_p, false);
                imagesavealpha($image_p, true);
                $transparent = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
                imagefilledrectangle($image_p, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $image_p; // Replace image asli dengan yang resize
        }

        // SIMPAN GAMBAR TERKOMPRESI
        $result = false;
        if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
            $result = imagejpeg($image, $targetAbsolutePath, $quality);
        } elseif ($mime == 'image/png') {
            $result = imagepng($image, $targetAbsolutePath, 6); // Level kompresi 0-9
        }

        imagedestroy($image); // Bersihkan memori

        if ($result) {
            return $dbPath;
        }
        return null;
    }

    private function processPdfUpload($file)
    {
        // 1. Setting Folder Tujuan
        $targetDir = "public/uploads/documents/";
        $uploadPath = __DIR__ . '/../../' . $targetDir;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // 2. Validasi Ekstensi
        $fileName = time() . '_' . basename($file["name"]); // Rename agar unik
        $targetFilePath = $uploadPath . $fileName;
        $dbPath = $targetDir . $fileName; // Path untuk database

        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        if ($fileType != "pdf") {
            return ['status' => false, 'message' => 'File format must be PDF.'];
        }

        // 3. Validasi Ukuran
        $maxSize = 2 * 1024 * 1024;

        if ($file["size"] > $maxSize) {
            return [
                'status' => false,
                'message' => 'File size is too large! Maximum 2MB. Please compress your PDF file first.'
            ];
        }

        // 4. Upload File
        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            return ['status' => true, 'path' => $dbPath];
        }

        return ['status' => false, 'message' => 'Failed to upload document.'];
    }
}
