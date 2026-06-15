<?php

class Controller
{
    public function model($model)
    {
        $modelPath = '../app/models/' . $model . '.php';

        if (!file_exists($modelPath)) {
            die("Model file not found: {$modelPath}");
        }

        require_once $modelPath;
        return new $model();
    }

    public function view($view, $data = [])
    {
        $viewPath = '../app/views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            die("View file not found: {$viewPath}");
        }

        extract($data);
        require_once $viewPath;
    }

    // load view tanpa layout (untuk public page seperti QR scan)
    public function viewPublic($view, $data = [])
    {
        if (!empty($data)) {
            extract($data);
        }

        $viewFile = '../app/views/' . $view . '.php';

        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View: {$view} not found");
        }
    }

    public function redirect($url)
    {
        header('Location: ' . BASE_URL . '/' . $url);
        exit;
    }

    protected function isAdminLoggedIn()
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    protected function requireAdmin()
    {
        if (!$this->isAdminLoggedIn()) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }

    public function jsonResponse($data, $statusCode = 200)
    {
        // Bersihkan semua output sebelumnya (spasi, warning, notice)
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    protected function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    protected function getRouteParam($param)
    {
        if (isset($_GET['url'])) {
            $url = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));

            // Jika parameter berupa angka (index), ambil dari array url
            if (is_numeric($param)) {
                return isset($url[$param]) ? $url[$param] : null;
            }

            // Jika parameter berupa string (key), cari dari query string atau path
            // Untuk case: /equipment/exportPDF/123
            // url[0] = equipment, url[1] = exportPDF, url[2] = 123
            // Kita asumsikan ID selalu di index ke-2
            if ($param === 'id') {
                return isset($url[2]) ? $url[2] : null;
            }
        }

        return null;
    }
}
