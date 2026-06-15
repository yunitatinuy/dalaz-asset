<?php

class BorrowedController extends Controller
{
    private $borrowedModel;
    private $equipmentModel;
    private $userModel;

    public function __construct()
    {
        $this->borrowedModel = $this->model('Borrowed');
        $this->equipmentModel = $this->model('Equipment');
        $this->userModel = $this->model('User');
    }

    public function index()
    {
        $data = [
            'title' => 'Borrow Equipment - Dalaz Asset'
        ];

        $this->view('borrowed/index', $data);
    }

    public function admin()
    {
        $this->requireAdmin();
        $borrowedItems = $this->borrowedModel->getAllWithDetails();
        $data = [
            'title' => 'Borrowing Management',
            'borrowed_items' => $borrowedItems ?? []
        ];

        $this->view('borrowed/admin', $data);
    }

    public function scanUser()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Request not valid'], 400);
        }

        $qrCode = $_POST['qr_code'] ?? '';

        if (empty($qrCode)) {
            $this->jsonResponse(['success' => false, 'message' => 'QR Code not valid'], 400);
        }

        // (Kode pembersih URL sudah dihapus karena sudah di-handle oleh JS)

        $user = $this->userModel->getUserByQRCode($qrCode);

        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'User not found: ' . htmlspecialchars($qrCode)], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'employee_no' => $user['employee_no'],
                'position' => $user['position'],
                'profile_picture' => $user['profile_picture']
            ]
        ]);
    }

    public function scanEquipment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Request not valid'], 400);
        }

        $qrCode = $_POST['qr_code'] ?? '';
        $userId = $_POST['user_id'] ?? '';

        if (empty($qrCode) || empty($userId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Data not complete'], 400);
        }

        $equipment = null;

        if (method_exists($this->equipmentModel, 'getByAssetNumber')) {
            $equipment = $this->equipmentModel->getByAssetNumber($qrCode);
        }

        if (!$equipment) {
            $equipment = $this->equipmentModel->getByQRCode($qrCode);
        }

        if (!$equipment) {
            $this->jsonResponse(['success' => false, 'message' => 'Equipment not found: ' . htmlspecialchars($qrCode)], 404);
        }

        // Cek stock
        if ($equipment['quantity'] < 1) {
            $this->jsonResponse(['success' => false, 'message' => 'Equipment is currently out of stock'], 400);
        }

        $this->jsonResponse([
            'success' => true,
            'equipment' => [
                'id' => $equipment['id'],
                'equipment_name' => $equipment['equipment_name'],
                'asset_number' => $equipment['asset_number'],
                'quantity' => $equipment['quantity'],
                'pictures' => $equipment['pictures']
            ]
        ]);
    }

    public function process()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Request not valid'], 400);
        }

        $userId = $_POST['user_id'] ?? '';
        $equipmentId = $_POST['equipment_id'] ?? '';
        $assetNumber = $_POST['asset_number'] ?? '';
        $noJd = $_POST['no_jd'] ?? '';
        $client = $_POST['client'] ?? '';
        $location = $_POST['location'] ?? '';
        $workingDays = isset($_POST['working_days']) ? intval($_POST['working_days']) : 1;
        $quantity = $_POST['quantity'] ?? 1;

        if (empty($userId) || empty($equipmentId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Data not complete'], 400);
        }

        $borrowData = [
            'user_id' => $userId,
            'equipment_id' => $equipmentId,
            'asset_number' => $assetNumber,
            'no_jd' => $noJd,
            'client' => $client,
            'location' => $location,
            'working_days' => $workingDays,
            'quantity' => $quantity,
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'description' => 'good',
            'status' => 'borrowed'
        ];

        $result = $this->borrowedModel->insert($borrowData);

        if ($result) {
            $this->equipmentModel->decreaseQuantity($equipmentId, $quantity);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Borrowing successfully recorded',
                'borrow_id' => $result
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to record borrowing'], 500);
        }
    }

    public function delete($id)
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Request not valid'], 400);
        }

        // Ambil data peminjaman
        $borrowedItem = $this->borrowedModel->getById($id);

        if (!$borrowedItem) {
            $this->setFlash('error', 'Borrowing data not found');
            $this->redirect('borrowed/admin');
            return;
        }

        if ($borrowedItem['status'] === 'borrowed') {
            $this->setFlash('error', 'Access Denied: Cannot delete this data because the user has not returned the item yet.');
            $this->redirect('borrowed/admin');
            return;
        }

        // Jika statusnya sudah 'returned', baru bisa dihapus
        $result = $this->borrowedModel->delete($id);

        if ($result) {
            $this->setFlash('success', 'Borrowing history successfully deleted.');
        } else {
            $this->setFlash('error', 'Failed to delete borrowing data');
        }

        $this->redirect('borrowed/admin');
    }
}
