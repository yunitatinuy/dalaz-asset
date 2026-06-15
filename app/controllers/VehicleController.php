<?php

class VehicleController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = $this->model('Vehicle');
    }

    public function index()
    {
        $this->requireAdmin();

        $userModel = $this->model('User');
        $locationModel = $this->model('Location');
        $categoryModel = $this->model('Category');

        $data = [
            'title' => 'Vehicle Data',
            'pageCSS' => 'asset',
            'pageJS' => 'vehicle',
            'users' => $userModel->getAll(),
            'locations' => $locationModel->getAll(),
            'categories' => $categoryModel->getAll()
        ];
        $this->view('vehicle/index', $data);
    }

    // CRUD

    public function getAll()
    {
        header('Content-Type: application/json');
        try {
            $data = $this->model->getAll();
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getById()
    {
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        try {
            $data = $this->model->getById($id);
            echo json_encode(['success' => (bool)$data, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function add()
    {
        $this->processForm('add');
    }

    public function edit()
    {
        $this->processForm('edit');
    }

    public function delete()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID Required']);
            exit;
        }

        try {
            // Hapus File Fisik
            $item = $this->model->getById($id);
            if ($item) {
                $this->deleteFile($item['bpkb_path']);
                $this->deleteFile($item['stnk_path']);

                if (!empty($item['photos'])) {
                    $photos = json_decode($item['photos'], true);
                    if (is_array($photos)) {
                        foreach ($photos as $p) $this->deleteFile($p);
                    }
                }
            }

            if ($this->model->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Vehicle deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function processForm($mode)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid Request']);
            exit;
        }

        try {
            $data = $this->collectPostData();

            // Validasi Asset Code
            if (empty($data['asset_code'])) {
                echo json_encode(['success' => false, 'message' => 'Asset Code is required']);
                exit;
            }

            // 1. Upload BPKB (Hybrid: PDF/Image)
            $bpkbPath = null;
            if (isset($_FILES['bpkb_path']) && $_FILES['bpkb_path']['error'] == 0) {
                $res = $this->handleHybridUpload($_FILES['bpkb_path'], 'vehicles/bpkb');
                if (!$res['status']) {
                    echo json_encode(['success' => false, 'message' => 'BPKB: ' . $res['message']]);
                    exit;
                }
                $bpkbPath = $res['path'];
            }

            // 2. Upload STNK (Hybrid: PDF/Image)
            $stnkPath = null;
            if (isset($_FILES['stnk_path']) && $_FILES['stnk_path']['error'] == 0) {
                $res = $this->handleHybridUpload($_FILES['stnk_path'], 'vehicles/stnk');
                if (!$res['status']) {
                    echo json_encode(['success' => false, 'message' => 'STNK: ' . $res['message']]);
                    exit;
                }
                $stnkPath = $res['path'];
            }

            // 3. Upload Photos (di kompres)
            $photosJson = $this->handlePhotos('photos');

            if ($mode == 'add') {
                // Cek Duplikat Kode
                if ($this->model->checkAssetCode($data['asset_code'])) {
                    echo json_encode(['success' => false, 'message' => 'Asset Code already exists']);
                    exit;
                }

                $data['bpkb_path'] = $bpkbPath;
                $data['stnk_path'] = $stnkPath;
                $data['photos'] = $photosJson;

                if ($this->model->insert($data)) {
                    echo json_encode(['success' => true, 'message' => 'Vehicle added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add vehicle']);
                }
            } else {
                // MODE EDIT
                $id = intval($_POST['id']);

                // Cek Duplikat Kode (Exclude ID sendiri)
                if ($this->model->checkAssetCode($data['asset_code'], $id)) {
                    echo json_encode(['success' => false, 'message' => 'Asset Code already used']);
                    exit;
                }

                $oldData = $this->model->getById($id);

                // Logic ganti BPKB
                if ($bpkbPath) {
                    $this->deleteFile($oldData['bpkb_path']); // Hapus file lama
                    $data['bpkb_path'] = $bpkbPath;
                } else {
                    $data['bpkb_path'] = $oldData['bpkb_path']; // Pakai file lama
                }

                // Logic ganti STNK
                if ($stnkPath) {
                    $this->deleteFile($oldData['stnk_path']);
                    $data['stnk_path'] = $stnkPath;
                } else {
                    $data['stnk_path'] = $oldData['stnk_path'];
                }

                // Logic ganti Photo
                if ($photosJson) {
                    // Hapus foto-foto lama
                    if (!empty($oldData['photos'])) {
                        $oldPics = json_decode($oldData['photos'], true);
                        if (is_array($oldPics)) {
                            foreach ($oldPics as $p) $this->deleteFile($p);
                        }
                    }
                    $data['photos'] = $photosJson;
                } else {
                    $data['photos'] = $oldData['photos'];
                }

                if ($this->model->update($id, $data)) {
                    echo json_encode(['success' => true, 'message' => 'Vehicle updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // HELPERS UPLOAD (KOMPRESI)
    // Handle Hybrid (Bisa Gambar, Bisa PDF) -> STNK & BPKB
    private function handleHybridUpload($file, $subFolder)
    {
        $mime = mime_content_type($file['tmp_name']);

        // Jika Gambar -> Kompres
        if (strpos($mime, 'image') !== false) {
            $path = $this->compressImage($file['tmp_name'], $file['name'], $subFolder);
            return $path ? ['status' => true, 'path' => $path] : ['status' => false, 'message' => 'Failed to compress image'];
        }
        // Jika PDF -> Cek Size (Max 2MB)
        elseif ($mime == 'application/pdf') {
            if ($file['size'] > 2 * 1024 * 1024) {
                return ['status' => false, 'message' => 'File too large (Max 2MB)'];
            }
            return $this->uploadRawFile($file, $subFolder);
        }

        return ['status' => false, 'message' => 'Format not supported (JPG/PNG/PDF only)'];
    }

    private function handlePhotos($key)
    {
        if (isset($_FILES[$key]) && !empty($_FILES[$key]['name'][0])) {
            $uploaded = [];
            foreach ($_FILES[$key]['name'] as $i => $name) {
                if ($_FILES[$key]['error'][$i] == 0) {
                    $tmp = $_FILES[$key]['tmp_name'][$i];
                    // Validasi Mime
                    $mime = mime_content_type($tmp);
                    if (strpos($mime, 'image') !== false) {
                        // Kompres foto ke folder vehicles/photos
                        $path = $this->compressImage($tmp, $name, 'vehicles/photos');
                        if ($path) $uploaded[] = $path;
                    }
                }
            }
            return !empty($uploaded) ? json_encode($uploaded) : null;
        }
        return null;
    }

    // Fungsi Upload Biasa (Untuk PDF)
    private function uploadRawFile($file, $subFolder)
    {
        $dir = __DIR__ . '/../../public/uploads/' . $subFolder . '/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new = uniqid('doc_') . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $dir . $new)) {
            return [
                'status' => true,
                'path' => 'public/uploads/' . $subFolder . '/' . $new
            ];
        }
        return ['status' => false, 'message' => 'Upload failed'];
    }

    // Fungsi Kompresi Gambar (Resize 800px & kualitas 70%)
    private function compressImage($source, $originalName, $subFolder)
    {
        $uploadDir = __DIR__ . '/../../public/uploads/' . $subFolder . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $imageInfo = getimagesize($source);
        if (!$imageInfo) return null;

        $mime = $imageInfo['mime'];
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = uniqid('img_') . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        $dbPath = 'public/uploads/' . $subFolder . '/' . $fileName;

        $maxWidth = 800; // Resize ke lebar 800px
        $quality = 70;   // Kualitas 70%

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            default:
                return null;
        }

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
                $trans = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
                imagefilledrectangle($image_p, 0, 0, $newWidth, $newHeight, $trans);
            }

            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $image_p;
        }

        if ($mime == 'image/jpeg') imagejpeg($image, $targetPath, $quality);
        elseif ($mime == 'image/png') imagepng($image, $targetPath, 6);

        imagedestroy($image);
        return $dbPath;
    }

    private function deleteFile($path)
    {
        if ($path && file_exists(__DIR__ . '/../../' . $path)) {
            unlink(__DIR__ . '/../../' . $path);
        }
    }

    private function collectPostData()
    {
        $fields = [
            'asset_code',
            'vehicle_type',
            'owner',
            'user_id',
            'brand',
            'license_plate',
            'year',
            'purchase_date',
            'equipment_details',
            'location_id',
            'category_id',
            'bpkb_number',
            'stnk_number',
            'chassis_number',
            'engine_number',
            'condition',
            'maintenance_frequency'
        ];
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = !empty($_POST[$f]) ? trim($_POST[$f]) : null;
        }
        $data['user_id'] = !empty($data['user_id']) ? intval($data['user_id']) : null;
        $data['location_id'] = !empty($data['location_id']) ? intval($data['location_id']) : null;
        $data['category_id'] = !empty($data['category_id']) ? intval($data['category_id']) : null;
        $data['year'] = !empty($data['year']) ? intval($data['year']) : null;

        return $data;
    }

    // CSV IMPORT / EXPORT
    public function downloadTemplate()
    {
        $filename = 'template_vehicle.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        $headers = [
            'Asset Code*',
            'Vehicle Type',
            'Owner',
            'User ID',
            'Brand/Type',
            'License Plate',
            'Year',
            'Purchase Date (YYYY-MM-DD)',
            'Equipment Details',
            'ID Location',
            'ID Category',
            'BPKB Number',
            'STNK Number',
            'Chassis Number',
            'Engine Number',
            'Condition',
            'Maintenance Freq'
        ];
        fputcsv($output, $headers);
        fclose($output);
        exit;
    }

    public function import()
    {
        header('Content-Type: application/json'); 

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file_csv'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid file']);
            exit;
        }

        $file = $_FILES['file_csv']['tmp_name'];
        $handle = fopen($file, "r");
        $bom = fread($handle, 3);
        if ($bom != "\xEF\xBB\xBF") rewind($handle);
        fgetcsv($handle);

        $success = 0;
        $fail = 0;
        $errors = []; // Tambahan array error agar mempermudah debug
        $row = 1;

        // Alat Penerjemah Tanggal 
        $formatDate = function ($val) {
            $val = trim($val ?? '');
            if (empty($val)) return null;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;
            $val = str_replace('/', '-', $val); // Ubah 31/01/2024 jadi 31-01-2024
            $time = strtotime($val);
            if ($time !== false) return date('Y-m-d', $time);
            return null;
        };

        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                $code = trim($data[0] ?? '');

                if (empty($code)) {
                    $fail++;
                    $errors[] = "Row $row: Asset Code is empty.";
                    continue;
                }

                if ($this->model->checkAssetCode($code)) {
                    $fail++;
                    $errors[] = "Row $row: Code $code already exists.";
                    continue;
                }

                $insertData = [
                    'asset_code' => $code,
                    'vehicle_type' => trim($data[1] ?? ''),
                    'owner' => trim($data[2] ?? ''),
                    'user_id' => intval($data[3] ?? 0) ?: null,
                    'brand' => trim($data[4] ?? ''),
                    'license_plate' => trim($data[5] ?? ''),
                    'year' => intval($data[6] ?? 0) ?: null,
                    'purchase_date' => $formatDate($data[7] ?? null),
                    'equipment_details' => trim($data[8] ?? ''),
                    'location_id' => intval($data[9] ?? 0) ?: null,
                    'category_id' => intval($data[10] ?? 0) ?: null,
                    'bpkb_number' => trim($data[11] ?? ''),
                    'stnk_number' => trim($data[12] ?? ''),
                    'chassis_number' => trim($data[13] ?? ''),
                    'engine_number' => trim($data[14] ?? ''),
                    'condition' => trim($data[15] ?? ''),
                    'maintenance_frequency' => trim($data[16] ?? ''),
                    'photos' => null,
                    'bpkb_path' => null,
                    'stnk_path' => null
                ];

                if ($this->model->insert($insertData)) {
                    $success++;
                } else {
                    $fail++;
                    $errors[] = "Row $row: Failed to insert to Database.";
                }
            }
            fclose($handle);

            echo json_encode([
                'success' => true,
                'message' => "Import Completed. Success: $success, Failed: $fail",
                'debug_errors' => $errors
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
