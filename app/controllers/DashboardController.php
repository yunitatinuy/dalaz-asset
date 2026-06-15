<?php

class DashboardController extends Controller
{
    private $equipmentModel;
    private $consumableModel;
    private $borrowedModel;
    private $returnModel;
    private $assetModel;
    private $landBuildingModel;
    private $vehicleModel;
    private $intangibleModel;

    public function __construct()
    {
        $this->equipmentModel = $this->model('Equipment');
        $this->consumableModel = $this->model('Consumable');
        $this->borrowedModel = $this->model('Borrowed');
        $this->returnModel = $this->model('ReturnModel');
        $this->assetModel = $this->model('Asset');
        $this->landBuildingModel = $this->model('Landbuilding');
        $this->vehicleModel = $this->model('Vehicle');
        $this->intangibleModel = $this->model('Intangible');
    }

    public function index()
    {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            header('Location: ' . BASE_URL . '/dashboard/admin');
        } elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
            header('Location: ' . BASE_URL . '/dashboard/user');
        } else {
            header('Location: ' . BASE_URL . '/auth/login');
        }
        exit;
    }

    public function admin()
    {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $stats = $this->getAdminStats();

        $data = [
            'title' => 'Admin Dashboard - ' . APP_NAME,
            'admin_name' => $_SESSION['admin_full_name'] ?? 'Admin',
            'stats' => $stats
        ];

        $this->view('dashboard/admin', $data);
    }

    public function user()
    {
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $stats = $this->getUserStats();

        $data = [
            'title' => 'Dashboard - ' . APP_NAME,
            'user_name' => $_SESSION['user_full_name'] ?? 'User',
            'stats' => $stats
        ];

        $this->view('dashboard/user', $data);
    }

    private function getAdminStats()
    {
        // 1. Hitungan Total Aset (GABUNGAN 6 TABEL)
        // assets + equipment + consumable (Stok) + land + vehicle + intangible (Unit)
        $totalAssets =
            $this->assetModel->getTotalAssets()
            + $this->equipmentModel->getTotalEquipment()
            + $this->consumableModel->getTotalConsumable()
            + $this->landBuildingModel->getTotalLandBuilding()
            + $this->vehicleModel->getTotalVehicles()
            + $this->intangibleModel->getTotalIntangibles();

        $borrowedAssets = $this->borrowedModel->getTotalBorrowed();
        $returnedToday = $this->returnModel->getReturnedToday();
        $transactions = $this->borrowedModel->getTotalTransactions();

        // 2. Data Stok & Kondisi
        $lowStockItems = $this->consumableModel->getLowStockConsumable(10);
        $damagedItems = $this->equipmentModel->getDamagedItemsList(10);

        // 3. Aktivitas Peminjaman
        $currentlyBorrowed = $this->borrowedModel->getRecentBorrowings();
        $overdueItems = $this->borrowedModel->getOverdueItems();

        // 4. LOGIKA PERINGATAN KALIBRASI (Disederhanakan)
        $calibrationData = $this->equipmentModel->getAllWithCalibration();
        $calibrationAlerts = [];
        $today = new DateTime();

        foreach ($calibrationData as $item) {
            $nextDueDateStr = $this->calculateNextDueDate($item['calibration_date'], $item['maintenance_frequency']);

            if ($nextDueDateStr) {
                try {
                    $dueDate = new DateTime($nextDueDateStr);
                    $warningStartDate = clone $dueDate;
                    $warningStartDate->modify('-6 months');

                    if ($today > $dueDate) {
                        $daysLate = $today->diff($dueDate)->days;
                        $calibrationAlerts[] = [
                            'name' => $item['equipment_name'],
                            'asset_number' => $item['asset_number'],
                            'due_date' => $nextDueDateStr,
                            'status' => 'expired',
                            'message' => "Overdue {$daysLate} days"
                        ];
                    } elseif ($today >= $warningStartDate) {
                        $interval = $today->diff($dueDate);
                        $monthsLeft = ($interval->y * 12) + $interval->m;
                        $daysLeft = $interval->d;
                        $statusColor = ($monthsLeft < 2) ? 'danger' : 'warning';

                        $calibrationAlerts[] = [
                            'name' => $item['equipment_name'],
                            'asset_number' => $item['asset_number'],
                            'due_date' => $nextDueDateStr,
                            'status' => $statusColor,
                            'message' => "Remaining {$monthsLeft} months {$daysLeft} days"
                        ];
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        usort($calibrationAlerts, function ($a, $b) {
            return strtotime($a['due_date']) - strtotime($b['due_date']);
        });

        return [
            'total_assets' => $totalAssets, // Nilai total dari 6 tabel
            'borrowed_assets' => $borrowedAssets,
            'returned_today' => $returnedToday,
            'transactions' => $transactions,
            'low_stock_count' => count($lowStockItems),
            'low_stock_items' => $lowStockItems,
            'damaged_count' => count($damagedItems),
            'damaged_items' => $damagedItems,
            'currently_borrowed' => $currentlyBorrowed,
            'overdue_items' => $overdueItems,
            'calibration_alerts' => array_slice($calibrationAlerts, 0, 10)
        ];
    }

    // Menghitung tanggal jatuh tempo berikutnya berdasarkan frekuensi
    private function calculateNextDueDate($lastCalDate, $frequencyString)
    {
        if (empty($lastCalDate) || $lastCalDate == '0000-00-00' || empty($frequencyString)) {
            return null;
        }

        try {
            $date = new DateTime($lastCalDate);
            $freqStr = strtolower($frequencyString);

            // Ambil angka dari string (contoh: "12 Bulan" -> 12)
            $number = (int) filter_var($freqStr, FILTER_SANITIZE_NUMBER_INT);

            if ($number > 0) {
                // Cek satuan waktu
                if (strpos($freqStr, 'year') !== false || strpos($freqStr, 'year') !== false || strpos($freqStr, 'thn') !== false) {
                    $date->modify("+$number year");
                } else {
                    // Default ke bulan
                    $date->modify("+$number month");
                }
                return $date->format('Y-m-d');
            }
        } catch (Exception $e) {
            return null;
        }
        return null;
    }

    private function getUserStats()
    {
        // Total barang yang sedang dipinjam oleh SIAPA SAJA
        $borrowedCount = $this->borrowedModel->countGlobalActive();

        // Total transaksi yang pernah terjadi di sistem
        $transactionsCount = $this->borrowedModel->getTotalTransactions();

        // Daftar barang yang telat (Overdue) dari SEMUA user
        $overdue = $this->borrowedModel->getOverdueItems();

        // Aktivitas terakhir dari semua user
        $recentHistory = $this->borrowedModel->getGlobalRecentHistory(5);

        // Barang yang harus dikembalikan HARI INI dari SEMUA user
        $dueTodayItems = $this->borrowedModel->getGlobalDueToday();
        $dueTodayCount = count($dueTodayItems);

        // Aset paling populer
        $popularAssets = $this->borrowedModel->getPopularAssets(4);

        // Info Barang Baru
        $newArrival = $this->equipmentModel->getLatestEquipment();

        // Logika Notifikasi
        $notifications = [];

        // OVERDUE (MERAH)
        if (count($overdue) > 0) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'fas fa-exclamation-triangle',
                'msg' => "Attention: There are <strong>" . count($overdue) . " overdue items</strong> that have not been returned yet."
            ];
        }

        // BARANG BARU (BIRU)
        if ($newArrival) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'fas fa-bullhorn',
                'msg' => "New Arrival: <strong>" . htmlspecialchars($newArrival['equipment_name']) . "</strong> is now available."
            ];
        }

        // REMINDER DUE TODAY (KUNING)
        if ($dueTodayCount > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'fas fa-clock',
                'msg' => "Reminder: <strong>" . $dueTodayCount . " items</strong> from various users are due for return TODAY."
            ];
        }

        // DEFAULT (HIJAU)
        if (empty($notifications)) {
            $notifications[] = [
                'type' => 'success',
                'icon' => 'fas fa-check-circle',
                'msg' => "Inventory status is healthy. No issues found."
            ];
        }

        return [
            'borrowed_count' => $borrowedCount,
            'transactions_count' => $transactionsCount,
            'overdue_count' => count($overdue),
            'due_today_count' => $dueTodayCount, 
            'overdue_list' => $overdue,
            'recent_history' => $recentHistory,
            'popular_assets' => $popularAssets,
            'notifications' => $notifications
        ];
    }
}
