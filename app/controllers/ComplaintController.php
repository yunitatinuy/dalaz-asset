<?php
require_once __DIR__ . '/../helpers/DefectReportPDF.php';

class ComplaintController extends Controller
{
    private $complaintModel;

    public function __construct()
    {
        $this->requireAdmin();
        $this->complaintModel = $this->model('Complaint');
    }

    public function index()
    {
        $complaints = $this->complaintModel->getAllPendingComplaints();
        $data = [
            'title' => 'Complaints',
            'complaints' => $complaints,
            'pageJS' => 'complaint'
        ];
        $this->view('complaint/index', $data);
    }

    public function getDetails()
    {
        header('Content-Type: application/json');
        $return_id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if (!$return_id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID required'], 400);
        }

        try {
            $data = $this->complaintModel->getDetailsByReturnId($return_id);
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        exit;
    }

    public function respond()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Request not valid'], 400);
            exit;
        }

        try {
            $data = [
                'return_id' => $_POST['return_id'],
                'control_no' => $_POST['control_no'],
                'treatment' => $_POST['treatment'],
                'check_date' => $_POST['check_date'],
                'check_status' => $_POST['check_status']
            ];

            $result = $this->complaintModel->saveResponse($data);

            $this->jsonResponse([
                'success' => $result,
                'message' => $result ? 'Response successfully saved' : 'Failed to save response'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function exportPdf($return_id)
    {
        // 1. Ambil Data
        $data = $this->complaintModel->getDetailsByReturnId($return_id);

        if (!$data) {
            die("Complaint data with return ID {$return_id} not found");
        }

        // Masukkan Nama Admin dari Session
        $data['admin_name'] = $_SESSION['admin_full_name'] ?? 'Administrator';
        
        // 2. Cek Class 
        if (!class_exists('DefectReportPDF')) {
            require_once __DIR__ . '/../helpers/DefectReportPDF.php';
        }

        // 3. Generate PDF
        $pdf = new DefectReportPDF();
        $pdf->generate($data);
    }
}
