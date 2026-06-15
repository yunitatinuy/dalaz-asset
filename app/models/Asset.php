<?php

class Asset extends Model
{
    protected $table = 'assets';

    public function getAllWithDetails($search = null)
    {
        $sql = "
            SELECT a.*, 
                l.location_name, 
                c.category_name 
            FROM assets a
            LEFT JOIN location l ON a.location_id = l.id
            LEFT JOIN category c ON a.category_id = c.id
        ";

        // Logika Search
        if ($search) {
            $sql .= " WHERE a.asset_name LIKE :search 
                    OR a.asset_code LIKE :search 
                    OR a.owner LIKE :search 
                    OR a.brand LIKE :search";
        }

        $sql .= " ORDER BY a.id DESC";

        $this->db->query($sql);

        if ($search) {
            $this->db->bind(':search', "%$search%");
        }

        return $this->db->resultSet();
    }

    public function getById($id)
    {
        $this->db->query('SELECT * FROM assets WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getByCode($code)
    {
        $this->db->query('
        SELECT a.*, 
            l.location_name, 
            c.category_name 
        FROM assets a
        LEFT JOIN location l ON a.location_id = l.id
        LEFT JOIN category c ON a.category_id = c.id
        WHERE a.asset_code = :code
        LIMIT 1
    ');
        $this->db->bind(':code', $code);
        return $this->db->single();
    }

    public function getTotalAssets()
    {
        $this->db->query("SELECT SUM(quantity) as total FROM {$this->table}");
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }

    public function getCompanyAssets()
    {
        $query = "SELECT a.*, 
                        l.location_name, 
                        c.category_name
                FROM {$this->table} a
                LEFT JOIN location l ON a.location_id = l.id
                LEFT JOIN category c ON a.category_id = c.id
                ORDER BY a.asset_name ASC, a.asset_code ASC";

        $this->db->query($query);
        return $this->db->resultSet();
    }

    public function getDamagedAssets()
    {
        $query = "SELECT a.*, 
                        l.location_name, 
                        c.category_name
                FROM {$this->table} a
                LEFT JOIN location l ON a.location_id = l.id
                LEFT JOIN category c ON a.category_id = c.id
                WHERE a.description = 'damaged'
                ORDER BY a.asset_name, a.asset_code";

        $this->db->query($query);
        return $this->db->resultSet();
    }

    public function insert($data)
    {
        $this->db->query('
            INSERT INTO assets 
            (asset_code, asset_name, quantity, location_id, category_id, description, 
            owner, responsible_person, assigned_to, brand, serial_number, purchase_date,
            details, capacity, dimensions, weight, color, maintenance_frequency, vendor,
            pictures, qr_code) 
            VALUES 
            (:code, :name, :qty, :loc, :cat, :desc, 
            :owner, :resp, :assign, :brand, :sn, :pdate,
            :details, :cap, :dim, :weight, :color, :maint, :vendor,
            :pic, :qr)
        ');

        $this->bindParams($data);

        return $this->db->execute();
    }

    public function update($id, $data)
    {
        $sql = '
            UPDATE assets 
            SET asset_code = :code,
                asset_name = :name,
                quantity = :qty,
                location_id = :loc,
                category_id = :cat,
                description = :desc,
                owner = :owner,
                responsible_person = :resp,
                assigned_to = :assign,
                brand = :brand,
                serial_number = :sn,
                purchase_date = :pdate,
                details = :details,
                capacity = :cap,
                dimensions = :dim,
                weight = :weight,
                color = :color,
                maintenance_frequency = :maint,
                vendor = :vendor,
                qr_code = :qr
        ';

        // Hanya tambahkan kolom pictures ke SQL update jika ada upload baru
        if (isset($data['pictures'])) {
            $sql .= ', pictures = :pic';
        }

        $sql .= ' WHERE id = :id';

        $this->db->query($sql);
        $this->db->bind(':id', $id);

        $this->bindParams($data);

        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query('DELETE FROM assets WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function assetCodeExists($code, $excludeId = null)
    {
        if ($excludeId) {
            $this->db->query("SELECT id FROM assets WHERE asset_code = :code AND id != :id");
            $this->db->bind(':id', $excludeId);
        } else {
            $this->db->query("SELECT id FROM assets WHERE asset_code = :code");
        }
        $this->db->bind(':code', $code);
        return $this->db->single() !== false;
    }

    // Helper untuk binding agar tidak berulang
    private function bindParams($data)
    {
        $this->db->bind(':code', $data['asset_code']);
        $this->db->bind(':name', $data['asset_name']);
        $this->db->bind(':qty', $data['quantity']);
        $this->db->bind(':loc', $data['location_id']);
        $this->db->bind(':cat', $data['category_id']);
        $this->db->bind(':desc', $data['description']);
        $this->db->bind(':owner', $data['owner'] ?? null);
        $this->db->bind(':resp', $data['responsible_person'] ?? null);
        $this->db->bind(':assign', $data['assigned_to'] ?? null);
        $this->db->bind(':brand', $data['brand'] ?? null);
        $this->db->bind(':sn', $data['serial_number'] ?? null);
        $this->db->bind(':pdate', !empty($data['purchase_date']) ? $data['purchase_date'] : null);
        $this->db->bind(':details', $data['details'] ?? null);
        $this->db->bind(':cap', $data['capacity'] ?? null);
        $this->db->bind(':dim', $data['dimensions'] ?? null);
        $this->db->bind(':weight', $data['weight'] ?? null);
        $this->db->bind(':color', $data['color'] ?? null);
        $this->db->bind(':maint', $data['maintenance_frequency'] ?? null);
        $this->db->bind(':vendor', $data['vendor'] ?? null);
        $this->db->bind(':qr', $data['qr_code'] ?? null);

        if ($this->db->query_string_contains(':pic')) {
            $this->db->bind(':pic', $data['pictures'] ?? null);
        }
    }
}
