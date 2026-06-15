<?php

class ConsumableController extends Controller
{
    private $consumableModel;
    private $locationModel;
    private $categoryModel;

    public function __construct()
    {
        $this->requireAdmin();
        $this->consumableModel = $this->model('Consumable');
        $this->locationModel = $this->model('Location');
        $this->categoryModel = $this->model('Category');
    }

    public function index()
    {
        $data = [
            'title' => 'Inventory Data - ' . APP_NAME,
            'pageCSS' => 'asset',
            'locations' => $this->locationModel->getAll(),
            'categories' => $this->categoryModel->getAll(),
            'pageJS' => 'consumable'
        ];
        $this->view('consumable/index', $data);
    }

    public function inout()
    {
        $data = [
            'title' => 'Stock In/Out - ' . APP_NAME,
            'pageCSS' => 'asset',
            'pageJS' => 'consumable_inout'
        ];
        $this->view('consumable/inout', $data);
    }

    public function detail($id = null)
    {
        if (!$id && isset($_GET['id'])) {
            $id = intval($_GET['id']);
        }

        $item = $this->consumableModel->getById($id);

        $logs = $this->consumableModel->getLogsByItemId($id);

        $data = [
            'title' => 'Detail Inventory - ' . APP_NAME,
            'pageCSS' => 'asset',
            'item' => $item,
            'logs' => $logs ? $logs : [], 
            'pageJS' => 'consumable_detail'
        ];

        $this->view('consumable/detail', $data);
    }

    // API METHODS

