<?php

class Location extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'location';
    }

    public function getAll()
    {
        $this->db->query("SELECT * FROM {$this->table} ORDER BY location_name ASC");
        return $this->db->resultSet();
    }

    public function getById($id)
    {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function insert($data)
    {
        $this->db->query("INSERT INTO {$this->table} (location_name) VALUES (:location_name)");
        $this->db->bind(':location_name', $data['location_name']);
        return $this->db->execute();
    }

    public function update($id, $data)
    {
        $this->db->query("UPDATE {$this->table} SET location_name = :location_name WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':location_name', $data['location_name']);
        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Search assets dari 3 tabel: assets, equipment, consumable
     * @param string $search - keyword pencarian
     * @param int $locationId - filter berdasarkan location_id
     * @param int $limit - jumlah data per halaman
     * @param int $offset - offset pagination
     * @return array - ['data' => array, 'total' => int]
     */
    public function searchAssets($search, $locationId, $limit = 10, $offset = 0)
    {
        $params = [];
        $whereAsset = 'WHERE 1=1';
        $whereEquip = 'WHERE 1=1';
        $whereConsumable = 'WHERE 1=1';

        // Filter berdasarkan search keyword
        if (!empty($search)) {
            $whereAsset .= ' AND (a.asset_name LIKE :search OR a.asset_code LIKE :search)';
            $whereEquip .= ' AND (e.equipment_name LIKE :search OR e.asset_number LIKE :search)';
            $whereConsumable .= ' AND (c.item_name LIKE :search OR c.item_code LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        // Filter berdasarkan location_id
        if (!empty($locationId)) {
            $whereAsset .= ' AND a.location_id = :location_id';
            $whereEquip .= ' AND e.location_id = :location_id';
            $whereConsumable .= ' AND c.location_id = :location_id';
            $params[':location_id'] = $locationId;
        }

        // 1. Hitung total semua data
        $sqlCount = "
            SELECT 
                (SELECT COUNT(*) FROM assets a {$whereAsset}) +
                (SELECT COUNT(*) FROM equipment e {$whereEquip}) +
                (SELECT COUNT(*) FROM consumable c {$whereConsumable}) AS total
        ";

        $this->db->query($sqlCount);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $totalRow = $this->db->single();
        $total = $totalRow ? $totalRow['total'] : 0;

        // 2. Ambil data dengan UNION ALL (Asset + Equipment + Consumable)
        $sqlData = "
            (SELECT 
                a.id, 
                a.asset_name AS name, 
                a.asset_code AS code, 
                a.quantity,
                'Office Equipment' AS type, 
                l.location_name, 
                cat.category_name
            FROM assets a
            LEFT JOIN location l ON a.location_id = l.id
            LEFT JOIN category cat ON a.category_id = cat.id
            {$whereAsset})
            
            UNION ALL
            
            (SELECT 
                e.id, 
                e.equipment_name AS name, 
                e.asset_number AS code, 
                e.quantity,
                'Equipment' AS type, 
                l.location_name, 
                cat.category_name
            FROM equipment e
            LEFT JOIN location l ON e.location_id = l.id
            LEFT JOIN category cat ON e.category_id = cat.id
            {$whereEquip})
            
            UNION ALL
            
            (SELECT 
                c.id, 
                c.item_name AS name, 
                c.item_code AS code, 
                c.quantity,
                'Inventory' AS type, 
                l.location_name, 
                cat.category_name
            FROM consumable c
            LEFT JOIN location l ON c.location_id = l.id
            LEFT JOIN category cat ON c.category_id = cat.id
            {$whereConsumable})
            
            ORDER BY name
            LIMIT :limit OFFSET :offset
        ";

        $this->db->query($sqlData);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $this->db->bind(':limit', (int)$limit, PDO::PARAM_INT);
        $this->db->bind(':offset', (int)$offset, PDO::PARAM_INT);

        $results = $this->db->resultSet();

        return ['data' => $results, 'total' => $total];
    }

    
}
