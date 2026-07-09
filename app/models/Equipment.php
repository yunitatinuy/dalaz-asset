<?php

class Equipment extends Model
{
    protected $table = 'equipment';
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    // Mengambil semua data dengan nama lokasi dan kategori
    public function getAllWithDetails()
    {
        $this->db->query('
            SELECT e.*, 
                l.location_name, 
                c.category_name 
            FROM equipment e
            LEFT JOIN location l ON e.location_id = l.id
            LEFT JOIN category c ON e.category_id = c.id
            ORDER BY e.id DESC
        ');
        return $this->db->resultSet();
    }

    // Mengambil satu data lengkap berdasarkan ID
    public function getById($id)
    {
        $query = "SELECT e.*,
            c.category_name,
            l.location_name
            FROM {$this->table} e
            LEFT JOIN category c ON e.category_id = c.id
            LEFT JOIN location l ON e.location_id = l.id
            WHERE e.id = :id";

        $this->db->query($query);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Mencari data berdasarkan QR Code
    public function getByQRCode($qrCode)
    {
        $this->db->query('SELECT * FROM equipment WHERE qr_code = :qr_code');
        $this->db->bind(':qr_code', $qrCode);
        return $this->db->single();
    }

    // untuk di halaman borrowed, mencari data berdasarkan Nomor Aset
    public function getByAssetNumber($assetNumber)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE asset_number = :asset_number");
        $this->db->bind(':asset_number', $assetNumber);
        return $this->db->single();
    }

    // Cek apakah Nomor Aset sudah ada (untuk validasi unik)
    public function assetNumberExists($code, $excludeId = null)
    {
        if (empty($code)) return false;

        if ($excludeId) {
            $this->db->query('SELECT id FROM equipment WHERE asset_number = :code AND id != :id');
            $this->db->bind(':id', $excludeId);
        } else {
            $this->db->query('SELECT id FROM equipment WHERE asset_number = :code');
        }

        $this->db->bind(':code', $code);
        return $this->db->single() !== false;
    }

    // Mengambil barang yang tersedia (Quantity > 0)
    public function getAvailable()
    {
        $this->db->query('SELECT * FROM equipment WHERE quantity > 0 ORDER BY equipment_name ASC');
        return $this->db->resultSet();
    }

    public function insert($data)
    {
        $sql = "INSERT INTO equipment (
            equipment_name, quantity, location_id, category_id, asset_number, 
            owner, responsible_person, type, serial_number, manufacturer, 
            purchase_date, condition_status, equipment_details, 
            capacity, dimensions, weight, storage_temp, humidity, 
            calibration_cert_no, calibration_date, maintenance_frequency, 
            supporting_vendor, usage_steps, pictures, qr_code, doc_support
        ) VALUES (
            :equipment_name, :quantity, :location_id, :category_id, :asset_number, 
            :owner, :responsible_person, :type, :serial_number, :manufacturer, 
            :purchase_date, :condition_status, :equipment_details, 
            :capacity, :dimensions, :weight, :storage_temp, :humidity, 
            :calibration_cert_no, :calibration_date, :maintenance_frequency, 
            :supporting_vendor, :usage_steps, :pictures, :qr_code, :doc_support
        )";

        $this->db->query($sql);
        $this->bindParams($data);
        return $this->db->execute();
    }

    public function update($id, $data)
    {
        // Hapus 'id' dari array data jika ada agar tidak error saat binding
        if (isset($data['id'])) unset($data['id']);

        $sql = "UPDATE equipment SET 
                equipment_name = :equipment_name,
                quantity = :quantity,
                location_id = :location_id,
                category_id = :category_id,
                asset_number = :asset_number,
                owner = :owner,
                responsible_person = :responsible_person,
                type = :type,
                serial_number = :serial_number,
                manufacturer = :manufacturer,
                purchase_date = :purchase_date,
                condition_status = :condition_status,
                equipment_details = :equipment_details,
                capacity = :capacity,
                dimensions = :dimensions,
                weight = :weight,
                storage_temp = :storage_temp,
                humidity = :humidity,
                calibration_cert_no = :calibration_cert_no,
                calibration_date = :calibration_date,
                maintenance_frequency = :maintenance_frequency,
                supporting_vendor = :supporting_vendor,
                usage_steps = :usage_steps,
                doc_support = :doc_support,
                qr_code = :qr_code";

        // Update kolom gambar hanya jika ada data gambar baru
        if (isset($data['pictures'])) {
            $sql .= ", pictures = :pictures";
        }

        $sql .= " WHERE id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', $id);

        // Bind parameter lainnya
        $this->bindParams($data, isset($data['pictures']));

        return $this->db->execute();
    }

