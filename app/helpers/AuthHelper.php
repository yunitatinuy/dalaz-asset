<?php

class AuthHelper
{
    // check admin login
    public static function isAdminLoggedIn()
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    // ambil id admin
    public static function getAdminId()
    {
        return $_SESSION['admin_id'] ?? null;
    }

    // ambil username admin
    public static function getAdminUsername()
    {
        return $_SESSION['admin_username'] ?? null;
    }

    // ambil admin fullname
    public static function getAdminFullName()
    {
        return $_SESSION['admin_full_name'] ?? 'Admin';
    }

    // ambil admin profile picture
    public static function getAdminProfilePicture()
    {
        return $_SESSION['admin_profile_picture'] ?? 'public/images/profile.png';
    }

    // Generate CSRF token
    public static function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verifikasi CSRF token
    public static function verifyCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Check admin session timeout (2 jam)
    public static function checkAdminSessionTimeout($timeout = 7200)
    {
        if (isset($_SESSION['admin_login_time'])) {
            $elapsed = time() - $_SESSION['admin_login_time'];
            if ($elapsed > $timeout) {
                // Session expired
                session_unset();
                session_destroy();
                return false;
            }
            // Update login time
            $_SESSION['admin_login_time'] = time();
        }
        return true;
    }

    // Require admin login - redirect kalau ga login
    public static function requireAdmin($redirectUrl = 'auth/login')
    {
        if (!self::isAdminLoggedIn()) {
            header('Location: ' . BASE_URL . '/' . $redirectUrl);
            exit;
        }

        // Check session timeout
        if (!self::checkAdminSessionTimeout()) {
            header('Location: ' . BASE_URL . '/' . $redirectUrl);
            exit;
        }
    }

    // cek user login
    public static function isUserLoggedIn()
    {
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
    }

    // ambil id user
    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    // Require User login - redirect kalau ga login
    public static function requireUser($redirectUrl = 'auth/login')
    {
        if (!self::isUserLoggedIn()) {
            header('Location: ' . BASE_URL . '/' . $redirectUrl);
            exit;
        }
    }
}
