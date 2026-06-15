<?php

class LandbuildingController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->requireAdmin();
        $this->model = $this->model('Landbuilding');
    }

    public function index()
    {
        $userModel = $this->model('User');
        $locationModel = $this->model('Location');
        $categoryModel = $this->model('Category');

        $data = [
            'title' => 'Land & Building',
            'pageCSS' => 'asset',
            'pageJS' => 'land_building',
            'users' => $userModel->getAll(),
            'locations' => $locationModel->getAll(),
            'categories' => $categoryModel->getAll()
        ];
        $this->view('land_building/index', $data);
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

    public function downloadTemplate()
    {
        $filename = 'template_landbuilding.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM untuk Excel

        fputcsv($out, [
            'Asset Code*',
            'Asset Name*',
            'Responsible Person',
            'ID User (Number)',
            'ID Location (Number)',
            'ID Category (Number)',
            'Certificate Number',
            'Certificate Date (YYYY-MM-DD)',
            'Address',
            'Surface Area',
            'Intended Use',
            'Condition',
            'Usage Status (used/not_used)'
        ]);

        fclose($out);
        exit;
    }

    public function import()
    {
        header('Content-Type: application/json');
        if (!isset($_FILES['file_csv'])) {
            echo json_encode(['success' => false, 'message' => 'File not found']);
            exit;
        }

        $file = $_FILES['file_csv']['tmp_name'];
        $handle = fopen($file, "r");

        $bom = fread($handle, 3);
        if ($bom != "\xEF\xBB\xBF") rewind($handle);
        fgetcsv($handle); // Skip Header

        $success = 0;
        $fail = 0;
        $errors = [];
        $row = 1;

        // Alat Penerjemah Tanggal
        $formatDate = function ($val) {
            $val = trim($val ?? '');
            if (empty($val)) return null;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;
            $val = str_replace('/', '-', $val);
            $time = strtotime($val);
            if ($time !== false) return date('Y-m-d', $time);
            return null;
        };

        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                $insertData = [
                    'asset_code'         => trim($data[0] ?? ''),
                    'asset_name'         => trim($data[1] ?? ''),
                    'responsible_person' => trim($data[2] ?? ''),
                    'user_id'            => !empty($data[3]) ? intval($data[3]) : null,
                    'location_id'        => !empty($data[4]) ? intval($data[4]) : null,
                    'category_id'        => !empty($data[5]) ? intval($data[5]) : null,
                    'certificate_number' => trim($data[6] ?? ''),
                    'certificate_date'   => $formatDate($data[7] ?? null),
                    'address'            => trim($data[8] ?? ''),
                    'surface_area'       => trim($data[9] ?? ''),
                    'intended_use'       => trim($data[10] ?? ''),
                    'condition'          => trim($data[11] ?? ''),
                    'usage_status'       => strtolower(trim($data[12] ?? 'used')),

                    'site_plan_path'     => null,
                    'document_path'      => null,
                    'photos'             => null
                ];

                if (empty($insertData['asset_code']) || empty($insertData['asset_name'])) {
                    $fail++;
                    $errors[] = "Row $row: Asset Code/Name is empty.";
                    continue;
                }

                if (!$this->model->checkAssetCode($insertData['asset_code'])) {
                    if ($this->model->insert($insertData)) $success++;
                    else {
                        $fail++;
                        $errors[] = "Row $row: DB Insert Error";
                    }
                } else {
                    $fail++;
                    $errors[] = "Row $row: Code {$insertData['asset_code']} already exists.";
                }
            }
            fclose($handle);

            echo json_encode([
                'success' => true,
                'message' => "Import Complete. Success: $success. Fail: $fail.",
                'debug_errors' => $errors
            ]);
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
                $this->deleteFile($item['site_plan_path']);
                $this->deleteFile($item['document_path']);

                if (!empty($item['photos'])) {
                    $photos = json_decode($item['photos'], true);
                    if (is_array($photos)) {
                        foreach ($photos as $p) $this->deleteFile($p);
                    }
                }
            }

            if ($this->model->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Data deleted successfully']);
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
            $data = $_POST;
            $data['location_id'] = !empty($data['location_id']) ? intval($data['location_id']) : null;
            $data['category_id'] = !empty($data['category_id']) ? intval($data['category_id']) : null;
            $data['user_id']     = !empty($data['user_id']) ? intval($data['user_id']) : null;

            // Validasi Anti-Duplikat untuk Asset Code
            $excludeId = ($mode == 'edit') ? intval($data['id'] ?? 0) : null;
            if ($this->model->checkAssetCode($data['asset_code'], $excludeId)) {
                echo json_encode(['success' => false, 'message' => 'Asset Code already exists. Please use a unique code.']);
                exit;
            }

            // 1. Upload Site Plan (PDF/Image)
            $sitePlan = null;
            if (isset($_FILES['site_plan_path']) && $_FILES['site_plan_path']['error'] == 0) {
                $res = $this->handleSitePlan($_FILES['site_plan_path']);
                if (!$res['status']) {
                    echo json_encode(['success' => false, 'message' => 'Site Plan: ' . $res['message']]);
                    exit;
                }
                $sitePlan = $res['path'];
            }

            // 2. Upload Dokumen (PDF Only)
            $docPath = null;
            if (isset($_FILES['document_path']) && $_FILES['document_path']['error'] == 0) {
                $res = $this->handleDocument($_FILES['document_path']);
                if (!$res['status']) {
                    echo json_encode(['success' => false, 'message' => 'Document: ' . $res['message']]);
                    exit;
                }
                $docPath = $res['path'];
            }

            // 3. Upload Poto (Images Only)
            $photosJson = $this->handlePhotos('photos');

            if ($mode == 'add') {
                $data['site_plan_path'] = $sitePlan;
                $data['document_path'] = $docPath;
                $data['photos'] = $photosJson;

                if ($this->model->insert($data)) {
                    echo json_encode(['success' => true, 'message' => 'Added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add']);
                }
            } else {
                // MODE EDIT
                $id = intval($data['id'] ?? 0);
                if (!$id) {
                    echo json_encode(['success' => false, 'message' => 'ID missing for update']);
                    exit;
                }

                $oldData = $this->model->getById($id);

                // Logic ganti Site Plan
                if ($sitePlan) {
                    $this->deleteFile($oldData['site_plan_path']);
                    $data['site_plan_path'] = $sitePlan;
                } else {
                    $data['site_plan_path'] = $oldData['site_plan_path'];
                }

                // Logic ganti Document
                if ($docPath) {
                    $this->deleteFile($oldData['document_path']);
                    $data['document_path'] = $docPath;
                } else {
                    $data['document_path'] = $oldData['document_path'];
                }

                // Logic ganti Photo
                if ($photosJson) {
                    // Hapus foto lama
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
                    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // HELPERS UPLOAD

    // 1. Handle Site Plan (Bisa Gambar, Bisa PDF)
    private function handleSitePlan($file)
    {
        $mime = mime_content_type($file['tmp_name']);

        // Jika Gambar -> Kompres
        if (strpos($mime, 'image') !== false) {
            $path = $this->compressImage($file['tmp_name'], $file['name'], 'land_building');
            return $path ? ['status' => true, 'path' => $path] : ['status' => false, 'message' => 'Failed to compress image'];
        }
        // Jika PDF -> Cek Size
        elseif ($mime == 'application/pdf') {
            if ($file['size'] > 2 * 1024 * 1024) { // 2MB
                return ['status' => false, 'message' => 'PDF too large (Max 2MB)'];
            }
            return $this->uploadRawFile($file, 'land_building');
        }

        return ['status' => false, 'message' => 'Format not supported (JPG/PNG/PDF only)'];
    }

    // 2. Handle Document (PDF Only)
    private function handleDocument($file)
    {
        $mime = mime_content_type($file['tmp_name']);
        if ($mime != 'application/pdf') {
            return ['status' => false, 'message' => 'Only PDF allowed'];
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['status' => false, 'message' => 'PDF too large (Max 2MB)'];
        }
        return $this->uploadRawFile($file, 'documents');
    }

    // 3. Handle Photos (Multiple, Image Only, Compressed)
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
                        $path = $this->compressImage($tmp, $name, 'land_building');
                        if ($path) $uploaded[] = $path;
                    }
                }
            }
            return !empty($uploaded) ? json_encode($uploaded) : null;
        }
        return null;
    }

    private function uploadRawFile($file, $folder)
    {
        $dir = __DIR__ . '/../../public/uploads/' . $folder . '/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new = uniqid('lb_') . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $dir . $new)) {
            return ['status' => true, 'path' => 'public/uploads/' . $folder . '/' . $new];
        }
        return ['status' => false, 'message' => 'Upload failed'];
    }

    // Kompresi Gambar
    private function compressImage($source, $originalName, $folder)
    {
        $uploadDir = __DIR__ . '/../../public/uploads/' . $folder . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $imageInfo = getimagesize($source);
        if (!$imageInfo) return null;

        $mime = $imageInfo['mime'];
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = uniqid('lb_img_') . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        $dbPath = 'public/uploads/' . $folder . '/' . $fileName;

        $maxWidth = 800;
        $quality = 70;

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
}
