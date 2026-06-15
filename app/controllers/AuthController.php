<?php

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = $this->model('User');
    }

    public function login()
    {
        // Cek jika sudah login sebagai admin
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            header('Location: ' . BASE_URL . '/dashboard/admin');
            exit;
        }

        // Cek jika sudah login sebagai user 
        if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
            header('Location: ' . BASE_URL . '/dashboard/user');
            exit;
        }

        $data = ['title' => 'Login - ' . APP_NAME];
        $this->view('auth/login', $data);
    }

    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Username and password must be filled in.'];
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $user = $this->userModel->authenticate($username, $password);

        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid username or password.'];
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // LOGIN BERHASIL

        // Skenario 1: Login sebagai ADMIN
        if ($user['role'] === 'admin') {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_full_name'] = $user['full_name'];
            $_SESSION['admin_role'] = 'admin';
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();

            header('Location: ' . BASE_URL . '/dashboard/admin');
            exit;
        }

        // Skenario 2: Login sebagai USER (Long Session)
        else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['user_role'] = 'user';
            $_SESSION['user_logged_in'] = true;

            header('Location: ' . BASE_URL . '/dashboard/user');
            exit;
        }
    }

    public function logout()
    {
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }
}
