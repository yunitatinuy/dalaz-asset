<?php

class ReturnModel extends Model
{
    protected $table = '`return`';
    protected $primaryKey = 'id';

    public function insert($data)
    {
        $query = "INSERT INTO `return` (user_id, borrowed_id, equipment_id, asset_number, quantity, date, time, description)
                VALUES (:user_id, :borrowed_id, :equipment_id, :asset_number, :quantity, :date, :time, :description)";

        $this->db->query($query);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':borrowed_id', $data['borrowed_id']);
        $this->db->bind(':equipment_id', $data['equipment_id']);
        $this->db->bind(':asset_number', $data['asset_number']);
        $this->db->bind(':quantity', $data['quantity']);
        $this->db->bind(':date', $data['date']);
        $this->db->bind(':time', $data['time']);
        $this->db->bind(':description', $data['description']);

        if ($this->db->execute()) {
            return $this->db->lastInsertId(); 
        } else {
            return false;
        }
    }

    public function getAllWithDetails()
    {
        $this->db->query("SELECT * FROM `return` ORDER BY date DESC, time DESC");
        return $this->db->resultSet();
    }

    public function getReturnedToday()
    {
        $query = "SELECT COUNT(*) as total FROM `return` WHERE DATE(date) = CURDATE()";
        $this->db->query($query);
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }

    public function getById($id)
    {
        $query = "SELECT r.*, b.user_id, b.equipment_id 
                FROM `return` r
                LEFT JOIN borrowed b ON r.borrowed_id = b.id
                WHERE r.id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Mengambil status kondisi terakhir dari sebuah equipment berdasarkan log return
    public function getLastReturnStatus($equipmentId)
    {
        $this->db->query("SELECT description FROM `return` WHERE equipment_id = :eq_id ORDER BY id DESC LIMIT 1");
        $this->db->bind(':eq_id', $equipmentId);
        $result = $this->db->single();
        
        return $result ? strtolower(trim($result['description'])) : null;
    }
}