    public function getAll()
    {
        header('Content-Type: application/json');
        try {
            $data = $this->consumableModel->getAllWithDetails();
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getById()
    {
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID Required']);
            exit;
        }

        try {
            $data = $this->consumableModel->getById($id);
            $this->jsonResponse(['success' => (bool)$data, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // CRUD OPERATIONS

    public function add()
    {
        $this->processForm('add');
    }
    public function edit()
    {
        $this->processForm('edit');
    }

    private function processForm($mode)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid Request']);
        }

        try {
            $data = $this->collectPostData();

            // Validasi Wajib
            if (empty($data['item_name']) || empty($data['item_code'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Asset Name & Code must be filled']);
                exit;
            }

            // Upload Foto (Multiple)
            $picturesJson = $this->handleUploadMultiple('pictures');

            // Upload PDF (Single)
            $pdfPath = null;
            if (isset($_FILES['doc_support']) && $_FILES['doc_support']['error'] == 0) {
                $up = $this->processPdfUpload($_FILES['doc_support']);
                if ($up['status']) {
                    $pdfPath = $up['path'];
                } else {
                    $this->jsonResponse(['success' => false, 'message' => $up['message']]);
                    exit;
                }
            }

            if ($mode == 'add') {
                // Cek Duplikat Kode
                if ($this->consumableModel->itemCodeExists($data['item_code'])) {
                    $this->jsonResponse(['success' => false, 'message' => 'Asset Code already exists']);
                    exit;
                }

                // Set Default Data untuk Insert Baru
                $data['date'] = date('Y-m-d');
                $data['status'] = 'in'; // Stok awal dianggap IN
                $data['remark'] = 'Stok Awal';
                $data['pictures'] = $picturesJson;
                $data['doc_support'] = $pdfPath;

                if ($this->consumableModel->insert($data)) {
                    $this->jsonResponse(['success' => true, 'message' => 'Successfully added']);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to add data']);
                }
            } else {
                // MODE EDIT
                $id = intval($_POST['id']);

                // Cek Duplikat (kecuali punya sendiri)
                if ($this->consumableModel->itemCodeExists($data['item_code'], $id)) {
                    $this->jsonResponse(['success' => false, 'message' => 'Asset Code already used by another item']);
                    exit;
                }

                // Ambil data lama sebelum diupdate
                $oldData = $this->consumableModel->getById($id);

                if ($picturesJson) {
                    // KASUS A: User Mengupload Gambar Baru

                    // 1. Hapus File Fisik Gambar Lama
                    if (!empty($oldData['pictures'])) {
                        $oldPicsArray = json_decode($oldData['pictures'], true);
                        if (is_array($oldPicsArray)) {
                            foreach ($oldPicsArray as $picPath) {
                                $fullPathToDelete = __DIR__ . '/../../' . $picPath;
                                if (file_exists($fullPathToDelete)) {
                                    unlink($fullPathToDelete); // Hapus file!
                                }
                            }
                        }
                    }

                    // 2. Gunakan Gambar Baru Saja (Tidak di-merge)
                    $data['pictures'] = $picturesJson;
                } else {
                    // KASUS B: Tidak Ada Upload Baru
                    $data['pictures'] = $oldData['pictures'];
                }

                // Logika PDF 
                if ($pdfPath) {
                    // Hapus file fisik lama jika ada
                    if (!empty($oldData['doc_support']) && file_exists(__DIR__ . '/../../' . $oldData['doc_support'])) {
                        unlink(__DIR__ . '/../../' . $oldData['doc_support']);
                    }
                    $data['doc_support'] = $pdfPath;
                } else {
                    // Pakai data lama
                    $data['doc_support'] = $oldData['doc_support'];
                }

                if ($this->consumableModel->update($id, $data)) {
                    $this->jsonResponse(['success' => true, 'message' => 'Successfully updated. Old images replaced.']);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to update data']);
                }
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

        try {
            // Hapus file fisik sebelum hapus data DB
            $item = $this->consumableModel->getById($id);
            if ($item) {
                if (!empty($item['doc_support']) && file_exists(__DIR__ . '/../../' . $item['doc_support'])) {
                    unlink(__DIR__ . '/../../' . $item['doc_support']);
                }
                if (!empty($item['pictures'])) {
                    $pics = json_decode($item['pictures'], true);
                    if (is_array($pics)) {
                        foreach ($pics as $p) {
                            if (file_exists(__DIR__ . '/../../' . $p)) unlink(__DIR__ . '/../../' . $p);
                        }
                    }
                }
            }

            if ($this->consumableModel->delete($id)) {
                $this->jsonResponse(['success' => true, 'message' => 'Deleted data successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete data']);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // IN/OUT STOCK

    public function updateStock()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid Request']);
        }

        try {
            $id = intval($_POST['item_id'] ?? 0);
            $quantityChange = intval($_POST['quantity'] ?? 0);
            $status = $_POST['status'] ?? '';
            $remark = trim($_POST['remark'] ?? '');
            $date = $_POST['date'] ?? date('Y-m-d');

            if (!$id || !$quantityChange || !in_array($status, ['in', 'out'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Incomplete In/Out data']);
                exit;
            }

            $item = $this->consumableModel->getById($id);
            if (!$item) {
                $this->jsonResponse(['success' => false, 'message' => 'Item not found']);
                exit;
            }

            // Hitung Stok Baru
            $newQty = ($status === 'in')
                ? $item['quantity'] + $quantityChange
                : $item['quantity'] - $quantityChange;

            if ($newQty < 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Stock not sufficient. Remaining: ' . $item['quantity']]);
                exit;
            }

            // Handle Dokumen Transaksi (Opsional)
            $doc = null;
            if (isset($_FILES['doc_support']) && $_FILES['doc_support']['error'] == 0) {
                $up = $this->processPdfUpload($_FILES['doc_support']);
                if ($up['status']) {
                    $doc = $up['path'];
                }
            }

            if (!$doc) $doc = $item['doc_support'];

            $res = $this->consumableModel->updateStock($id, $newQty, $status, $date, $remark, $doc, $quantityChange);

            if ($res) {
                $this->jsonResponse(['success' => true, 'message' => 'Stock successfully updated']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update stock']);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getHistory()
    {
        header('Content-Type: application/json');
        try {
            // Ambil data semua barang
            $items = $this->consumableModel->getHistory(1000);
            $allTransactions = [];

            foreach ($items as $item) {
                if (!empty($item['transaction_log'])) {
                    $logs = json_decode($item['transaction_log'], true);
                    if (is_array($logs)) {
                        foreach ($logs as $log) {
                            $allTransactions[] = [
                                'date' => $log['date'] ?? '-',
                                'item_code' => $item['item_code'],
                                'item_name' => $item['item_name'],
                                'status' => $log['status'] ?? '-',
                                'remark' => $log['remark'] ?? '-',
                                'quantity' => $log['qty_change'] ?? 0,
                                'current_balance' => $log['current_balance'] ?? '-' // <--- BACA SISA STOK
                            ];
                        }
                    }
                } else if (!empty($item['date']) && $item['date'] != '0000-00-00') {
                    $allTransactions[] = [
                        'date' => $item['date'],
                        'item_code' => $item['item_code'],
                        'item_name' => $item['item_name'],
                        'status' => $item['status'],
                        'remark' => $item['remark'],
                        'quantity' => $item['quantity'],
                        'current_balance' => $item['quantity']
                    ];
                }
            }

            // Urutkan transaksi dari yang paling baru
            usort($allTransactions, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            $this->jsonResponse(['success' => true, 'data' => $allTransactions]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // CSV IMPORT / EXPORT
    public function downloadTemplate()
    {
        $filename = 'template_inventory.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM for Excel

        // Header sesuai field baru
        fputcsv($out, [
            'Asset Name*',
            'Item Code*',
            'Merk',
            'Responsible Person',
            'User',
            'Unit of Measure (UOM)',
            'Min Order',
            'Quantity',
            'ID Location',
            'ID Category',
            'Vendor Support',
            'Condition (good/damaged)'
        ]);

        fclose($out);
        exit;
    }

    public function import()
    {
        header('Content-Type: application/json');
        if (!isset($_FILES['file_csv'])) {
            $this->jsonResponse(['success' => false, 'message' => 'File not found']);
            exit;
        }

        $file = $_FILES['file_csv']['tmp_name'];
        $handle = fopen($file, "r");

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom != "\xEF\xBB\xBF") rewind($handle);

        fgetcsv($handle); // Skip Header

        $success = 0;
        $fail = 0;
        $errors = [];
        $row = 1;

        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                $insertData = [
                    'item_name'         => trim($data[0] ?? ''),
                    'item_code'         => trim($data[1] ?? ''),
                    'merk'              => trim($data[2] ?? ''),
                    'responsible_person' => trim($data[3] ?? ''),
                    'assigned_to'       => trim($data[4] ?? ''),
                    'uom'               => trim($data[5] ?? ''),
                    'min_order'         => intval($data[6] ?? 6),
                    'quantity'          => intval($data[7] ?? 0),
                    'location_id'       => !empty($data[8]) ? intval($data[8]) : null,
                    'category_id'       => !empty($data[9]) ? intval($data[9]) : null,
                    'supporting_vendor' => trim($data[10] ?? ''),
                    'condition_status'  => strtolower(trim($data[11] ?? 'good')),
                    'date'              => date('Y-m-d'),
                    'status'            => 'in',
                    'remark'            => 'Imported from CSV',
                    'pictures'          => null,
                    'doc_support'       => null
                ];

                if (empty($insertData['item_name']) || empty($insertData['item_code'])) {
                    $fail++;
                    $errors[] = "Row $row: Name/Code is empty.";
                    continue;
                }

                if (!$this->consumableModel->itemCodeExists($insertData['item_code'])) {
                    if ($this->consumableModel->insert($insertData)) $success++;
                    else {
                        $fail++;
                        $errors[] = "Row $row: DB Insert Error";
                    }
                } else {
                    $fail++;
                    $errors[] = "Row $row: Code {$insertData['item_code']} already exists.";
                }
            }
            fclose($handle);

            $this->jsonResponse([
                'success' => true,
                'message' => "Import Complete. Success: $success. Failure: $fail.",
                'debug_errors' => $errors
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // HELPER
    private function collectPostData()
    {
        $fields = [
            'item_name',
            'item_code',
            'merk',
            'responsible_person',
            'assigned_to',
            'uom',
            'min_order',
            'quantity',
            'location_id',
            'category_id',
            'supporting_vendor',
            'condition_status'
        ];

        $data = [];
        foreach ($fields as $f) {
            $data[$f] = !empty($_POST[$f]) ? trim($_POST[$f]) : null;
        }

        // Pastikan angka valid
        $data['quantity'] = intval($data['quantity'] ?? 0);
        $data['min_order'] = intval($data['min_order'] ?? 6);

        return $data;
    }

    private function handleUploadMultiple($key)
    {
        if (isset($_FILES[$key]) && !empty($_FILES[$key]['name'][0])) {
            $uploaded = [];
            foreach ($_FILES[$key]['name'] as $i => $name) {
                if ($_FILES[$key]['error'][$i] == 0) {
                    $tmpName = $_FILES[$key]['tmp_name'][$i];

                    // Panggil fungsi kompresi
                    $savedPath = $this->compressImage($tmpName, $name);

                    if ($savedPath) {
                        $uploaded[] = $savedPath;
                    }
                }
            }
            return !empty($uploaded) ? json_encode($uploaded) : null;
        }
        return null;
    }

    // Fungsi Helper untuk Kompresi Gambar
    private function compressImage($source, $originalName)
    {
        $uploadDir = __DIR__ . '/../../public/uploads/consumable/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // Ambil informasi gambar
        $imageInfo = getimagesize($source);
        if (!$imageInfo) {
            // Fallback: Upload biasa jika gagal deteksi gambar
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = uniqid('con_') . '.' . $ext;
            if (move_uploaded_file($source, $uploadDir . $fileName)) {
                return 'public/uploads/consumable/' . $fileName;
            }
            return null;
        }

        $mime = $imageInfo['mime'];
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = uniqid('con_') . '.' . $ext;

        $targetAbsolutePath = $uploadDir . $fileName;
        $dbPath = 'public/uploads/consumable/' . $fileName;

        // Setting Kompresi
        $maxWidth = 800; // Resize lebar maks 800px
        $quality = 70;   // Kualitas JPG 70%

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            default:
                // Jika bukan JPG/PNG, upload biasa
                if (move_uploaded_file($source, $targetAbsolutePath)) {
                    return $dbPath;
                }
                return null;
        }

        // Logika Resize
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = floor($height * ($maxWidth / $width));

            $image_p = imagecreatetruecolor($newWidth, $newHeight);

            // Handle Transparansi PNG
            if ($mime == 'image/png') {
                imagealphablending($image_p, false);
                imagesavealpha($image_p, true);
                $transparent = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
                imagefilledrectangle($image_p, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $image_p;
        }

        // Simpan & Kompress
        $result = false;
        if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
            $result = imagejpeg($image, $targetAbsolutePath, $quality);
        } elseif ($mime == 'image/png') {
            $result = imagepng($image, $targetAbsolutePath, 6);
        }

        imagedestroy($image);

        if ($result) {
            return $dbPath;
        }
        return null;
    }

    // Validasi Ukuran PDF Max 2MB
    private function processPdfUpload($file)
    {
        $targetDir = "public/uploads/documents/";
        if (!is_dir(__DIR__ . '/../../' . $targetDir)) mkdir(__DIR__ . '/../../' . $targetDir, 0777, true);

        $fileName = time() . '_' . basename($file["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        // 1. Validasi Ekstensi
        if ($fileType != "pdf") {
            return ['status' => false, 'message' => 'Only PDF files are allowed.'];
        }

        // 2. Validasi Ukuran (Max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB dalam bytes
        if ($file["size"] > $maxSize) {
            return ['status' => false, 'message' => 'File PDF too large! Max 2MB.'];
        }

        // 3. Upload
        if (move_uploaded_file($file["tmp_name"], __DIR__ . '/../../' . $targetFilePath)) {
            return ['status' => true, 'path' => $targetFilePath];
        }

        return ['status' => false, 'message' => 'Failed to upload document.'];
    }
}
