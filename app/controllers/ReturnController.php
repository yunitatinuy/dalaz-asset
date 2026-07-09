<?php

class ReturnController extends Controller
{
    private $returnModel;
    private $borrowedModel;
    private $equipmentModel;
    private $userModel;
    private $complaintModel;

    public function __construct()
    {
        $this->returnModel = $this->model('ReturnModel');
        $this->borrowedModel = $this->model('Borrowed');
        $this->equipmentModel = $this->model('Equipment');
        $this->userModel = $this->model('User');
        $this->complaintModel = $this->model('Complaint');
    }

    public function index()
    {
        $data = [
            'title' => 'Return Equipment - Dalaz Asset'
        ];

        $this->view('return/index', $data);
    }

    public function admin()
    {
        $this->requireAdmin();
        $returnItems = $this->returnModel->getAllWithDetails();
        $data = [
            'title' => 'Returned Equipment',
            'return_items' => $returnItems
        ];
        $this->view('return/admin', $data);
    }

    public function scanUser()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $qrCode = $_POST['qr_code'] ?? '';
        if (strpos($qrCode, 'code=') !== false) {
            $parts = explode('code=', $qrCode);
            $qrCode = end($parts);
            if (strpos($qrCode, '&') !== false) $qrCode = explode('&', $qrCode)[0];
            $qrCode = urldecode($qrCode);
        }

        $user = $this->userModel->getUserByQRCode($qrCode);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $borrowedItems = $this->borrowedModel->getActiveBorrowedByUser($user['id']);

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'employee_no' => $user['employee_no']
            ],
            'borrowed_items' => $borrowedItems
        ]);
        exit;
    }

    public function scanEquipment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->jsonResponse(['success' => false], 400);

        $qrCode = $_POST['qr_code'] ?? '';
        $userId = $_POST['user_id'] ?? '';

        if (empty($qrCode) || empty($userId)) $this->jsonResponse(['success' => false], 400);

        // Bersihkan URL
        if (strpos($qrCode, 'code=') !== false) {
            $parts = explode('code=', $qrCode);
            $qrCode = end($parts);
            if (strpos($qrCode, '&') !== false) $qrCode = explode('&', $qrCode)[0];
            $qrCode = urldecode($qrCode);
        }

        $equipment = $this->equipmentModel->getByQRCode($qrCode);
        if (!$equipment && method_exists($this->equipmentModel, 'getByAssetNumber')) {
            $equipment = $this->equipmentModel->getByAssetNumber($qrCode);
        }

        if (!$equipment) $this->jsonResponse(['success' => false, 'message' => 'Equipment not found'], 404);

        $borrowed = $this->borrowedModel->getActiveBorrowedByUserAndEquipment($userId, $equipment['id']);

        if (!$borrowed) $this->jsonResponse(['success' => false, 'message' => 'User has not borrowed this item'], 404);
        $this->jsonResponse([
            'success' => true,
            'borrowed' => $borrowed,
            'equipment' => ['id' => $equipment['id'], 'asset_number' => $equipment['asset_number']]
        ]);
    }

    public function process()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            // Ambil data POST
            $userId = $_POST['user_id'] ?? '';
            $borrowedId = $_POST['borrowed_id'] ?? '';
            $equipmentId = $_POST['equipment_id'] ?? '';
            $assetNumber = $_POST['asset_number'] ?? '';
            $quantity = $_POST['quantity'] ?? 1;
            $description = $_POST['description'] ?? 'good';
            $defectCause = $_POST['defect_cause'] ?? '';

            // 1. INSERT RETURN
            $returnData = [
                'user_id' => $userId,
                'borrowed_id' => $borrowedId,
                'equipment_id' => $equipmentId,
                'asset_number' => $assetNumber,
                'quantity' => $quantity,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'description' => $description
            ];

            $returnId = $this->returnModel->insert($returnData);

            if ($returnId) {
                // Update status borrowed -> returned
                $this->borrowedModel->updateStatus($borrowedId, 'returned');

                // Update Stock Equipment (Jika bukan lost, karena kalau lost barang fisik hilang)
                if ($description !== 'lost') {
                    $this->equipmentModel->increaseQuantity($equipmentId, $quantity);
                }

                // Hanya update jika statusnya 'good' atau 'damaged' agar sesuai dengan ENUM database
                if ($description === 'damaged' || $description === 'good') {
                    $this->equipmentModel->updateCondition($equipmentId, $description);
                }

                // JIKA RUSAK ATAU HILANG, BUAT TIKET COMPLAINT OTOMATIS
                if (in_array($description, ['damaged', 'lost'])) {

                    $uploadedPhotos = [];

                    // Perbaikan 2: Wajib Foto Jika Damaged
                    if ($description === 'damaged') {
                        if (!isset($_FILES['defect_photos']) || empty($_FILES['defect_photos']['name'][0])) {
                            throw new Exception("Evidence photos are strictly required for damaged items");
                        }
                    }

                    // Proses upload foto (Jika ada foto yang dikirim)
                    if (isset($_FILES['defect_photos']) && !empty($_FILES['defect_photos']['name'][0])) {
                        // Perbaikan 3: Path folder disesuaikan
                        $uploadDir = 'public/uploads/complaints/';
                        if (!is_dir(__DIR__ . '/../../' . $uploadDir)) {
                            mkdir(__DIR__ . '/../../' . $uploadDir, 0777, true);
                        }

                        $fileCount = count($_FILES['defect_photos']['name']);
                        for ($i = 0; $i < $fileCount; $i++) {
                            if ($_FILES['defect_photos']['error'][$i] === UPLOAD_ERR_OK) {
                                $tmpName = $_FILES['defect_photos']['tmp_name'][$i];
                                // Buat nama file unik
                                $fileName = time() . '_' . uniqid() . '.jpg';
                                $destination = __DIR__ . '/../../' . $uploadDir . $fileName;

                                if (move_uploaded_file($tmpName, $destination)) {
                                    // Simpan ke database beserta tulisan 'public/...'
                                    $uploadedPhotos[] = $uploadDir . $fileName;
                                }
                            }
                        }
                    }

                    // Ubah array nama file menjadi JSON string agar mudah disimpan ke DB
                    $photosJson = !empty($uploadedPhotos) ? json_encode($uploadedPhotos) : null;

                    $complaintData = [
                        'return_id' => $returnId,
                        'user_id' => $userId,
                        'equipment_id' => $equipmentId,
                        'defect_cause' => $defectCause ?: 'Submitted upon return',
                        'photos' => $photosJson
                    ];

                    $this->complaintModel->createTicketFromReturn($complaintData);
                }

                echo json_encode(['success' => true, 'message' => 'Return process completed successfully']);
            } else {
                throw new Exception("Failed to save return data");
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
