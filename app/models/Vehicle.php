<?php

class Vehicle
{
    private $db;
    private $table = 'vehicles';

    public function __construct()
    {
        $this->db = new Database;
    }

    public function getAll()
    {
        $this->db->query("SELECT v.*, 
                                u.full_name AS user_name,
                                l.location_name AS location_name,
                                c.category_name AS category_name
                        FROM " . $this->table . " v
                        LEFT JOIN users u ON v.user_id = u.id
                        LEFT JOIN location l ON v.location_id = l.id
                        LEFT JOIN category c ON v.category_id = c.id
                        ORDER BY v.id DESC");
        return $this->db->resultSet();
    }

    public function getTotalVehicles()
    {
        $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }

    public function getById($id)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE id = :id");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    public function checkAssetCode($code, $excludeId = null)
    {
        $sql = "SELECT id FROM " . $this->table . " WHERE asset_code = :code";
        if ($excludeId) $sql .= " AND id != :id";
        $this->db->query($sql);
        $this->db->bind('code', $code);
        if ($excludeId) $this->db->bind('id', $excludeId);
        return $this->db->single();
    }

    public function insert($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                    (asset_code, vehicle_type, owner, user_id, brand, license_plate, 
                    year, purchase_date, equipment_details, location_id, category_id,
                    bpkb_number, stnk_number, chassis_number, engine_number, 
                    `condition`, maintenance_frequency, 
                    photos, bpkb_path, stnk_path)
                VALUES
                    (:asset_code, :vehicle_type, :owner, :user_id, :brand, :license_plate,
                    :year, :purchase_date, :equipment_details, :location_id, :category_id,
                    :bpkb_number, :stnk_number, :chassis_number, :engine_number,
                    :condition, :maintenance_frequency,
                    :photos, :bpkb_path, :stnk_path)";

        $this->db->query($query);
        $this->bindParams($data);
        return $this->db->execute();
    }

    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table . " SET
                    asset_code = :asset_code, vehicle_type = :vehicle_type, owner = :owner,
                    user_id = :user_id, brand = :brand, license_plate = :license_plate,
                    year = :year, purchase_date = :purchase_date, equipment_details = :equipment_details,
                    location_id = :location_id, category_id = :category_id,
                    bpkb_number = :bpkb_number, stnk_number = :stnk_number, 
                    chassis_number = :chassis_number, engine_number = :engine_number,
                    `condition` = :condition, maintenance_frequency = :maintenance_frequency,
                    photos = :photos, bpkb_path = :bpkb_path, stnk_path = :stnk_path
                WHERE id = :id";

        $this->db->query($query);
        $this->db->bind('id', $id);
        $this->bindParams($data);
        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM " . $this->table . " WHERE id = :id");
        $this->db->bind('id', $id);
        return $this->db->execute();
    }

    private function bindParams($data)
    {
        $this->db->bind('asset_code', $data['asset_code']);
        $this->db->bind('vehicle_type', $data['vehicle_type']);
        $this->db->bind('owner', $data['owner']);
        $this->db->bind('user_id', $data['user_id']);
        $this->db->bind('brand', $data['brand']);
        $this->db->bind('license_plate', $data['license_plate']);
        $this->db->bind('year', $data['year']);
        $this->db->bind('purchase_date', $data['purchase_date']);
        $this->db->bind('equipment_details', $data['equipment_details']);
        $this->db->bind('location_id', $data['location_id']);
        $this->db->bind('category_id', $data['category_id']);
        $this->db->bind('bpkb_number', $data['bpkb_number']);
        $this->db->bind('stnk_number', $data['stnk_number']);
        $this->db->bind('chassis_number', $data['chassis_number']);
        $this->db->bind('engine_number', $data['engine_number']);
        $this->db->bind('condition', $data['condition']);
        $this->db->bind('maintenance_frequency', $data['maintenance_frequency']);
        $this->db->bind('photos', $data['photos']);
        $this->db->bind('bpkb_path', $data['bpkb_path']);
        $this->db->bind('stnk_path', $data['stnk_path']);
    }
}
