<?php

class CategoryController extends Controller
{
    private $categoryModel;

    public function __construct()
    {
        $this->requireAdmin();
        $this->categoryModel = $this->model('Category');
    }

    public function index()
    {
        $data = [
            'title' => 'Category Data - ' . APP_NAME,
            'pageCSS' => 'asset',
            'pageJS' => 'category',
            'activeMenu' => 'category'
        ];
        $this->view('category/index', $data);
    }

    // CRUD
    public function getAll()
    {
        header('Content-Type: application/json');
        try {
            $categories = $this->categoryModel->getAll();
            echo json_encode(['success' => true, 'data' => $categories]);
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
            $category = $this->categoryModel->getById($id);
            echo json_encode($category ? ['success' => true, 'data' => $category] : ['success' => false, 'message' => 'Not found']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function add()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Request not valid']);
            exit;
        }

        if (empty($_POST['categoryName'])) {
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            exit;
        }

        try {
            $result = $this->categoryModel->insert(['category_name' => trim($_POST['categoryName'])]);
            echo json_encode($result ? ['success' => true, 'message' => 'Category successfully added'] : ['success' => false, 'message' => 'Failed to add category']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function edit()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Request not valid']);
            exit;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        if (!$id || empty($_POST['categoryName'])) {
            echo json_encode(['success' => false, 'message' => 'ID and category name are required']);
            exit;
        }

        try {
            $result = $this->categoryModel->update($id, ['category_name' => trim($_POST['categoryName'])]);
            echo json_encode($result ? ['success' => true, 'message' => 'Category successfully updated'] : ['success' => false, 'message' => 'Failed to update category']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function delete()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Request not valid']);
            exit;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        try {
            // Cek apakah ada barang yang menggunakan kategori ini
            $checkUsage = $this->categoryModel->searchAssets('', $id, 1, 0);

            // Jika jumlah barang yang ditemukan lebih dari 0, ga bisa dihapus
            if ($checkUsage['total'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot delete: There are ' . $checkUsage['total'] . ' items still using this category.'
                ]);
                exit;
            }

            // Jika aman (kosong), bisa dihapus
            $result = $this->categoryModel->delete($id);
            echo json_encode($result ? ['success' => true, 'message' => 'Category successfully deleted'] : ['success' => false, 'message' => 'Failed to delete category']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Cari aset berdasarkan kategori atau kata kunci
    public function searchAssets()
    {
        header('Content-Type: application/json');

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($page < 1) {
            $page = 1;
        }
        $limit = 10;
        $offset = ($page - 1) * $limit;

        if (empty($search) && empty($categoryId)) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'pagination' => ['total' => 0, 'page' => 1, 'totalPages' => 0]
            ]);
            exit;
        }

        try {
            $result = $this->categoryModel->searchAssets($search, $categoryId, $limit, $offset);
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