    // Hapus Data
    public function delete($id)
    {
        $this->db->query('DELETE FROM equipment WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Helper Methods
    // Fungsi binding otomatis untuk insert dan update
    private function bindParams($data, $hasPictures = true)
    {
        $fields = [
            'equipment_name',
            'quantity',
            'location_id',
            'category_id',
            'asset_number',
            'owner',
            'responsible_person',
            'type',
            'serial_number',
            'manufacturer',
            'purchase_date',
            'condition_status',
            'equipment_details',
            'capacity',
            'dimensions',
            'weight',
            'storage_temp',
            'humidity',
            'calibration_cert_no',
            'calibration_date',
            'maintenance_frequency',
            'supporting_vendor',
            'usage_steps',
            'qr_code',
            'doc_support'
        ];

        foreach ($fields as $field) {
            // Gunakan null coalescing operator (??) untuk menangani key yang mungkin tidak dikirim
            $value = $data[$field] ?? null;
            $this->db->bind(":$field", $value);
        }

        // Bind pictures secara kondisional
        if ($hasPictures) {
            $this->db->bind(':pictures', $data['pictures'] ?? null);
        }
    }

    public function decreaseQuantity($id, $amount)
    {
        $this->db->query('UPDATE equipment SET quantity = quantity - :amount WHERE id = :id');
        $this->db->bind(':id', $id);
        $this->db->bind(':amount', $amount);
        return $this->db->execute();
    }

    public function increaseQuantity($id, $amount)
    {
        $this->db->query('UPDATE equipment SET quantity = quantity + :amount WHERE id = :id');
        $this->db->bind(':id', $id);
        $this->db->bind(':amount', $amount);
        return $this->db->execute();
    }

    // Mengupdate status kondisi barang secara spesifik
    public function updateCondition($id, $condition)
    {
        $this->db->query("UPDATE {$this->table} SET condition_status = :condition WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':condition', $condition);
        return $this->db->execute();
    }

    // DASHBOARD

    public function getTotalEquipment()
    {
        $this->db->query("SELECT SUM(quantity) as total FROM {$this->table}");
        $res = $this->db->single();
        return $res['total'] ?? 0;
    }

    public function getLowStockEquipment()
    {
        $this->db->query("SELECT equipment_name as name, quantity, 'Equipment' as type FROM {$this->table} WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 10");
        return $this->db->resultSet();
    }

    public function getLatestEquipment()
    {
        // Mengambil 1 data dengan ID terbesar (paling baru diinput)
        $this->db->query("SELECT equipment_name, asset_number FROM {$this->table} ORDER BY id DESC LIMIT 1");
        return $this->db->single();
    }

    public function getDamagedItemsList($limit = 10)
    {
        // Menggabungkan data dari tabel Equipment (stok rusak) dan Complaint (laporan user), menggunakan UNION ALL untuk performa
        $query = "
            (
                -- Data dari Equipment (Stok yang ditandai rusak)
                SELECT 
                    e.id, 
                    e.equipment_name, 
                    e.asset_number, 
                    'System' as reported_by, 
                    'Kondisi Rusak' as issue, 
                    e.purchase_date as report_date 
                FROM {$this->table} e
                WHERE e.condition_status = 'damaged'
            )
            UNION ALL
            (
                -- Data dari Complaint (Laporan User yang belum selesai)
                SELECT 
                    e.id,
                    e.equipment_name,
                    e.asset_number,
                    u.full_name as reported_by,
                    c.defect_cause as issue,
                    c.check_date as report_date
                FROM complaint c
                JOIN `return` r ON c.return_id = r.id
                JOIN equipment e ON r.equipment_id = e.id
                JOIN users u ON r.user_id = u.id
                WHERE c.check_status != 'Completed' OR c.check_status IS NULL
            )
            ORDER BY report_date DESC
            LIMIT :limit
        ";

        $this->db->query($query);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    public function getAllWithCalibration()
    {
        $this->db->query("
            SELECT id, equipment_name, asset_number, calibration_date, maintenance_frequency 
            FROM {$this->table} 
            WHERE calibration_date IS NOT NULL AND calibration_date != '0000-00-00'
        ");
        return $this->db->resultSet();
    }
}
