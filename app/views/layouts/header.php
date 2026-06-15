<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= APP_NAME ?? 'Dalaz Asset Management' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/components.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/asset.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/search.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/history.css">


    <?php if (isset($pageCSS)): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/<?= $pageCSS ?>.css">
    <?php endif; ?>
</head>

<body>
    <!-- Header Navigasi -->
    <header class="header">
        <div class="header-container">
            <!-- Logo -->
            <div class="header-logo">
                <img src="<?= BASE_URL ?>/public/images/logo.png" alt="<?= APP_NAME ?? 'Dalaz' ?>" class="logo-img">
            </div>

            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigation Menu -->
            <nav class="header-nav" id="headerNav">
                <ul class="nav-menu">
                    <!-- Dashboard -->
                    <li>
                        <a href="<?= BASE_URL ?>/dashboard"
                            class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false) ? 'active' : ''; ?>">
                            Dashboard
                        </a>
                    </li>

                    <!-- Asset Data Dropdown -->
                    <li class="nav-item-dropdown">
                        <a href="javascript:void(0);"
                            class="nav-link <?php
                                            $url = isset($_GET['url']) ? $_GET['url'] : '';
                                            echo (strpos($url, 'asset') !== false ||
                                                strpos($url, 'equipment') !== false ||
                                                strpos($url, 'consumable') !== false ||
                                                strpos($url, 'landbuilding') !== false ||
                                                strpos($url, 'vehicle') !== false ||
                                                strpos($url, 'intangible') !== false) ? 'active' : '';
                                            ?>">
                            Asset Data
                        </a>
                        <div class="nav-submenu">
                            <a href="<?= BASE_URL ?>/public/index.php?url=asset/company"
                                class="nav-submenu-item <?php echo (strpos($url, 'asset') !== false) ? 'active' : ''; ?>">
                                Office Equipment
                            </a>
                            <a href="<?= BASE_URL ?>/public/index.php?url=equipment"
                                class="nav-submenu-item <?php echo (strpos($url, 'equipment') !== false) ? 'active' : ''; ?>">
                                Equipment
                            </a>
                            <a href="<?= BASE_URL ?>/public/index.php?url=consumable"
                                class="nav-submenu-item <?php echo (strpos($url, 'consumable') !== false) ? 'active' : ''; ?>">
                                Inventory
                            </a>
                            <a href="<?= BASE_URL ?>/public/index.php?url=landbuilding"
                                class="nav-submenu-item <?php echo (strpos($url, 'landbuilding') !== false) ? 'active' : ''; ?>">
                                Land & Building
                            </a>
                            <a href="<?= BASE_URL ?>/public/index.php?url=vehicle"
                                class="nav-submenu-item <?php echo (strpos($url, 'vehicle') !== false) ? 'active' : ''; ?>">
                                Vehicle
                            </a>
                            <a href="<?= BASE_URL ?>/public/index.php?url=intangible"
                                class="nav-submenu-item <?php echo (strpos($url, 'intangible') !== false) ? 'active' : ''; ?>">
                                Intangible Assets
                            </a>
                        </div>
                    </li>

                    <!-- Location -->
                    <li>
                        <a href="<?= BASE_URL ?>/location"
                            class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/location') !== false ? 'active' : ''; ?>">
                            Location
                        </a>
                    </li>

                    <!-- Category -->
                    <li>
                        <a href="<?= BASE_URL ?>/category"
                            class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/category') !== false ? 'active' : ''; ?>">
                            Category
                        </a>
                    </li>

                    <!-- User Data -->
                    <li>
                        <a href="<?= BASE_URL ?>/user"
                            class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/user') !== false && strpos($_SERVER['REQUEST_URI'], '/user/profile') === false) ? 'active' : ''; ?>">
                            User Data
                        </a>
                    </li>

                    <!-- History -->
                    <li>
                        <a href="<?= BASE_URL ?>/history"
                            class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/history') !== false ? 'active' : ''; ?>">
                            History
                        </a>
                    </li>

                    <!-- Complaint -->
                    <li>
                        <a href="<?= BASE_URL ?>/complaint"
                            class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/complaint') !== false ? 'active' : ''; ?>">
                            Complaint
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile Menu -->
            <div class="user-menu">
                <div class="user-profile">
                    <img src="<?= BASE_URL ?>/public/images/profile.png" alt="Profile" class="user-avatar">
                    <div class="dropdown-menu">
                        <a href="<?= BASE_URL ?>/setting/index" class="dropdown-item">Settings</a>
                        <a href="<?= BASE_URL ?>/auth/logout" class="dropdown-item">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">