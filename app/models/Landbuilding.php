<?php

class Landbuilding
{
    private $db;
    private $table = 'land_buildings';

    public function __construct()
    {
        $this->db = new Database;
    }

    public function getAll()
    {
        $this->db->query("SELECT lb.*, 
                                u.full_name AS user_name,
                                l.location_name AS location_name,
                                c.category_name AS category_name
                        FROM " . $this->table . " lb
                        LEFT JOIN users u ON lb.user_id = u.id
                        LEFT JOIN location l ON lb.location_id = l.id
                        LEFT JOIN category c ON lb.category_id = c.id
                        ORDER BY lb.id DESC");
        return $this->db->resultSet();
    }

    public function getTotalLandBuilding()
    {
        $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }

    public function getById($id)
    {
        $this->db->query("SELECT lb.*, 
                                u.full_name AS user_name,
                                l.location_name AS location_name,
                                c.category_name AS category_name
                        FROM " . $this->table . " lb
                        LEFT JOIN users u ON lb.user_id = u.id
                        LEFT JOIN location l ON lb.location_id = l.id
                        LEFT JOIN category c ON lb.category_id = c.id
                        WHERE lb.id = :id");
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
                    (asset_code, asset_name, responsible_person, user_id, location_id, category_id, 
                    certificate_number, certificate_date, address, surface_area, intended_use, 
                    `condition`, usage_status, site_plan_path, document_path, photos)
                VALUES
                    (:asset_code, :asset_name, :responsible_person, :user_id, :location_id, :category_id,
                    :certificate_number, :certificate_date, :address, :surface_area, :intended_use,
                    :condition, :usage_status, :site_plan_path, :document_path, :photos)";
        $this->db->query($query);
        $this->bindParams($data);
        return $this->db->execute();
    }

    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table . " SET
                    asset_code = :asset_code, asset_name = :asset_name, responsible_person = :responsible_person,
                    user_id = :user_id, location_id = :location_id, category_id = :category_id,
                    certificate_number = :certificate_number, certificate_date = :certificate_date,
                    address = :address, surface_area = :surface_area, intended_use = :intended_use,
                    `condition` = :condition, usage_status = :usage_status,
                    site_plan_path = :site_plan_path, document_path = :document_path, photos = :photos
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
        $this->db->bind('asset_name', $data['asset_name']);
        $this->db->bind('responsible_person', $data['responsible_person']);
        $this->db->bind('user_id', $data['user_id']);
        $this->db->bind('location_id', $data['location_id']);
        $this->db->bind('category_id', $data['category_id']);
        $this->db->bind('certificate_number', $data['certificate_number']);
        $this->db->bind('certificate_date', $data['certificate_date']);
        $this->db->bind('address', $data['address']);
        $this->db->bind('surface_area', $data['surface_area']);
        $this->db->bind('intended_use', $data['intended_use']);
        $this->db->bind('condition', $data['condition']);
        $this->db->bind('usage_status', $data['usage_status']);
        $this->db->bind('site_plan_path', $data['site_plan_path']);
        $this->db->bind('document_path', $data['document_path']);
        $this->db->bind('photos', $data['photos']);
    }
}
