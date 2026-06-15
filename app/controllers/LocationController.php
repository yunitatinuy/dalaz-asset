<?php

class LocationController extends Controller
{
    private $locationModel;

    public function __construct()
    {
        $this->requireAdmin();
        $this->locationModel = $this->model('Location');
    }

    public function index()
    {
        $data = [
            'title' => 'Location Data - ' . APP_NAME,
            'pageCSS' => 'asset',
            'pageJS' => 'location',
            'activeMenu' => 'location'
        ];
        $this->view('location/index', $data);
    }

    // CRUD LOCATION
    public function getAll()
    {
        header('Content-Type: application/json');
        try {
            $locations = $this->locationModel->getAll();
            echo json_encode(['success' => true, 'data' => $locations]);
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
            $location = $this->locationModel->getById($id);
            echo json_encode($location ? ['success' => true, 'data' => $location] : ['success' => false, 'message' => 'Location not found']);
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

        if (empty($_POST['locationName'])) {
            echo json_encode(['success' => false, 'message' => 'Location name is required']);
            exit;
        }

        try {
            $result = $this->locationModel->insert(['location_name' => trim($_POST['locationName'])]);
            echo json_encode($result ? ['success' => true, 'message' => 'Location added!'] : ['success' => false, 'message' => 'Failed to add location']);
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

        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        if (!$id || empty($_POST['locationName'])) {
            echo json_encode(['success' => false, 'message' => 'ID and location name are required']);
            exit;
        }

        try {
            $result = $this->locationModel->update($id, ['location_name' => trim($_POST['locationName'])]);
            echo json_encode($result ? ['success' => true, 'message' => 'Location updated!'] : ['success' => false, 'message' => 'Failed to update']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function delete()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        try {
            // Cek apakah ada barang di lokasi ini. Menggunakan fungsi searchAssets (Pencarian kosong, filter lokasi ID ini)
            $checkUsage = $this->locationModel->searchAssets('', $id, 1, 0);

            // Jika jumlah barang yang ditemukan lebih dari 0, ga bisa dihapus
            if ($checkUsage['total'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot delete: There are ' . $checkUsage['total'] . ' items still registered at this location.'
                ]);
                exit;
            }

            // Jika aman (kosong), bisa dihapus
            $result = $this->locationModel->delete($id);
            echo json_encode($result ? ['success' => true, 'message' => 'Location deleted!'] : ['success' => false, 'message' => 'Failed to delete']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function searchAssets()
    {
        header('Content-Type: application/json');

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $locationId = isset($_GET['location_id']) ? intval($_GET['location_id']) : null;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($page < 1) {
            $page = 1;
        }
        $limit = 10;
        $offset = ($page - 1) * $limit;

        if (empty($search) && empty($locationId)) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'pagination' => ['total' => 0, 'page' => 1, 'totalPages' => 0]
            ]);
            exit;
        }

        try {
            $result = $this->locationModel->searchAssets($search, $locationId, $limit, $offset);
            echo json_encode([
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'total' => (int)$result['total'],
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil($result['total'] / $limit)
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
