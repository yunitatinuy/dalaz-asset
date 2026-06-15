<?php

class AssetController extends Controller
{
    private $assetModel;
    private $locationModel;
    private $categoryModel;

    public function __construct()
    {
        $this->assetModel = $this->model('Asset');
        $this->locationModel = $this->model('Location');
        $this->categoryModel = $this->model('Category');
    }

    public function index()
    {
        header('Location: ' . BASE_URL . '/asset/company');
        exit;
    }

    public function company()
    {
        $this->requireAdmin();

        $data = [
            'title' => 'Office Equipment',
            'pageCSS' => 'asset',
            'assets' => $this->assetModel->getCompanyAssets(),
            'locations' => $this->locationModel->getAll(),
            'categories' => $this->categoryModel->getAll(),
            'pageJS' => 'asset'
        ];

        $this->view('asset/company', $data);
    }

    public function damaged()
    {
        $this->requireAdmin();
        $data = [
            'title' => 'Damaged Assets',
            'pageCSS' => 'asset',
            'assets' => $this->assetModel->getDamagedAssets()
        ];
        $this->view('asset/damaged', $data);
    }

    // API & SEARCH
    public function getAll()
    {
        header('Content-Type: application/json');
        $search = isset($_GET['search']) ? trim($_GET['search']) : null;
        echo json_encode(['success' => true, 'data' => $this->assetModel->getAllWithDetails($search)]);
        exit;
    }

    public function getById()
    {
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        if ($id) echo json_encode(['success' => true, 'data' => $this->assetModel->getById($id)]);
        else echo json_encode(['success' => false, 'message' => 'ID Missing']);
        exit;
    }

    // fungsi CSV 
    public function downloadTemplate()
    {
        $filename = "template_office_equipment.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // Tambah BOM for UTF-8
        fputs($output, "\xEF\xBB\xBF");

        // Header CSV nya
        $headers = [
            'Asset Name*',
            'Asset Code*',
            'Location ID (Number)',
            'Category ID (Number)',
            'Quantity',
            'Condition (good/damaged)',
            'Owner',
            'Person in Charge',
            'User',
            'Brand',
            'Serial Number',
            'Purchase Date (YYYY-MM-DD)',
            'Details',
            'Capacity',
            'Dimensions',
            'Weight',
            'Color',
            'Maint. Frequency',
            'Vendor'
        ];
        fputcsv($output, $headers);
        fclose($output);
        exit;
    }

    // Helper dari Excel (MM/DD/YYYY) ke MySQL (YYYY-MM-DD)
    private function formatDateForDB($dateStr)
    {
        if (empty($dateStr)) return null;

        // cek jika tersedia YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // Handle MM/DD/YYYY atau M/D/YYYY
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null; // tanggal ga valid
    }

