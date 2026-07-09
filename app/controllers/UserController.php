<?php

class UserController extends Controller
{
    private $userModel;
    private $borrowedModel;

    public function __construct()
    {
        $this->requireAdmin();
        $this->userModel = $this->model('User');
        $this->borrowedModel = $this->model('Borrowed');
    }

    public function index()
    {
        $data = [
            'title' => 'User Data - ' . APP_NAME,
            'pageJS' => 'user',
            'pageCSS' => 'asset',
            'activeMenu' => 'user'
        ];
        $this->view('user/index', $data);
    }

    public function getAll()
    {
        header('Content-Type: application/json');
        try {
            $users = $this->userModel->getAll();
            echo json_encode(['success' => true, 'data' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getById()
    {
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        try {
            $user = $this->userModel->getById($id);
            if ($user) {
                unset($user['password']); // Jangan kirim password
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function add()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            $role = $_POST['role'] ?? 'user';

            // VALIDASI: Admin WAJIB isi username & password
            if ($role === 'admin') {
                if (empty($_POST['username']) || empty($_POST['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Admin must have a username and password']);
                    exit;
                }

                if (empty($_POST['email'])) {
                    echo json_encode(['success' => false, 'message' => 'Admin must have an Email for notifications']);
                    exit;
                }

                // Cek username sudah ada
                if ($this->userModel->usernameExists($_POST['username'])) {
                    echo json_encode(['success' => false, 'message' => 'Username is already taken']);
                    exit;
                }
            }

            // Validasi full_name & employee_no wajib untuk semua
            if (empty($_POST['full_name']) || empty($_POST['employee_no'])) {
                echo json_encode(['success' => false, 'message' => 'Full name and employee number are required']);
                exit;
            }

            // Cek employee_no sudah ada
            if ($this->userModel->employeeNoExists($_POST['employee_no'])) {
                echo json_encode(['success' => false, 'message' => 'Employee number is already taken']);
                exit;
            }

            $data = [
                'full_name' => trim($_POST['full_name']),
                'position' => trim($_POST['position'] ?? ''),
                'employee_no' => trim($_POST['employee_no']),
                'role' => $role
            ];

            // Username & Password hanya untuk Admin (set NULL untuk user biasa)
            if ($role === 'admin') {
                $data['username'] = trim($_POST['username']);
                $data['email'] = trim($_POST['email']);
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            } else {
                $data['username'] = null;
                $data['email'] = null;
                $data['password'] = null;
            }

            // GENERATE QR CODE berdasarkan employee_no
            $qrData = $data['employee_no'];
            $qrBase64 = QRHelper::generate($qrData);

            if ($qrBase64) {
                $data['qr_code'] = $qrBase64;
            } else {
                error_log("Failed to generate QR Code for user: " . $qrData);
                $data['qr_code'] = null;
            }

            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . uniqid() . '.' . $ext;
                $uploadPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                    $data['profile_picture'] = '/uploads/profiles/' . $filename;
                }
            }

            if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
                $sigUploadDir = __DIR__ . '/../../public/uploads/signatures/';
                if (!is_dir($sigUploadDir)) mkdir($sigUploadDir, 0755, true);

                $ext = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
                $filename = 'sig_' . uniqid() . '.' . $ext;
                $uploadPath = $sigUploadDir . $filename;

                if (move_uploaded_file($_FILES['signature']['tmp_name'], $uploadPath)) {
                    $data['signature'] = '/uploads/signatures/' . $filename;
                }
            }

            $result = $this->userModel->insert($data);
            echo json_encode($result ? ['success' => true, 'message' => 'User successfully added!'] : ['success' => false, 'message' => 'Failed to add user']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function edit()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                exit;
            }

            $role = $_POST['role'] ?? 'user';

            // VALIDASI: Admin WAJIB punya username
            if ($role === 'admin' && empty($_POST['username'])) {
                echo json_encode(['success' => false, 'message' => 'Admin must have a username']);
                exit;
            }

            // Validasi Email saat Edit Admin
            if ($role === 'admin' && empty($_POST['email'])) {
                echo json_encode(['success' => false, 'message' => 'Admin must have an Email']);
                exit;
            }

            // Validasi full_name & employee_no wajib
            if (empty($_POST['full_name']) || empty($_POST['employee_no'])) {
                echo json_encode(['success' => false, 'message' => 'Full name and employee number are required']);
                exit;
            }

            // Cek username conflict (jika admin)
            if ($role === 'admin' && !empty($_POST['username'])) {
                $existingUser = $this->userModel->getUserByUsername($_POST['username']);
                if ($existingUser && $existingUser['id'] != $id) {
                    echo json_encode(['success' => false, 'message' => 'Username is already taken']);
                    exit;
                }
            }

            // Cek employee_no conflict
            $existingEmp = $this->userModel->getUserByEmployeeNo($_POST['employee_no']);
            if ($existingEmp && $existingEmp['id'] != $id) {
                echo json_encode(['success' => false, 'message' => 'Employee number is already taken']);
                exit;
            }

            $data = [
                'full_name' => trim($_POST['full_name']),
                'position' => trim($_POST['position'] ?? ''),
                'employee_no' => trim($_POST['employee_no']),
                'role' => $role
            ];

            // Username & Password hanya untuk Admin
            if ($role === 'admin') {
                if (!empty($_POST['username'])) {
                    $data['username'] = trim($_POST['username']);
                }
                if (!empty($_POST['email'])) {
                    $data['email'] = trim($_POST['email']);
                }
                // Update password hanya jika diisi
                if (!empty($_POST['password'])) {
                    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
            } else {
                // Jika role berubah dari admin ke user, set NULL
                $data['username'] = null;
                $data['email'] = null;
                $data['password'] = null;
            }

            // RE-GENERATE QR CODE jika employee_no berubah
            $oldUser = $this->userModel->getById($id);
            if ($data['employee_no'] !== $oldUser['employee_no']) {
                $qrBase64 = QRHelper::generate($data['employee_no']);
                if ($qrBase64) {
                    $data['qr_code'] = $qrBase64;
                }
            }

            // Handel upload profile picture
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . uniqid() . '.' . $ext;
                $uploadPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                    // Hapus gambar profil lama jika ada
                    if (!empty($oldUser['profile_picture']) && file_exists(__DIR__ . '/../../public' . $oldUser['profile_picture'])) {
                        unlink(__DIR__ . '/../../public' . $oldUser['profile_picture']);
                    }
                    $data['profile_picture'] = '/uploads/profiles/' . $filename;
                }
            }

            // handel signature
            if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
                $sigUploadDir = __DIR__ . '/../../public/uploads/signatures/';
                if (!is_dir($sigUploadDir)) mkdir($sigUploadDir, 0755, true);

                $ext = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
                $filename = 'sig_' . uniqid() . '.' . $ext;
                $uploadPath = $sigUploadDir . $filename;

                if (move_uploaded_file($_FILES['signature']['tmp_name'], $uploadPath)) {
                    // Hapus signature lama jika ada
                    if (!empty($oldUser['signature']) && file_exists(__DIR__ . '/../../public' . $oldUser['signature'])) {
                        unlink(__DIR__ . '/../../public' . $oldUser['signature']);
                    }
                    $data['signature'] = '/uploads/signatures/' . $filename;
                }
            }

            $result = $this->userModel->update($id, $data);
            echo json_encode($result ? ['success' => true, 'message' => 'User successfully updated'] : ['success' => false, 'message' => 'Failed to update user']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function delete()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : null;

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }
            if (isset($_SESSION['user_id']) && $id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
                exit;
            }

            $user = $this->userModel->getById($id);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            // user tidak bisa dihapus jika masih ada equipment yang dipinjam
            $activeBorrows = $this->borrowedModel->getActiveBorrowedByUser($id);
            if ($activeBorrows && count($activeBorrows) > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot delete! This user still has ' . count($activeBorrows) . ' unreturned equipment(s).'
                ]);
                exit;
            }

            if (!empty($user['profile_picture'])) {
                $picPath = __DIR__ . '/../../public' . $user['profile_picture'];
                if (file_exists($picPath)) {
                    @unlink($picPath);
                }
            }

            if (!empty($user['signature'])) {
                $sigPath = __DIR__ . '/../../public' . $user['signature'];
                if (file_exists($sigPath)) @unlink($sigPath);
            }

            // Delete user
            $result = $this->userModel->delete($id);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User successfully deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
        } catch (Exception $e) {
            error_log("Delete User Exception: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }
}
