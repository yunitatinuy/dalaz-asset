<?php

class HistoryController extends Controller
{
    private $borrowedModel;
    private $equipmentModel;

    public function __construct()
    {
        if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $this->borrowedModel = $this->model('Borrowed');
        $this->equipmentModel = $this->model('Equipment');
    }

    // HALAMAN KHUSUS ADMIN
    public function index()
    {
        $this->requireAdmin(); // Pastikan hanya admin

        // Ambil summary semua equipment
        $summary = $this->borrowedModel->getEquipmentHistorySummary();

        $data = [
            'title' => 'Equipment History (Admin)',
            'summary' => $summary,
            'pageJS' => 'history'
        ];

        $this->view('history/index', $data);
    }

    public function detail($id = 0)
    {
        $this->requireAdmin(); // Pastikan hanya admin

        if (empty($id)) {
            header('Location: ' . BASE_URL . '/history');
            exit;
        }

        // ambil data equipment
        $equipment = $this->equipmentModel->getById($id);

        if (!$equipment) {
            header('Location: ' . BASE_URL . '/history');
            exit;
        }

        $filters = [
            'name' => $_GET['name'] ?? null,
            'date' => $_GET['date'] ?? null
        ];

        // Ambil riwayat peminjaman untuk equipment ini dengan filter
        $history = $this->borrowedModel->getHistoryForEquipment($id, $filters['name'], $filters['date']);

        // Hitung total peminjaman untuk equipment ini
        $totalBorrowed = $this->borrowedModel->getTotalBorrowedByEquipmentId($id);

        $data = [
            'title' => 'History Detail - ' . $equipment['equipment_name'],
            'equipment' => $equipment,
            'history' => $history,
            'totalBorrowed' => $totalBorrowed,
            'filters' => $filters,
            'pageJS' => 'history'
        ];

        $this->view('history/detail', $data);
    }

    // HALAMAN KHUSUS USER
    public function user()
    {
        if (!isset($_SESSION['user_logged_in'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Ambil Data
        $history = $this->borrowedModel->getGlobalHistory($limit, $offset);

        // Hitung Total Data
        $totalRecords = $this->borrowedModel->countGlobalHistory(); // <-- Ini sudah dihitung

        $totalPages = ceil($totalRecords / $limit);

        $data = [
            'title' => 'User History - Dalaz Asset',
            'history' => $history,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];

        $this->view('history/user', $data);
    }
}
