<?php
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: ' . BASE_URL . '/auth/login');
    exit;
}
$uri = $_SERVER['REQUEST_URI'];
$active = 'dashboard';
if (strpos($uri, '/borrowed') !== false) $active = 'borrow';
if (strpos($uri, '/return') !== false) $active = 'return';
if (strpos($uri, '/history') !== false) $active = 'history';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?? 'User Dashboard' ?> - DALAZ Asset</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/user-header.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/user-dashboard.css">

    <style>
        .user-header {
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
        }
    </style>
</head>

<body>
    <header class="user-header">
        <div class="header-container">
            <div class="header-left">
                <a href="<?= BASE_URL ?>/dashboard/user" class="header-logo">
                    <img src="<?= BASE_URL ?>/public/images/logo.png" alt="Dalaz">
                </a>

                <nav class="header-nav" id="userNav">
                    <a href="<?= BASE_URL ?>/dashboard/user" class="nav-link <?= $active == 'dashboard' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>/borrowed/scan" class="nav-link <?= $active == 'borrow' ? 'active' : '' ?>">
                        <i class="fas fa-box-open"></i> Borrow
                    </a>
                    <a href="<?= BASE_URL ?>/return/scan" class="nav-link <?= $active == 'return' ? 'active' : '' ?>">
                        <i class="fas fa-undo-alt"></i> Return
                    </a>
                    <a href="<?= BASE_URL ?>/history/user" class="nav-link <?= $active == 'history' ? 'active' : '' ?>">
                        <i class="fas fa-history"></i> History
                    </a>
                </nav>
            </div>

            <div class="header-right">
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="user-dropdown-container">
                    <button class="user-pill-btn" onclick="toggleUserDropdown()">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_full_name'] ?? 'User') ?></span>
                        <div class="user-icon-circle">
                            <i class="fas fa-user"></i>
                        </div>
                    </button>

                    <div class="dropdown-menu" id="userMenu">
                        <a href="<?= BASE_URL ?>/setting/index" class="dropdown-item">
                            <i class="fas fa-cog"></i> Setting
                        </a>
                        <a href="<?= BASE_URL ?>/auth/logout" class="dropdown-item" style="color:red;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <script>
        function toggleUserDropdown() {
            document.getElementById('userMenu').classList.toggle('show');
        }

        // Fungsi Toggle Mobile Menu
        function toggleMobileMenu() {
            const nav = document.getElementById('userNav');
            const btnIcon = document.querySelector('.mobile-menu-btn i');

            nav.classList.toggle('active');

            if (nav.classList.contains('active')) {
                btnIcon.classList.remove('fa-bars');
                btnIcon.classList.add('fa-times');
            } else {
                btnIcon.classList.remove('fa-times');
                btnIcon.classList.add('fa-bars');
            }
        }

        window.onclick = function(e) {
            // Tutup dropdown user
            if (!e.target.closest('.user-dropdown-container')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
            // Tutup mobile menu jika klik di luar header
            if (!e.target.closest('.user-header') && !e.target.closest('.mobile-menu-btn')) {
                const nav = document.getElementById('userNav');
                if (nav && nav.classList.contains('active')) {
                    nav.classList.remove('active');
                    const btnIcon = document.querySelector('.mobile-menu-btn i');
                    if (btnIcon) {
                        btnIcon.classList.remove('fa-times');
                        btnIcon.classList.add('fa-bars');
                    }
                }
            }
        }
    </script>

    <main class="main-content">