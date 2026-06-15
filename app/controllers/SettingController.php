<?php

class SettingController extends Controller
{
    private $userModel;

    public function __construct()
    {
        // Cek apakah user sudah login (Baik Admin maupun User biasa)
        if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $this->userModel = $this->model('User');
    }

    public function index()
    {
        // Tentukan ID User yang sedang login
        $userId = 0;
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            $userId = $_SESSION['admin_id'];
        } elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
            $userId = $_SESSION['user_id'];
        }

        if (!$userId) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Ambil data user terbaru dari database
        $user = $this->userModel->getById($userId);

        $data = [
            'title' => 'Settings',
            'pageCSS' => 'setting',
            'pageJS' => 'setting',
            'user' => $user
        ];

        $this->view('setting/index', $data);
    }

    public function update()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            exit;
        }

        // Ambil ID User dari Session
        $userId = 0;
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            $userId = $_SESSION['admin_id'];
        } elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
            $userId = $_SESSION['user_id'];
        }

        if (!$userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Session expired. Please login again.'], 401);
            exit;
        }

        try {
            // 1. Validasi Input Profil
            $fullName = trim($_POST['full_name'] ?? '');
            $position = trim($_POST['position'] ?? ''); // Opsional

            if (empty($fullName)) {
                $this->jsonResponse(['success' => false, 'message' => 'Full Name is required']);
                exit;
            }

            // 2. Update Profil ke Database
            $profileData = [
                'full_name' => $fullName,
                'position' => $position,
            ];

            // Panggil method updateProfile di User Model
            $this->userModel->updateProfile($userId, $profileData);

            // 3. Update Password (Hanya jika kolom password diisi)
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (!empty($newPass)) {
                // Ambil data user untuk verifikasi password lama
                $user = $this->userModel->getById($userId);

                // Validasi: Password lama wajib diisi
                if (empty($currentPass)) {
                    $this->jsonResponse(['success' => false, 'message' => 'Current password is required to set a new password']);
                    exit;
                }

                // Validasi: Cek kecocokan password lama
                if (!password_verify($currentPass, $user['password'])) {
                    $this->jsonResponse(['success' => false, 'message' => 'Current password is incorrect']);
                    exit;
                }

                // Validasi: Password baru minimal 6 karakter
                if (strlen($newPass) < 6) {
                    $this->jsonResponse(['success' => false, 'message' => 'New password must be at least 6 characters']);
                    exit;
                }

                // Validasi: Konfirmasi password cocok
                if ($newPass !== $confirmPass) {
                    $this->jsonResponse(['success' => false, 'message' => 'New password confirmation does not match']);
                    exit;
                }

                // Simpan password baru (Hashing dilakukan di Model)
                $this->userModel->updatePassword($userId, $newPass);
            }

            // 4. Update Session Name 
            if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
                $_SESSION['admin_full_name'] = $fullName;
            } elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
                $_SESSION['user_full_name'] = $fullName;
                $_SESSION['full_name'] = $fullName;
            }

            // Kirim respon sukses
            $this->jsonResponse(['success' => true, 'message' => 'Settings updated successfully!']);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
        exit;
    }
}
