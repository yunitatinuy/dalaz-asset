<?php

class Borrowed extends Model
{
    protected $table = 'borrowed';
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    public function insert($data)
    {
        $query = "INSERT INTO borrowed (user_id, equipment_id, asset_number, no_jd, client, location, working_days, quantity, date, time, description, status) 
            VALUES (:user_id, :equipment_id, :asset_number, :no_jd, :client, :location, :working_days, :quantity, :date, :time, :description, :status)";

        $this->db->query($query);
        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }

        if ($this->db->execute()) {
            return $this->db->lastInsertId(); // Mengembalikan ID baru
        } else {
            return false;
        }
    }

    public function getActiveBorrowedByUser($userId)
    {
        $this->db->query("
            SELECT 
                b.id as borrowed_id,
                b.no_jd,
                b.asset_number,
                b.quantity,
                b.status,
                e.id as equipment_id,
                e.equipment_name,
                e.pictures
            FROM borrowed b
            JOIN equipment e ON b.equipment_id = e.id
            WHERE b.user_id = :user_id AND b.status = 'borrowed'
        ");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function getActiveBorrowedByUserAndEquipment($userId, $equipmentId)
    {
        $this->db->query("
            SELECT * FROM borrowed 
            WHERE user_id = :user_id 
            AND equipment_id = :equipment_id 
            AND status = 'borrowed'
            LIMIT 1
        ");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':equipment_id', $equipmentId);
        return $this->db->single();
    }

    public function updateStatus($id, $status)
    {
        $this->db->query("UPDATE borrowed SET status = :status WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        return $this->db->execute();
    }

    public function getUserHistory($userId, $limit = 10, $offset = 0)
    {
        // Query ini mengambil data peminjaman dan menggabungkannya dengan data pengembalian (jika ada)
        $this->db->query("
            SELECT 
                b.id,
                b.no_jd,
                b.date as borrow_date,
                b.status,
                u.full_name as user_name,
                e.equipment_name as asset_name,
                r.date as return_date
            FROM borrowed b
            JOIN users u ON b.user_id = u.id
            JOIN equipment e ON b.equipment_id = e.id
            LEFT JOIN `return` r ON b.id = r.borrowed_id
            WHERE b.user_id = :user_id
            ORDER BY b.date DESC
            LIMIT :limit OFFSET :offset
        ");

        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);

        return $this->db->resultSet();
    }

    public function countUserHistory($userId)
    {
        $this->db->query("SELECT COUNT(*) as total FROM borrowed WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }

    public function checkIsBorrowed($equipmentId)
    {
        // Hitung jumlah transaksi dengan status 'borrowed' untuk alat ini
        $this->db->query("SELECT COUNT(*) as total FROM borrowed WHERE equipment_id = :id AND status = 'borrowed'");
        $this->db->bind(':id', $equipmentId);
        $res = $this->db->single();
        return ($res['total'] > 0);
    }

    // DASHBOARD
    public function getTotalBorrowed()
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'borrowed'";
        $this->db->query($query);
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }


    public function getTotalTransactions()
    {
        $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }

    public function getRecentBorrowings($limit = 10)
    {
        $query = "SELECT 
                    b.id, 
                    b.no_jd, 
                    b.date as borrow_date, 
                    b.status,
                    u.full_name as borrower_name,
                    COALESCE(e.equipment_name, a.asset_name) as item_name,
                    COALESCE(e.asset_number, a.asset_code) as asset_code
                FROM borrowed b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN equipment e ON b.equipment_id = e.id
                LEFT JOIN assets a ON b.asset_number = a.asset_code 
                WHERE b.status = 'borrowed'
                ORDER BY b.date DESC, b.time DESC
                LIMIT :limit";

        $this->db->query($query);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    public function getOverdueItems()
    {
        $query = "SELECT 
                    b.id,
                    b.asset_number,
                    b.date as borrow_date,
                    u.full_name as borrower_name,
                    COALESCE(e.equipment_name, 'Unknown Item') as equipment_name,
                    (DATEDIFF(CURDATE(), b.date) - b.working_days) as days_overdue
                FROM borrowed b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN equipment e ON b.equipment_id = e.id
                WHERE b.status = 'borrowed' 
                AND DATEDIFF(CURDATE(), b.date) > b.working_days
                ORDER BY days_overdue DESC";

        $this->db->query($query);
        return $this->db->resultSet();
    }

    public function getPopularAssets($limit = 4)
    {
        $query = "SELECT e.equipment_name, e.asset_number, SUM(b.quantity) as total_qty
                FROM borrowed b
                JOIN equipment e ON b.equipment_id = e.id
                GROUP BY e.id, e.equipment_name, e.asset_number
                ORDER BY total_qty DESC
                LIMIT :limit";

        $this->db->query($query);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    // HISTORY
    public function getEquipmentHistorySummary()
    {
        $query = "SELECT 
            e.id,
            e.equipment_name,
            e.asset_number,
            e.quantity as stock_available,
            COALESCE(SUM(CASE WHEN b.status = 'borrowed' THEN b.quantity ELSE 0 END), 0) as total_borrowed
            FROM equipment e
            LEFT JOIN {$this->table} b ON e.id = b.equipment_id
            GROUP BY e.id, e.equipment_name, e.quantity
            ORDER BY e.equipment_name ASC";

        $this->db->query($query);
        return $this->db->resultSet();
    }

    public function getTotalBorrowedByEquipmentId($equipmentId)
    {
        $query = "SELECT SUM(quantity) as total FROM {$this->table} WHERE equipment_id = :id AND status = 'borrowed'";
        $this->db->query($query);
        $this->db->bind(':id', $equipmentId);
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }

    public function getHistoryForEquipment($equipmentId, $searchName = null, $searchDate = null)
    {
        $query = "SELECT 
            b.id,
            b.date as borrow_date,
            b.time as borrow_time,
            b.description as borrow_status,
            u.full_name as user_name,
            b.no_jd,
            b.client,
            r.date as return_date,
            r.time as return_time,
            r.description as return_status
            FROM {$this->table} b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN `return` r ON b.id = r.borrowed_id
            WHERE b.equipment_id = :equipment_id";

        if ($searchName) {
            $query .= " AND u.full_name LIKE :search_name";
        }

        if ($searchDate) {
            $query .= " AND b.date = :search_date";
        }

        $query .= " ORDER BY b.date DESC, b.time DESC";

        $this->db->query($query);
        $this->db->bind(':equipment_id', $equipmentId);

        if ($searchName) {
            $this->db->bind(':search_name', '%' . $searchName . '%');
        }

        if ($searchDate) {
            $this->db->bind(':search_date', $searchDate);
        }

        return $this->db->resultSet();
    }

    public function getGlobalHistory($limit = 10, $offset = 0)
    {
        $this->db->query("
            SELECT 
                b.id,
                b.no_jd,
                b.date as borrow_date,
                b.status,
                u.full_name as user_name,
                u.employee_no,
                e.equipment_name as asset_name,
                MAX(r.date) as return_date  /* Ambil tanggal return terakhir jika ada duplikat */
            FROM borrowed b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN equipment e ON b.equipment_id = e.id
            LEFT JOIN `return` r ON b.id = r.borrowed_id
            GROUP BY b.id /* Mencegah Data Double */
            ORDER BY b.date DESC, b.time DESC
            LIMIT :limit OFFSET :offset
        ");

        $this->db->bind(':limit', $limit);
        $this->db->bind(':offset', $offset);

        return $this->db->resultSet();
    }

    public function getGlobalDueToday()
    {
        $query = "SELECT b.*, e.equipment_name, e.asset_number, u.full_name as borrower_name,
                  DATEDIFF(CURDATE(), b.date) as days_elapsed
                  FROM {$this->table} b
                  LEFT JOIN equipment e ON b.equipment_id = e.id
                  LEFT JOIN users u ON b.user_id = u.id
                  WHERE b.status = 'borrowed'
                  AND DATEDIFF(CURDATE(), b.date) = b.working_days"; // Cek tepat hari H

        $this->db->query($query);
        return $this->db->resultSet();
    }

    //  Hitung total semua history (untuk pagination)
    public function countGlobalHistory()
    {
        $this->db->query("SELECT COUNT(*) as total FROM borrowed");
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }

    public function getGlobalRecentHistory($limit = 7)
    {
        $query = "SELECT b.*, 
                  e.equipment_name, 
                  e.asset_number,
                  u.full_name as borrower_name,
                  MAX(r.date) as return_date, 
                  MAX(r.description) as return_condition
                  FROM {$this->table} b
                  LEFT JOIN equipment e ON b.equipment_id = e.id
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN `return` r ON b.id = r.borrowed_id
                  GROUP BY b.id 
                  ORDER BY b.date DESC, b.time DESC
                  LIMIT :limit";

        $this->db->query($query);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    // Untuk notifikasi "baru saja dikembalikan"
    public function getLatestReturnLog()
    {
        $query = "SELECT r.*, e.equipment_name, u.full_name 
                  FROM `return` r
                  JOIN equipment e ON r.equipment_id = e.id
                  JOIN users u ON r.user_id = u.id
                  ORDER BY r.date DESC, r.time DESC LIMIT 1";
        $this->db->query($query);
        return $this->db->single();
    }

    public function countGlobalActive()
    {
        $this->db->query("SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'borrowed'");
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }
}
