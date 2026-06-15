<?php

class Intangible
{
    private $db;
    private $table = 'intangibles';

    public function __construct()
    {
        $this->db = new Database;
    }

    public function getAll()
    {
        $this->db->query("SELECT i.*, 
                                l.location_name AS location_name,
                                c.category_name AS category_name
                        FROM " . $this->table . " i
                        LEFT JOIN location l ON i.location_id = l.id
                        LEFT JOIN category c ON i.category_id = c.id
                        ORDER BY i.id DESC");
        return $this->db->resultSet();
    }

    public function getTotalIntangibles()
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
                    (asset_code, document_name, certificate_number, issuing_agency, 
                    issue_date, effective_date, expiration_date, 
                    document_status, location_id, category_id, document_path)
                VALUES
                    (:asset_code, :document_name, :certificate_number, :issuing_agency,
                    :issue_date, :effective_date, :expiration_date,
                    :document_status, :location_id, :category_id, :document_path)";

        $this->db->query($query);
        $this->bindParams($data);
        return $this->db->execute();
    }

    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table . " SET
                    asset_code = :asset_code, document_name = :document_name, 
                    certificate_number = :certificate_number, issuing_agency = :issuing_agency,
                    issue_date = :issue_date, effective_date = :effective_date, expiration_date = :expiration_date,
                    document_status = :document_status, location_id = :location_id, category_id = :category_id,
                    document_path = :document_path
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
        $this->db->bind('document_name', $data['document_name']);
        $this->db->bind('certificate_number', $data['certificate_number']);
        $this->db->bind('issuing_agency', $data['issuing_agency']);
        $this->db->bind('issue_date', $data['issue_date']);
        $this->db->bind('effective_date', $data['effective_date']);
        $this->db->bind('expiration_date', $data['expiration_date']);
        $this->db->bind('document_status', $data['document_status']);
        $this->db->bind('location_id', $data['location_id']);
        $this->db->bind('category_id', $data['category_id']);
        $this->db->bind('document_path', $data['document_path']);
    }
}
