<?php

class Consumable extends Model
{
    protected $table = 'consumable';
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    // Mengambil semua data dengan detail lokasi dan kategori
    public function getAllWithDetails()
    {
        $this->db->query("
            SELECT 
                c.*,
                l.location_name,
                cat.category_name
            FROM {$this->table} c
            LEFT JOIN location l ON c.location_id = l.id
            LEFT JOIN category cat ON c.category_id = cat.id
            ORDER BY c.id DESC
        ");
        return $this->db->resultSet();
    }

    public function getById($id)
    {
        $this->db->query("
            SELECT 
                c.*,
                l.location_name,
                cat.category_name
            FROM {$this->table} c
            LEFT JOIN location l ON c.location_id = l.id
            LEFT JOIN category cat ON c.category_id = cat.id
            WHERE c.id = :id
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Cek duplikat kode barang
    public function itemCodeExists($code, $excludeId = null)
    {
        if (empty($code)) return false;

        if ($excludeId) {
            $this->db->query("SELECT id FROM {$this->table} WHERE item_code = :code AND id != :id");
            $this->db->bind(':id', $excludeId);
        } else {
            $this->db->query("SELECT id FROM {$this->table} WHERE item_code = :code");
        }
        $this->db->bind(':code', $code);
        return $this->db->single() !== false;
    }

    public function insert($data)
    {
        $sql = "INSERT INTO {$this->table} (
            item_name, item_code, merk, responsible_person, assigned_to, uom, min_order, 
            quantity, condition_status, location_id, category_id, supporting_vendor, 
            date, status, remark, doc_support, pictures
        ) VALUES (
            :item_name, :item_code, :merk, :responsible_person, :assigned_to, :uom, :min_order, 
            :quantity, :condition_status, :location_id, :category_id, :supporting_vendor, 
            :date, :status, :remark, :doc_support, :pictures
        )";

        $this->db->query($sql);
        $this->bindParams($data);
        return $this->db->execute();
    }

    public function update($id, $data)
    {
        if (isset($data['id'])) unset($data['id']);

        $sql = "UPDATE {$this->table} SET 
                item_name = :item_name,
                item_code = :item_code,
                merk = :merk,
                responsible_person = :responsible_person,
                assigned_to = :assigned_to,
                uom = :uom,
                min_order = :min_order,
                quantity = :quantity,
                condition_status = :condition_status,
                location_id = :location_id,
                category_id = :category_id,
                supporting_vendor = :supporting_vendor,
                doc_support = :doc_support";

        // Hanya update gambar jika ada data gambar baru/diubah
        if (isset($data['pictures'])) {
            $sql .= ", pictures = :pictures";
        }

        $sql .= " WHERE id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->bindParams($data, isset($data['pictures']));

        return $this->db->execute();
    }

    // Update Khusus Stok (Untuk fitur In/Out)
    public function updateStock($id, $newQty, $status, $date, $remark, $doc = null, $qtyChange = 0)
    {
        // 1. Ambil log riwayat yang sudah ada sebelumnya
        $this->db->query("SELECT transaction_log FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id);
        $row = $this->db->single();

        $logs = [];
        if ($row && !empty($row['transaction_log'])) {
            $logs = json_decode($row['transaction_log'], true) ?: [];
        }

        // 2. Tambahkan transaksi baru ini ke urutan paling atas (array_unshift)
        array_unshift($logs, [
            'date' => $date,
            'status' => $status,
            'qty_change' => $qtyChange,
            'current_balance' => $newQty,
            'remark' => $remark,
            'doc' => $doc
        ]);

        $newLogJson = json_encode($logs);

        // 3. Update database (Simpan stok baru, timpa status terakhir, dan simpan JSON log)
        $sql = "UPDATE {$this->table} SET quantity=:q, status=:s, date=:d, remark=:r, transaction_log=:log";

        if ($doc) {
            $sql .= ", doc_support=:doc";
        }
        $sql .= " WHERE id=:id";

        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $this->db->bind(':q', $newQty);
        $this->db->bind(':s', $status);
        $this->db->bind(':d', $date);
        $this->db->bind(':r', $remark);
        $this->db->bind(':log', $newLogJson);

        if ($doc) {
            $this->db->bind(':doc', $doc);
        }

        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // History (Mengambil data berdasarkan tanggal transaksi terakhir)
    public function getHistory($limit = 50)
    {
        $this->db->query("
            SELECT c.*, l.location_name 
            FROM {$this->table} c
            LEFT JOIN location l ON c.location_id = l.id
            WHERE c.date IS NOT NULL
            ORDER BY c.date DESC, c.id DESC
            LIMIT :limit
        ");
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    private function bindParams($data, $hasPictures = true)
    {
        $fields = [
            'item_name',
            'item_code',
            'merk',
            'responsible_person',
            'assigned_to',
            'uom',
            'min_order',
            'quantity',
            'condition_status',
            'location_id',
            'category_id',
            'supporting_vendor',
            'doc_support'
        ];

        $extra = ['date', 'status', 'remark'];

        foreach ($fields as $f) {
            $this->db->bind(":$f", $data[$f] ?? null);
        }

        foreach ($extra as $e) {
            if (array_key_exists($e, $data)) {
                $this->db->bind(":$e", $data[$e]);
            }
        }

        if ($hasPictures) {
            $this->db->bind(':pictures', $data['pictures'] ?? null);
        }
    }

    // Metode Dashboard - stok hampir habis
    public function getTotalConsumable()
    {
        $this->db->query("SELECT SUM(quantity) as total FROM {$this->table}");
        $res = $this->db->single();
        return $res['total'] ?? 0;
    }

    public function getLowStockConsumable($limit = 10)
    {
        $this->db->query("
            SELECT item_name as name, quantity, item_code as code, 'Consumable' as type 
            FROM {$this->table} 
            WHERE quantity <= min_order 
            ORDER BY quantity ASC 
            LIMIT :limit
        ");
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    // Fungsi untuk mengambil log riwayat in/out per barang
    public function getLogsByItemId($id)
    {
        $this->db->query("SELECT transaction_log FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id);
        $row = $this->db->single();

        if ($row && !empty($row['transaction_log'])) {
            return json_decode($row['transaction_log'], true) ?: [];
        }
        
        return [];
    }
}
