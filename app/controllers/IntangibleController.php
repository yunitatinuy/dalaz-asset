<?php

class IntangibleController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = $this->model('Intangible');
    }

    public function index()
    {
        $this->requireAdmin();

        $locationModel = $this->model('Location');
        $categoryModel = $this->model('Category');

        $data = [
            'title' => 'Intangible Assets',
            'pageCSS' => 'asset',
            'pageJS' => 'intangible',
            'locations' => $locationModel->getAll(),
            'categories' => $categoryModel->getAll()
        ];
        $this->view('intangible/index', $data);
    }

    public function downloadTemplate()
    {
        $filename = 'template_intangible.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        $headers = [
            'Asset Code*',
            'Document Name*',
            'ID Location (Number)',
            'ID Category (Number)',
            'Cert Number',
            'Issuing Agency',
            'Status (active/expired)',
            'Issue Date (YYYY-MM-DD)',
            'Effective Date (YYYY-MM-DD)',
            'Expiration Date (YYYY-MM-DD)'
        ];

        fputcsv($output, $headers);
        fclose($output);
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

        $formatDate = function ($val) {
            $val = trim($val ?? '');
            if (empty($val)) return null;

            // Jika formatnya sudah benar YYYY-MM-DD, di biarkan saja
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;

            // Jika Excel pakai format DD/MM/YYYY, ubah garis miring jadi strip agar PHP paham
            $val = str_replace('/', '-', $val);

            $time = strtotime($val);
            if ($time !== false) {
                return date('Y-m-d', $time);
            }
            return null;
        };

        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                $insertData = [
                    'asset_code'         => trim($data[0] ?? ''),
                    'document_name'      => trim($data[1] ?? ''),
                    'location_id'        => !empty($data[2]) ? intval($data[2]) : null,
                    'category_id'        => !empty($data[3]) ? intval($data[3]) : null,
                    'certificate_number' => trim($data[4] ?? ''),
                    'issuing_agency'     => trim($data[5] ?? ''),
                    'document_status'    => strtolower(trim($data[6] ?? 'active')),
                    'issue_date'         => $formatDate($data[7] ?? null),
                    'effective_date'     => $formatDate($data[8] ?? null),
                    'expiration_date'    => $formatDate($data[9] ?? null),
                ];

                if (empty($insertData['asset_code']) || empty($insertData['document_name'])) {
                    $fail++;
                    $errors[] = "Row $row: Code/Name is empty.";
                    continue;
                }

                // Cek duplikat
                if (!$this->model->checkAssetCode($insertData['asset_code'])) {
                    if ($this->model->insert($insertData)) {
                        $success++;
                    } else {
                        $fail++;
                        $errors[] = "Row $row: DB Insert Error";
                    }
                } else {
                    $fail++;
                    $errors[] = "Row $row: Code {$insertData['asset_code']} exists.";
                }
            }
            fclose($handle);

            $this->jsonResponse([
                'success' => true,
                'message' => "Import Complete. Success: $success. Fail: $fail.",
                'debug_errors' => $errors
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getAll()
    {
        $data = $this->model->getAll();
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function getById()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $this->model->getById($id);
        if ($data) $this->jsonResponse(['success' => true, 'data' => $data]);
        else $this->jsonResponse(['success' => false, 'message' => 'Data not found']);
    }

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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid Request']);
            exit;
        }

        try {
            $data = $this->collectPostData();

            // Validasi Wajib
            if (empty($data['asset_code']) || empty($data['document_name'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Asset Code & Name required']);
                exit;
            }

            // Validasi File PDF (Max 2MB)
            $docPath = null;
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
                $res = $this->processPdfUpload($_FILES['document_file']);
                if (!$res['status']) {
                    $this->jsonResponse(['success' => false, 'message' => $res['message']]);
                    exit;
                }
                $docPath = $res['path'];
            }

            if ($mode == 'add') {
                // Cek Duplikat
                if ($this->model->checkAssetCode($data['asset_code'])) {
                    $this->jsonResponse(['success' => false, 'message' => 'Asset Code already exists']);
                    exit;
                }

                $data['document_path'] = $docPath;

                if ($this->model->insert($data)) {
                    $this->jsonResponse(['success' => true, 'message' => 'Data added successfully']);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to add data']);
                }
            } else {
                // MODE EDIT
                $id = intval($_POST['id'] ?? 0);

                // Cek Duplikat (Exclude ID ini)
                if ($this->model->checkAssetCode($data['asset_code'], $id)) {
                    $this->jsonResponse(['success' => false, 'message' => 'Asset Code already used']);
                    exit;
                }

                $oldData = $this->model->getById($id);

                // Logic Replace File
                if ($docPath) {
                    $this->deleteFile($oldData['document_path']); // Hapus file lama
                    $data['document_path'] = $docPath;
                } else {
                    $data['document_path'] = $oldData['document_path']; // Pakai file lama
                }

                if ($this->model->update($id, $data)) {
                    $this->jsonResponse(['success' => true, 'message' => 'Data updated successfully']);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to update']);
                }
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function processPdfUpload($file)
    {
        $targetDir = "public/uploads/intangibles/";
        // Buat folder jika belum ada
        $absDir = __DIR__ . '/../../' . $targetDir;
        if (!is_dir($absDir)) mkdir($absDir, 0777, true);

        // Validasi Tipe File
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileType != "pdf") {
            return ['status' => false, 'message' => 'Only PDF files are allowed.'];
        }

        // Validasi Ukuran (Max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['status' => false, 'message' => 'File too large. Max 2MB.'];
        }

        // Upload
        $newFileName = uniqid('doc_') . '.pdf';
        $targetFilePath = $absDir . $newFileName;
        $dbPath = $targetDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            return ['status' => true, 'path' => $dbPath];
        }

        return ['status' => false, 'message' => 'Upload failed. Check folder permissions.'];
    }

    public function delete()
    {
        $id = intval($_POST['id'] ?? 0);
        $data = $this->model->getById($id);

        if ($data) {
            $this->deleteFile($data['document_path']);
            if ($this->model->delete($id)) {
                $this->jsonResponse(['success' => true, 'message' => 'Data deleted successfully']);
                exit; // Pastikan berhenti setelah sukses
            }
        }

        $this->jsonResponse(['success' => false, 'message' => 'Delete failed']);
    }

    private function collectPostData()
    {
        $fields = ['asset_code', 'document_name', 'certificate_number', 'issuing_agency', 'issue_date', 'effective_date', 'expiration_date', 'document_status', 'location_id', 'category_id'];
        $data = [];
        foreach ($fields as $f) $data[$f] = !empty($_POST[$f]) ? trim($_POST[$f]) : null;
        $data['location_id'] = !empty($data['location_id']) ? intval($data['location_id']) : null;
        $data['category_id'] = !empty($data['category_id']) ? intval($data['category_id']) : null;
        return $data;
    }

    private function deleteFile($path)
    {
        if ($path && file_exists(__DIR__ . '/../../' . $path)) @unlink(__DIR__ . '/../../' . $path);
    }
}