    public function import()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid file']);
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];

        // Validasi tipe MIME (Harus text)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);

        // Izinkan csv, plain text, atau excel csv 
        $allowedMimes = ['text/plain', 'text/csv', 'application/vnd.ms-excel', 'text/x-csv'];
        if (!in_array($mime, $allowedMimes)) {
            echo json_encode(['success' => false, 'message' => 'Wrong file format. Make sure the file is CSV (Comma Delimited).']);
            exit;
        }

        $handle = fopen($file, "r");

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom != "\xEF\xBB\xBF") rewind($handle);

        fgetcsv($handle); // Skip Header

        $count = 0;
        $errors = [];
        $rowIdx = 1;

        try {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowIdx++;

                // Validasi minimal
                if (empty($row[0]) || empty($row[1])) {
                    $errors[] = "Row $rowIdx: Name/Code is empty.";
                    continue;
                }

                $baseCode = trim($row[1]);
                $quantity = intval($row[4] ?? 1);

                // Mencegah pemeriksaan kode dasar duplikat (hanya jika jumlahnya 1)
                if ($quantity <= 1 && $this->assetModel->assetCodeExists($baseCode)) {
                    $errors[] = "Row $rowIdx: Code {$baseCode} already exists.";
                    continue;
                }

                // Data Template
                $templateData = [
                    'asset_name' => trim($row[0]),
                    'location_id' => !empty($row[2]) ? intval($row[2]) : null,
                    'category_id' => !empty($row[3]) ? intval($row[3]) : null,
                    'description' => strtolower(trim($row[5] ?? 'good')),
                    'owner'      => trim($row[6] ?? ''),
                    'responsible_person' => trim($row[7] ?? ''),
                    'assigned_to' => trim($row[8] ?? ''),
                    'brand'      => trim($row[9] ?? ''),
                    'serial_number' => trim($row[10] ?? ''),
                    'purchase_date' => $this->formatDateForDB($row[11] ?? ''),
                    'details'    => trim($row[12] ?? ''),
                    'capacity'   => trim($row[13] ?? ''),
                    'dimensions' => trim($row[14] ?? ''),
                    'weight'     => trim($row[15] ?? ''),
                    'color'      => trim($row[16] ?? ''),
                    'maintenance_frequency' => trim($row[17] ?? ''),
                    'vendor'     => trim($row[18] ?? ''),
                    'pictures'   => null
                ];

                // Loop untuk qty > 1
                for ($i = 1; $i <= $quantity; $i++) {
                    // Logika: Jika Jumlah > 1, tambahkan -01, -02 (Penambahan 2 digit)
                    $finalCode = ($quantity > 1) ? $baseCode . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) : $baseCode;

                    // Periksa apakah kode yang dihasilkan ini sudah ada
                    if ($this->assetModel->assetCodeExists($finalCode)) {
                        $errors[] = "Row $rowIdx: Code {$finalCode} (Unit $i) already exists, skipped.";
                        continue;
                    }

                    $insertData = $templateData;
                    $insertData['asset_code'] = $finalCode;
                    $insertData['quantity'] = 1; // Selalu 1 per baris di database
                    $insertData['qr_code'] = QRHelper::generate($finalCode);

                    if ($this->assetModel->insert($insertData)) {
                        $count++;
                    }
                }
            }

            fclose($handle);

            $msg = "Import completed. $count assets successfully added.";
            if (count($errors) > 0) $msg .= " There are " . count($errors) . " errors (see details).";

            echo json_encode([
                'success' => true,
                'message' => $msg,
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function collectPostData()
    {
        return [
            'asset_name' => trim($_POST['assetName'] ?? ''),
            'asset_code' => trim($_POST['assetCode'] ?? ''),
            'quantity' => intval($_POST['totalQuantity'] ?? 1),
            'location_id' => !empty($_POST['location']) ? intval($_POST['location']) : null,
            'category_id' => !empty($_POST['category']) ? intval($_POST['category']) : null,
            'description' => trim($_POST['status'] ?? 'good'),
            'owner' => trim($_POST['owner'] ?? ''),
            'responsible_person' => trim($_POST['responsible_person'] ?? ''),
            'assigned_to' => trim($_POST['assigned_to'] ?? ''),
            'brand' => trim($_POST['brand'] ?? ''),
            'serial_number' => trim($_POST['serial_number'] ?? ''),
            'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
            'details' => trim($_POST['details'] ?? ''),
            'capacity' => trim($_POST['capacity'] ?? ''),
            'dimensions' => trim($_POST['dimensions'] ?? ''),
            'weight' => trim($_POST['weight'] ?? ''),
            'color' => trim($_POST['color'] ?? ''),
            'maintenance_frequency' => trim($_POST['maintenance_frequency'] ?? ''),
            'vendor' => trim($_POST['vendor'] ?? ''),
        ];
    }

    private function uploadImage($file)
    {
        $uploadDir = __DIR__ . '/../../public/uploads/assets/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return null;
        }

        $mime = $imageInfo['mime'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('asset_') . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        // Setting Kompresi
        $maxWidth = 800; // Maksimal lebar 800px
        $quality = 70;   // Kualitas 70%

        // Buat resource gambar baru berdasarkan tipe file
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file['tmp_name']);
                break;
            default:
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    return $fileName;
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

            // Handle Transparansi untuk PNG
            if ($mime == 'image/png') {
                imagealphablending($image_p, false);
                imagesavealpha($image_p, true);
                $transparent = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
                imagefilledrectangle($image_p, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $image_p;
        }

        // LOGIKA SIMPAN & KOMPRESS
        $result = false;
        if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
            $result = imagejpeg($image, $targetPath, $quality);
        } elseif ($mime == 'image/png') {
            $result = imagepng($image, $targetPath, 6);
        }

        imagedestroy($image);

        if ($result) {
            return $fileName;
        }

        return null;
    }

    // Cek apakah foto dipakai oleh aset lain
    private function isImageShared($pictureName, $excludeId)
    {
        $db = new Database();
        $db->query("SELECT id FROM assets WHERE pictures = :pic AND id != :id LIMIT 1");
        $db->bind(':pic', $pictureName);
        $db->bind(':id', $excludeId);
        return $db->single() !== false;
    }

    public function add()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid Request']);
            exit;
        }

        try {
            $data = $this->collectPostData();

            if (empty($data['asset_code']) || empty($data['asset_name']) || $data['quantity'] < 1) {
                echo json_encode(['success' => false, 'message' => 'Name, Asset Code, and Quantity are required.']);
                exit;
            }

            // Pemeriksaan untuk kode jika jumlahnya 1
            if ($data['quantity'] == 1 && $this->assetModel->assetCodeExists($data['asset_code'])) {
                echo json_encode(['success' => false, 'message' => 'Asset code already used.']);
                exit;
            }

            // Handle Upload Image
            $picture = null;
            if (isset($_FILES['pictures']) && $_FILES['pictures']['error'] == UPLOAD_ERR_OK) {
                $picture = $this->uploadImage($_FILES['pictures']);
            }

            // Loop quantity (jika > 1, generate sequence)
            $qty = $data['quantity'];
            $baseCode = $data['asset_code'];
            $successCount = 0;

            for ($i = 1; $i <= $qty; $i++) {
                $uniqueCode = ($qty > 1) ? $baseCode . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) : $baseCode;

                if ($this->assetModel->assetCodeExists($uniqueCode)) {
                    continue;
                }

                $insertData = $data;
                $insertData['asset_code'] = $uniqueCode;
                $insertData['quantity'] = 1;
                $insertData['pictures'] = $picture;
                $insertData['qr_code'] = QRHelper::generate($uniqueCode);

                if ($this->assetModel->insert($insertData)) {
                    $successCount++;
                }
            }

            if ($successCount > 0) {
                echo json_encode(['success' => true, 'message' => "$successCount Office Equipment units successfully added."]);
            } else {
                echo json_encode(['success' => false, 'message' => "Failed to add. Asset code might be duplicated."]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function edit()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid Request']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
            exit;
        }

        try {
            $data = $this->collectPostData();

            // Cek duplikat kode
            if ($this->assetModel->assetCodeExists($data['asset_code'], $id)) {
                echo json_encode(['success' => false, 'message' => 'Asset code already used by another asset']);
                exit;
            }

            // Regenerate QR jika kode berubah
            $data['qr_code'] = QRHelper::generate($data['asset_code']);

            // Handle Image Update (Hapus yang lama kalau aman)
            if (isset($_FILES['pictures']) && $_FILES['pictures']['error'] == UPLOAD_ERR_OK) {
                $oldAsset = $this->assetModel->getById($id);
                if ($oldAsset && !empty($oldAsset['pictures'])) {
                    // Cek apakah dipakai aset lain (hasil dari input quantity > 1)
                    if (!$this->isImageShared($oldAsset['pictures'], $id)) {
                        $oldPicPath = __DIR__ . "/../../public/uploads/assets/" . $oldAsset['pictures'];
                        if (file_exists($oldPicPath)) unlink($oldPicPath);
                    }
                }
                $data['pictures'] = $this->uploadImage($_FILES['pictures']);
            }

            $result = $this->assetModel->update($id, $data);
            echo json_encode(['success' => $result, 'message' => $result ? 'Data successfully updated' : 'Failed to update']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function delete()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
            exit;
        }
        try {
            $asset = $this->assetModel->getById($id);

            // Jangan hapus fisik foto jika dipakai oleh barang lain
            if ($asset && !empty($asset['pictures'])) {
                if (!$this->isImageShared($asset['pictures'], $id)) {
                    $picPath = __DIR__ . "/../../public/uploads/assets/" . $asset['pictures'];
                    if (file_exists($picPath)) unlink($picPath);
                }
            }
            $result = $this->assetModel->delete($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Asset deleted!' : 'Failed to delete']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function markRepaired()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
            exit;
        }

        try {
            $asset = $this->assetModel->getById($id);
            if ($asset) {
                $asset['description'] = 'good'; // Ubah status jadi baik
                $result = $this->assetModel->update($id, $asset);
                echo json_encode(['success' => $result, 'message' => $result ? 'Asset marked as repaired' : 'Failed to update status']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Asset not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function dispose()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
            exit;
        }

        try {
            $asset = $this->assetModel->getById($id);

            // Jangan hapus fisik foto jika dipakai oleh barang lain
            if ($asset && !empty($asset['pictures'])) {
                if (!$this->isImageShared($asset['pictures'], $id)) {
                    $picPath = __DIR__ . "/../../public/uploads/assets/" . $asset['pictures'];
                    if (file_exists($picPath)) unlink($picPath);
                }
            }
            $result = $this->assetModel->delete($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Asset successfully disposed!' : 'Failed to dispose asset']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
